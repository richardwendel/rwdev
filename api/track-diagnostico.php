<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    resposta_json(['sucesso' => false, 'mensagem' => 'Metodo nao permitido.'], 405);
}

$entrada = json_decode((string) file_get_contents('php://input'), true);

if (!is_array($entrada)) {
    resposta_json(['sucesso' => false, 'mensagem' => 'JSON invalido.'], 400);
}

$eventosPermitidos = [
    'page_view',
    'diagnosis_start',
    'diagnosis_completed',
    'whatsapp_click',
];

$eventType = (string) ($entrada['event_type'] ?? '');
$page = (string) ($entrada['page'] ?? '/diagnostico');

function texto_metricas(mixed $valor, int $limite): string
{
    $valor = trim((string) $valor);
    $valor = preg_replace('/\s+/', ' ', $valor) ?? '';

    return function_exists('mb_substr') ? mb_substr($valor, 0, $limite) : substr($valor, 0, $limite);
}

function origem_metricas(?string $referer): string
{
    $referer = strtolower(trim((string) $referer));

    if ($referer === '') {
        return 'Direto';
    }

    $host = parse_url($referer, PHP_URL_HOST);
    $base = $host ? strtolower((string) $host) : $referer;

    if (str_contains($base, 'google.')) {
        return 'Google';
    }

    if (str_contains($base, 'instagram.')) {
        return 'Instagram';
    }

    if (str_contains($base, 'facebook.') || str_contains($base, 'fb.')) {
        return 'Facebook';
    }

    if (str_contains($base, 'linkedin.')) {
        return 'LinkedIn';
    }

    if (str_contains($base, 'whatsapp.') || str_contains($base, 'wa.me')) {
        return 'WhatsApp';
    }

    return 'Outros';
}

function classificar_lead(array $respostas, int $pontuacao, bool $clicouWhatsapp): string
{
    $semSite = ($respostas['site_profissional'] ?? '') !== 'Sim';
    $semPerfilGoogle = ($respostas['perfil_google'] ?? '') !== 'Sim';
    $nuncaAnunciou = ($respostas['google_ads'] ?? '') === 'Nao' || ($respostas['google_ads'] ?? '') === 'Não';

    if ($semSite && $semPerfilGoogle && $nuncaAnunciou && $clicouWhatsapp) {
        return 'Muito Quente';
    }

    if ($clicouWhatsapp || $pontuacao < 80) {
        return 'Quente';
    }

    return 'Morno';
}

function salvar_lead_diagnostico(PDO $pdo, array $entrada, string $referer, string $ipHash, string $userAgentHash): void
{
    $lead = $entrada['lead'] ?? null;

    if (!is_array($lead)) {
        return;
    }

    $dados = $lead['dados'] ?? [];
    $respostas = $lead['respostas'] ?? [];

    if (!is_array($dados) || !is_array($respostas)) {
        return;
    }

    $empresa = texto_metricas($dados['empresa'] ?? '', 150);
    $cidade = texto_metricas($dados['cidade'] ?? '', 120);
    $responsavel = texto_metricas($dados['responsavel'] ?? '', 150);
    $whatsapp = texto_metricas(preg_replace('/[^\d()+\-\s]/', '', (string) ($dados['whatsapp'] ?? '')) ?? '', 30);
    $email = texto_metricas($dados['email'] ?? '', 150);
    $pontuacao = max(0, min(100, (int) ($lead['pontuacao'] ?? 0)));

    if ($empresa === '' || $cidade === '' || $responsavel === '' || $whatsapp === '') {
        return;
    }

    $respostasPermitidas = [
        'google_aparece',
        'whatsapp_contatos',
        'perfil_google',
        'instagram_ativo',
        'google_ads',
        'site_profissional',
        'visitas_site',
        'contatos_google',
    ];
    $respostasLimpas = [];

    foreach ($respostasPermitidas as $campoResposta) {
        $respostasLimpas[$campoResposta] = texto_metricas($respostas[$campoResposta] ?? '', 40);
    }

    $respostasJson = json_encode($respostasLimpas, JSON_UNESCAPED_UNICODE);
    $classificacao = classificar_lead($respostasLimpas, $pontuacao, false);
    $origem = origem_metricas($referer);

    $stmt = $pdo->prepare(
        'INSERT INTO diagnostico_leads
         (empresa, cidade, responsavel, whatsapp, email, pontuacao, classificacao, origem, referer, respostas_json, ip_hash, user_agent_hash, created_at, updated_at)
         VALUES
         (:empresa, :cidade, :responsavel, :whatsapp, :email, :pontuacao, :classificacao, :origem, :referer, :respostas_json, :ip_hash, :user_agent_hash, NOW(), NOW())'
    );
    $stmt->execute([
        ':empresa' => $empresa,
        ':cidade' => $cidade,
        ':responsavel' => $responsavel,
        ':whatsapp' => $whatsapp,
        ':email' => $email !== '' ? $email : null,
        ':pontuacao' => $pontuacao,
        ':classificacao' => $classificacao,
        ':origem' => $origem,
        ':referer' => $referer !== '' ? $referer : null,
        ':respostas_json' => $respostasJson !== false ? $respostasJson : null,
        ':ip_hash' => $ipHash,
        ':user_agent_hash' => $userAgentHash,
    ]);
}

function marcar_click_whatsapp_lead(PDO $pdo, string $ipHash, string $userAgentHash): void
{
    $stmtBusca = $pdo->prepare(
        'SELECT id, pontuacao, respostas_json
         FROM diagnostico_leads
         WHERE ip_hash = :ip_hash
           AND user_agent_hash = :user_agent_hash
           AND created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)
         ORDER BY created_at DESC
         LIMIT 1'
    );
    $stmtBusca->execute([
        ':ip_hash' => $ipHash,
        ':user_agent_hash' => $userAgentHash,
    ]);
    $lead = $stmtBusca->fetch();

    if (!$lead) {
        return;
    }

    $respostas = json_decode((string) ($lead['respostas_json'] ?? ''), true);

    if (!is_array($respostas)) {
        $respostas = [];
    }

    $classificacao = classificar_lead($respostas, (int) $lead['pontuacao'], true);
    $stmt = $pdo->prepare(
        'UPDATE diagnostico_leads
         SET clicou_whatsapp = 1,
             whatsapp_clicked_at = COALESCE(whatsapp_clicked_at, NOW()),
             classificacao = :classificacao,
             updated_at = NOW()
         WHERE id = :id'
    );
    $stmt->execute([
        ':classificacao' => $classificacao,
        ':id' => (int) $lead['id'],
    ]);
}

if (!in_array($eventType, $eventosPermitidos, true)) {
    resposta_json(['sucesso' => false, 'mensagem' => 'Evento invalido.'], 400);
}

if ($page !== '/diagnostico') {
    resposta_json(['sucesso' => false, 'mensagem' => 'Pagina invalida.'], 400);
}

try {
    $dataHoje = date('Y-m-d');
    $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
    $userAgent = substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 500);
    $refererBruto = substr((string) ($entrada['referer'] ?? $_SERVER['HTTP_REFERER'] ?? ''), 0, 500);
    $refererPartes = $refererBruto !== '' ? parse_url($refererBruto) : false;
    $referer = '';

    if (is_array($refererPartes) && !empty($refererPartes['host'])) {
        $referer = strtolower((string) ($refererPartes['scheme'] ?? 'https')) . '://'
            . strtolower((string) $refererPartes['host'])
            . substr((string) ($refererPartes['path'] ?? ''), 0, 180);
    }

    $ipHash = hash('sha256', $ip . '|' . $dataHoje);
    $userAgentHash = hash('sha256', $userAgent . '|' . $dataHoje);

    if ($eventType === 'page_view') {
        $stmtBusca = $pdo->prepare(
            'SELECT id
             FROM diagnostico_eventos
             WHERE event_type = :event_type
               AND page = :page
               AND ip_hash = :ip_hash
               AND user_agent_hash = :user_agent_hash
               AND created_at >= CURDATE()
               AND created_at < DATE_ADD(CURDATE(), INTERVAL 1 DAY)
             LIMIT 1'
        );
        $stmtBusca->execute([
            ':event_type' => $eventType,
            ':page' => $page,
            ':ip_hash' => $ipHash,
            ':user_agent_hash' => $userAgentHash,
        ]);

        if ($stmtBusca->fetchColumn()) {
            resposta_json([
                'sucesso' => true,
                'registrado' => false,
                'mensagem' => 'Visita ja registrada hoje.',
            ]);
        }
    }

    $stmt = $pdo->prepare(
        'INSERT INTO diagnostico_eventos
         (event_type, page, referer, ip_hash, user_agent_hash, created_at)
         VALUES
         (:event_type, :page, :referer, :ip_hash, :user_agent_hash, NOW())'
    );
    $stmt->execute([
        ':event_type' => $eventType,
        ':page' => $page,
        ':referer' => $referer,
        ':ip_hash' => $ipHash,
        ':user_agent_hash' => $userAgentHash,
    ]);

    if ($eventType === 'diagnosis_completed') {
        try {
            salvar_lead_diagnostico($pdo, $entrada, $referer, $ipHash, $userAgentHash);
        } catch (Throwable $erroLead) {
            error_log('[diagnostico_leads] erro ao salvar lead: ' . $erroLead->getMessage());
        }
    }

    if ($eventType === 'whatsapp_click') {
        try {
            marcar_click_whatsapp_lead($pdo, $ipHash, $userAgentHash);
        } catch (Throwable $erroLead) {
            error_log('[diagnostico_leads] erro ao marcar clique no WhatsApp: ' . $erroLead->getMessage());
        }
    }

    resposta_json([
        'sucesso' => true,
        'registrado' => true,
    ]);
} catch (Throwable $erro) {
    error_log('[diagnostico_metricas] erro ao registrar evento: ' . $erro->getMessage());
    resposta_json([
        'sucesso' => false,
        'mensagem' => 'Nao foi possivel registrar a metrica.',
    ], 500);
}
