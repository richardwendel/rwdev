<?php
declare(strict_types=1);

// Carrega a conexão PDO já usada pelo portal RWDEV.
require_once __DIR__ . '/portal/config/conexao.php';

// Caminho físico onde as fotos dos depoimentos serão armazenadas.
const DEPOIMENTOS_UPLOAD_DIR = __DIR__ . '/uploads/depoimentos';

// Caminho público salvo no banco e usado no src das imagens.
const DEPOIMENTOS_UPLOAD_URL = 'uploads/depoimentos';

// Tamanho máximo permitido para foto enviada no formulário: 3MB.
const DEPOIMENTOS_MAX_UPLOAD_BYTES = 3145728;

// Extensões aceitas para foto do depoimento.
const DEPOIMENTOS_EXTENSOES_PERMITIDAS = ['jpg', 'jpeg', 'png', 'webp'];

// MIME types reais aceitos, validados pelo conteúdo do arquivo.
const DEPOIMENTOS_MIMES_PERMITIDOS = [
    'jpg' => ['image/jpeg'],
    'jpeg' => ['image/jpeg'],
    'png' => ['image/png'],
    'webp' => ['image/webp'],
];

// Envia uma resposta JSON padronizada para as chamadas AJAX.
function resposta_json(array $dados, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($dados, JSON_UNESCAPED_UNICODE);
    exit;
}

// Remove espaços extras e limita o tamanho de campos de texto simples.
function campo_texto(string $valor, int $limite): string
{
    $valor = trim($valor);
    $valor = preg_replace('/\s+/', ' ', $valor) ?? '';

    return function_exists('mb_substr') ? mb_substr($valor, 0, $limite) : substr($valor, 0, $limite);
}

// Salva a foto opcional do depoimento com validação de extensão, tamanho e MIME real.
function salvar_foto_depoimento(array $arquivo): ?string
{
    if (($arquivo['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if (($arquivo['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Falha ao enviar a foto.');
    }

    if (($arquivo['size'] ?? 0) > DEPOIMENTOS_MAX_UPLOAD_BYTES) {
        throw new RuntimeException('A foto deve ter no máximo 3MB.');
    }

    $nomeOriginal = (string) ($arquivo['name'] ?? '');
    $extensao = strtolower(pathinfo($nomeOriginal, PATHINFO_EXTENSION));

    if (!in_array($extensao, DEPOIMENTOS_EXTENSOES_PERMITIDAS, true)) {
        throw new RuntimeException('Formato de foto não permitido. Use jpg, jpeg, png ou webp.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file((string) $arquivo['tmp_name']) ?: 'application/octet-stream';

    if (!in_array($mime, DEPOIMENTOS_MIMES_PERMITIDOS[$extensao], true)) {
        throw new RuntimeException('O arquivo enviado não parece ser uma imagem válida.');
    }

    if (!is_dir(DEPOIMENTOS_UPLOAD_DIR) && !mkdir(DEPOIMENTOS_UPLOAD_DIR, 0755, true)) {
        throw new RuntimeException('Não foi possível criar a pasta de uploads.');
    }

    $nomeSeguro = bin2hex(random_bytes(16)) . '.' . $extensao;
    $destino = DEPOIMENTOS_UPLOAD_DIR . '/' . $nomeSeguro;

    if (!move_uploaded_file((string) $arquivo['tmp_name'], $destino)) {
        throw new RuntimeException('Não foi possível salvar a foto enviada.');
    }

    return DEPOIMENTOS_UPLOAD_URL . '/' . $nomeSeguro;
}

// Remove uma foto antiga do servidor quando um depoimento é excluído.
function excluir_foto_depoimento(?string $caminho): void
{
    if (!$caminho) {
        return;
    }

    $arquivo = __DIR__ . '/' . ltrim($caminho, '/');
    $base = realpath(DEPOIMENTOS_UPLOAD_DIR);
    $real = realpath($arquivo);

    if ($base && $real && strpos($real, $base) === 0 && is_file($real)) {
        unlink($real);
    }
}
