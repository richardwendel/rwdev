<?php
declare(strict_types=1);

define('RWDEV_JSON_ENDPOINT', true);

require_once __DIR__ . '/../portal/config/conexao.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

function contar_pendencias(PDO $pdo, string $sql): int
{
    $stmt = $pdo->prepare($sql);
    $stmt->execute();

    return (int) $stmt->fetchColumn();
}

try {
    $total = 0;
    $total += contar_pendencias($pdo, "SELECT COUNT(*) FROM diagnostico_leads WHERE status = 'Novo Lead'");
    $total += contar_pendencias($pdo, "SELECT COUNT(*) FROM solicitacoes WHERE status <> 'Concluído'");
    $total += contar_pendencias($pdo, "SELECT COUNT(*) FROM depoimentos WHERE status = 'pendente'");

    echo json_encode(['total' => $total], JSON_UNESCAPED_UNICODE);
} catch (Throwable $erro) {
    error_log('[notificacoes-admin] erro ao contar pendencias: ' . $erro->getMessage());
    echo json_encode(['total' => 0], JSON_UNESCAPED_UNICODE);
}
