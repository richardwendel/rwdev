<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/conexao.php';
require_once __DIR__ . '/../../includes/auth.php';

exigir_admin();

const DOCS_MAX_UPLOAD_BYTES = 8388608;

function docs_upload_dir(): string
{
    return dirname(__DIR__, 2) . '/uploads/documentos-trabalho';
}

function docs_render_header(): void
{
    ?>
    <header class="app-header admin">
      <a href="../dashboard.php" class="marca">RWDEV Admin</a>
      <nav>
        <a href="../dashboard.php">Dashboard</a>
        <a href="../clientes.php">Clientes</a>
        <a href="../convites.php">Convites</a>
        <a href="../projetos.php">Projetos</a>
        <a href="../solicitacoes.php">Solicitações</a>
        <a href="../depoimentos.php">Depoimentos</a>
        <a href="../diagnostico-metricas.php">&#128202; Diagnóstico</a>
        <a href="../ponto/index.php">SONI PONTO</a>
        <a href="index.php">DOCUMENTOS</a>
        <a href="../../logout.php">Sair</a>
      </nav>
    </header>
    <?php
}

function docs_render_nav(string $ativo): void
{
    $itens = [
        'index.php' => 'Todos',
        'novo.php' => 'Novo documento',
        'categorias.php' => 'Categorias',
    ];
    ?>
    <nav class="ponto-tabs" aria-label="Navegação DOCUMENTOS">
      <?php foreach ($itens as $href => $rotulo): ?>
        <a class="<?= $ativo === $href ? 'ativo' : '' ?>" href="<?= e($href) ?>"><?= e($rotulo) ?></a>
      <?php endforeach; ?>
    </nav>
    <?php
}

function docs_mensagem_erro(Throwable $erro): string
{
    if ($erro instanceof RuntimeException) {
        return $erro->getMessage();
    }

    error_log('Erro DOCUMENTOS DO TRABALHO: ' . $erro->getMessage());

    return 'Não foi possível concluir a operação agora.';
}

function docs_categorias(PDO $pdo, bool $somenteAtivas = true): array
{
    $sql = 'SELECT nome FROM documentos_trabalho_categorias';

    if ($somenteAtivas) {
        $sql .= ' WHERE ativo = 1';
    }

    $sql .= ' ORDER BY nome';

    return $pdo->query($sql)->fetchAll(PDO::FETCH_COLUMN);
}

function docs_tem_soni_ponto(PDO $pdo): bool
{
    try {
        $stmtPontos = $pdo->query("SHOW TABLES LIKE 'pontos_trabalho'");
        $stmtLojas = $pdo->query("SHOW TABLES LIKE 'lojas_trabalho'");

        return (bool) $stmtPontos->fetchColumn() && (bool) $stmtLojas->fetchColumn();
    } catch (Throwable $erro) {
        return false;
    }
}

function docs_pontos(PDO $pdo): array
{
    if (!docs_tem_soni_ponto($pdo)) {
        return [];
    }

    try {
        return $pdo->query(
            'SELECT p.id, p.data, p.dia_semana, l.codigo_loja
             FROM pontos_trabalho p
             INNER JOIN lojas_trabalho l ON l.id = p.loja_id
             ORDER BY p.data DESC, p.id DESC
             LIMIT 100'
        )->fetchAll();
    } catch (Throwable $erro) {
        return [];
    }
}

function docs_validar_data(?string $data): ?string
{
    $data = trim((string) $data);

    if ($data === '') {
        return null;
    }

    $dt = DateTime::createFromFormat('Y-m-d', $data);

    if (!$dt || $dt->format('Y-m-d') !== $data) {
        throw new RuntimeException('Informe datas válidas.');
    }

    return $data;
}

function docs_upload_arquivo(array $arquivo, ?string $arquivoAtual = null): ?string
{
    if (($arquivo['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return $arquivoAtual;
    }

    if (($arquivo['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Falha ao enviar o arquivo.');
    }

    if ((int) $arquivo['size'] > DOCS_MAX_UPLOAD_BYTES) {
        throw new RuntimeException('O arquivo deve ter no máximo 8MB.');
    }

    $extensao = strtolower(pathinfo((string) $arquivo['name'], PATHINFO_EXTENSION));
    $permitidas = ['pdf', 'jpg', 'jpeg', 'png'];

    if (!in_array($extensao, $permitidas, true)) {
        throw new RuntimeException('Envie apenas PDF, JPG, JPEG ou PNG.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file((string) $arquivo['tmp_name']) ?: 'application/octet-stream';
    $mimesPermitidos = [
        'pdf' => ['application/pdf'],
        'jpg' => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png' => ['image/png'],
    ];

    if (!in_array($mime, $mimesPermitidos[$extensao], true)) {
        throw new RuntimeException('Tipo real do arquivo não permitido.');
    }

    $pasta = docs_upload_dir();

    if (!is_dir($pasta) && !mkdir($pasta, 0755, true)) {
        throw new RuntimeException('Não foi possível preparar a pasta de documentos.');
    }

    $nomeSeguro = date('YmdHis') . '-' . bin2hex(random_bytes(16)) . '.' . $extensao;
    $destino = $pasta . '/' . $nomeSeguro;

    if (!move_uploaded_file((string) $arquivo['tmp_name'], $destino)) {
        throw new RuntimeException('Não foi possível salvar o arquivo enviado.');
    }

    if ($arquivoAtual) {
        $arquivoAnterior = $pasta . '/' . basename($arquivoAtual);

        if (is_file($arquivoAnterior)) {
            unlink($arquivoAnterior);
        }
    }

    return $nomeSeguro;
}

function docs_dados_post(?string $arquivoAtual = null): array
{
    $titulo = trim($_POST['titulo'] ?? '');
    $categoria = trim($_POST['categoria'] ?? '');

    if ($titulo === '' || $categoria === '') {
        throw new RuntimeException('Título e categoria são obrigatórios.');
    }

    return [
        'titulo' => $titulo,
        'categoria' => $categoria,
        'empresa' => trim($_POST['empresa'] ?? ''),
        'cargo' => trim($_POST['cargo'] ?? ''),
        'data_documento' => docs_validar_data($_POST['data_documento'] ?? ''),
        'data_validade' => docs_validar_data($_POST['data_validade'] ?? ''),
        'arquivo' => docs_upload_arquivo($_FILES['arquivo'] ?? ['error' => UPLOAD_ERR_NO_FILE], $arquivoAtual),
        'observacoes' => trim($_POST['observacoes'] ?? ''),
        'ponto_id' => (int) ($_POST['ponto_id'] ?? 0) ?: null,
        'ativo' => isset($_POST['ativo']) ? 1 : 0,
    ];
}

function docs_buscar(PDO $pdo, int $id): ?array
{
    if (docs_tem_soni_ponto($pdo)) {
        $sql = 'SELECT d.*, p.data AS ponto_data, p.dia_semana AS ponto_dia, l.codigo_loja
                FROM documentos_trabalho d
                LEFT JOIN pontos_trabalho p ON p.id = d.ponto_id
                LEFT JOIN lojas_trabalho l ON l.id = p.loja_id
                WHERE d.id = :id
                LIMIT 1';
    } else {
        $sql = 'SELECT d.*, NULL AS ponto_data, NULL AS ponto_dia, NULL AS codigo_loja
                FROM documentos_trabalho d
                WHERE d.id = :id
                LIMIT 1';
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $id]);
    $documento = $stmt->fetch();

    return $documento ?: null;
}

function docs_extensao(string $arquivo): string
{
    return strtolower(pathinfo($arquivo, PATHINFO_EXTENSION));
}
