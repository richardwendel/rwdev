<?php
declare(strict_types=1);

define('RWDEV_JSON_ENDPOINT', true);

// Endpoint publico que entrega somente depoimentos aprovados.
require_once __DIR__ . '/config.php';

try {
    error_log('[depoimentos] inicio do carregamento');

    // Busca apenas depoimentos aprovados, conforme a regra informada para exibicao publica.
    $sql = 'SELECT nome, cidade, rede_social, foto, depoimento, tempo_conhece, criado_em
            FROM depoimentos
            WHERE status = "aprovado"
            ORDER BY criado_em DESC';

    error_log('[depoimentos] consulta executada: ' . preg_replace('/\s+/', ' ', trim($sql)));

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $depoimentos = $stmt->fetchAll();

    error_log('[depoimentos] quantidade de registros encontrados: ' . count($depoimentos));
    registrar_log_seguranca($pdo, 'depoimentos_ok', 'Total encontrado: ' . count($depoimentos));

    resposta_json([
        'sucesso' => true,
        'depoimentos' => $depoimentos,
    ]);
} catch (Throwable $erro) {
    // Evita expor detalhes do banco para visitantes.
    error_log('Erro ao listar depoimentos: ' . $erro->getMessage());
    error_log('[depoimentos] erro SQL: ' . $erro->getMessage());

    if (isset($pdo) && $pdo instanceof PDO) {
        registrar_log_seguranca($pdo, 'depoimentos_erro', $erro->getMessage());
    }

    resposta_json([
        'sucesso' => false,
        'mensagem' => 'Nao foi possivel carregar os depoimentos agora.',
    ], 500);
}
