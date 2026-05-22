<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function e(?string $valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

function redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function validar_csrf(): void
{
    $token = $_POST['csrf_token'] ?? '';

    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        exit('Token de seguranca invalido.');
    }
}

function status_solicitacao(): array
{
    return ['Recebido', 'Em análise', 'Em desenvolvimento', 'Aguardando cliente', 'Concluído'];
}

function tipos_alteracao(): array
{
    return ['Alterar texto', 'Trocar imagem', 'Adicionar conteúdo', 'Remover conteúdo', 'Corrigir erro', 'Criar nova seção', 'Outro'];
}

function paginas_padrao(): array
{
    return ['Início', 'Sobre', 'Serviços', 'Contato', 'Outra'];
}

function classe_status(string $status): string
{
    $mapa = [
        'Recebido' => 'status-recebido',
        'Em análise' => 'status-analise',
        'Em desenvolvimento' => 'status-dev',
        'Aguardando cliente' => 'status-cliente',
        'Concluído' => 'status-concluido',
    ];

    return $mapa[$status] ?? 'status-recebido';
}

function pasta_upload_cliente(int $clienteId, int $solicitacaoId): string
{
    return __DIR__ . '/../uploads/solicitacoes/cliente_' . $clienteId . '/solicitacao_' . $solicitacaoId;
}

function caminho_publico_upload(int $clienteId, int $solicitacaoId, string $arquivo): string
{
    return 'uploads/solicitacoes/cliente_' . $clienteId . '/solicitacao_' . $solicitacaoId . '/' . $arquivo;
}

function validar_e_salvar_uploads(array $files, int $clienteId, int $solicitacaoId, PDO $pdo): void
{
    if (empty($files['name']) || !is_array($files['name'])) {
        return;
    }

    $permitidas = ['jpg', 'jpeg', 'png', 'pdf', 'docx'];
    $maxArquivos = 5;
    $maxBytes = 5 * 1024 * 1024;
    $totalEnviados = count(array_filter($files['name']));

    if ($totalEnviados > $maxArquivos) {
        throw new RuntimeException('Envie no maximo 5 arquivos por solicitacao.');
    }

    $pasta = pasta_upload_cliente($clienteId, $solicitacaoId);

    if (!is_dir($pasta) && !mkdir($pasta, 0755, true)) {
        throw new RuntimeException('Nao foi possivel criar a pasta de uploads.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimesPermitidos = [
        'jpg' => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png' => ['image/png'],
        'pdf' => ['application/pdf'],
        'docx' => [
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/zip',
        ],
    ];

    foreach ($files['name'] as $indice => $nomeOriginal) {
        if ($files['error'][$indice] === UPLOAD_ERR_NO_FILE) {
            continue;
        }

        if ($files['error'][$indice] !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Falha ao enviar um dos arquivos.');
        }

        if ($files['size'][$indice] > $maxBytes) {
            throw new RuntimeException('Cada arquivo deve ter no maximo 5MB.');
        }

        $extensao = strtolower(pathinfo($nomeOriginal, PATHINFO_EXTENSION));

        if (!in_array($extensao, $permitidas, true)) {
            throw new RuntimeException('Formato de arquivo nao permitido.');
        }

        $mime = $finfo->file($files['tmp_name'][$indice]) ?: 'application/octet-stream';

        if (!in_array($mime, $mimesPermitidos[$extensao], true)) {
            throw new RuntimeException('Tipo real do arquivo nao permitido.');
        }

        $nomeSeguro = bin2hex(random_bytes(16)) . '.' . $extensao;
        $destino = $pasta . '/' . $nomeSeguro;

        if (!move_uploaded_file($files['tmp_name'][$indice], $destino)) {
            throw new RuntimeException('Nao foi possivel salvar o arquivo enviado.');
        }

        $stmt = $pdo->prepare(
            'INSERT INTO arquivos_solicitacao (solicitacao_id, nome_original, nome_arquivo, caminho, tipo, tamanho)
             VALUES (:solicitacao_id, :nome_original, :nome_arquivo, :caminho, :tipo, :tamanho)'
        );
        $stmt->execute([
            ':solicitacao_id' => $solicitacaoId,
            ':nome_original' => $nomeOriginal,
            ':nome_arquivo' => $nomeSeguro,
            ':caminho' => caminho_publico_upload($clienteId, $solicitacaoId, $nomeSeguro),
            ':tipo' => $mime,
            ':tamanho' => (int) $files['size'][$indice],
        ]);
    }
}

function resumo_tamanho(int $bytes): string
{
    if ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2, ',', '.') . ' MB';
    }

    return number_format($bytes / 1024, 1, ',', '.') . ' KB';
}
