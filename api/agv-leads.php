<?php
declare(strict_types=1);

define('RWDEV_JSON_ENDPOINT', true);

require_once __DIR__ . '/../portal/includes/funcoes.php';
require_once __DIR__ . '/../portal/includes/agv_leads.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('X-Content-Type-Options: nosniff');

function agv_resposta_json(array $dados, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($dados, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function agv_requisicao_mesma_origem(): bool
{
    $secFetchSite = strtolower((string) ($_SERVER['HTTP_SEC_FETCH_SITE'] ?? ''));

    if ($secFetchSite !== '' && !in_array($secFetchSite, ['same-origin', 'none'], true)) {
        return false;
    }

    $origem = trim((string) ($_SERVER['HTTP_ORIGIN'] ?? ''));

    if ($origem === '') {
        return true;
    }

    $host = strtolower((string) parse_url($origem, PHP_URL_HOST));

    return in_array($host, ['rwdev.com.br', 'www.rwdev.com.br', '127.0.0.1', 'localhost'], true);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    agv_resposta_json(['sucesso' => false, 'mensagem' => 'Método não permitido.'], 405);
}

if (!agv_requisicao_mesma_origem()) {
    agv_resposta_json(['sucesso' => false, 'mensagem' => 'Origem da solicitação não permitida.'], 403);
}

$contentType = strtolower((string) ($_SERVER['CONTENT_TYPE'] ?? ''));

if (!str_starts_with($contentType, 'application/json')) {
    agv_resposta_json(['sucesso' => false, 'mensagem' => 'Formato da solicitação inválido.'], 415);
}

$entrada = json_decode((string) file_get_contents('php://input'), true);

if (!is_array($entrada)) {
    agv_resposta_json(['sucesso' => false, 'mensagem' => 'Dados da solicitação inválidos.'], 400);
}

if (trim((string) ($entrada['website'] ?? '')) !== '') {
    agv_resposta_json(['sucesso' => true, 'mensagem' => 'Solicitação recebida.']);
}

$ultimaSolicitacao = (int) ($_SESSION['agv_ultima_solicitacao'] ?? 0);

if ($ultimaSolicitacao > 0 && (time() - $ultimaSolicitacao) < 10) {
    agv_resposta_json(['sucesso' => false, 'mensagem' => 'Aguarde alguns segundos antes de enviar novamente.'], 429);
}

$validacao = agv_validar_lead($entrada);

if ($validacao['erros']) {
    agv_resposta_json([
        'sucesso' => false,
        'mensagem' => 'Revise os campos destacados.',
        'erros' => $validacao['erros'],
    ], 422);
}

try {
    require_once __DIR__ . '/../portal/config/conexao.php';
    $registro = agv_salvar_lead($pdo, $validacao['dados']);
    $_SESSION['agv_ultima_solicitacao'] = time();

    agv_resposta_json([
        'sucesso' => true,
        'codigo' => $registro['codigo'],
        'whatsapp_url' => agv_url_whatsapp($validacao['dados'], $registro['codigo']),
    ], 201);
} catch (Throwable $erro) {
    error_log('[agv_leads] erro ao registrar lead: ' . $erro->getMessage());
    agv_resposta_json([
        'sucesso' => false,
        'mensagem' => 'Não foi possível registrar sua solicitação agora. Tente novamente em instantes.',
    ], 500);
}
