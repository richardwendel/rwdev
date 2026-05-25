<?php
declare(strict_types=1);

// Endpoint público que entrega somente depoimentos aprovados.
require_once __DIR__ . '/config.php';

try {
    // Busca apenas depoimentos aprovados e autorizados para exibição pública.
    $stmt = $pdo->prepare(
        'SELECT nome, cidade, rede_social, foto, depoimento, tempo_conhece, criado_em
         FROM depoimentos
         WHERE status = "aprovado" AND autorizacao = 1
         ORDER BY criado_em DESC'
    );
    $stmt->execute();

    resposta_json([
        'sucesso' => true,
        'depoimentos' => $stmt->fetchAll(),
    ]);
} catch (Throwable $erro) {
    // Evita expor detalhes do banco para visitantes.
    error_log('Erro ao listar depoimentos: ' . $erro->getMessage());
    resposta_json([
        'sucesso' => false,
        'mensagem' => 'Não foi possível carregar os depoimentos agora.',
    ], 500);
}
