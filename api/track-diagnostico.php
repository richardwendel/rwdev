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
