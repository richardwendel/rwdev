<?php
declare(strict_types=1);

define('RWDEV_JSON_ENDPOINT', true);

// Endpoint publico para registrar uma reacao por visitante em cada depoimento.
require_once __DIR__ . '/config.php';

const DEPOIMENTO_REACOES_TIPOS = ['like', 'love', 'haha', 'sad'];

function garantir_tabela_reacoes_depoimentos(PDO $pdo): void
{
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS depoimento_reacoes (
          id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          depoimento_id INT UNSIGNED NOT NULL,
          tipo ENUM('like','love','haha','sad') NOT NULL,
          identificador_usuario VARCHAR(120) NOT NULL,
          criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
          atualizado_em DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
          UNIQUE KEY uq_reacao_usuario (depoimento_id, identificador_usuario),
          INDEX idx_depoimento_id (depoimento_id),
          INDEX idx_tipo (tipo)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
}

function contagens_reacoes_depoimento(PDO $pdo, int $depoimentoId): array
{
    $contagens = [
        'like' => 0,
        'love' => 0,
        'haha' => 0,
        'sad' => 0,
    ];

    $stmt = $pdo->prepare(
        'SELECT tipo, COUNT(*) AS total
         FROM depoimento_reacoes
         WHERE depoimento_id = :depoimento_id
         GROUP BY tipo'
    );
    $stmt->execute([':depoimento_id' => $depoimentoId]);

    foreach ($stmt->fetchAll() as $reacao) {
        $tipo = (string) $reacao['tipo'];

        if (isset($contagens[$tipo])) {
            $contagens[$tipo] = (int) $reacao['total'];
        }
    }

    return $contagens;
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        resposta_json([
            'sucesso' => false,
            'mensagem' => 'Metodo nao permitido.',
        ], 405);
    }

    if (!isset($pdo)) {
        throw new RuntimeException('Variavel $pdo nao encontrada.');
    }

    $depoimentoId = filter_input(INPUT_POST, 'depoimento_id', FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1],
    ]);
    $tipo = campo_texto((string) ($_POST['tipo'] ?? ''), 20);
    $identificadorUsuario = campo_texto((string) ($_POST['identificador_usuario'] ?? ''), 120);

    if (!$depoimentoId || !in_array($tipo, DEPOIMENTO_REACOES_TIPOS, true) || $identificadorUsuario === '') {
        throw new RuntimeException('Dados da reacao invalidos.');
    }

    if (!preg_match('/^[a-zA-Z0-9._:-]{8,120}$/', $identificadorUsuario)) {
        throw new RuntimeException('Identificador de usuario invalido.');
    }

    garantir_tabela_reacoes_depoimentos($pdo);

    $stmtDepoimento = $pdo->prepare(
        'SELECT id
         FROM depoimentos
         WHERE id = :id
           AND status = "aprovado"
         LIMIT 1'
    );
    $stmtDepoimento->execute([':id' => $depoimentoId]);

    if (!$stmtDepoimento->fetch()) {
        throw new RuntimeException('Depoimento nao encontrado.');
    }

    $stmt = $pdo->prepare(
        'INSERT INTO depoimento_reacoes (depoimento_id, tipo, identificador_usuario)
         VALUES (:depoimento_id, :tipo, :identificador_usuario)
         ON DUPLICATE KEY UPDATE
           tipo = VALUES(tipo),
           atualizado_em = CURRENT_TIMESTAMP'
    );
    $stmt->execute([
        ':depoimento_id' => $depoimentoId,
        ':tipo' => $tipo,
        ':identificador_usuario' => $identificadorUsuario,
    ]);

    registrar_log_seguranca($pdo, 'depoimento_reacao_ok', 'Depoimento: ' . $depoimentoId . ' Tipo: ' . $tipo);

    resposta_json([
        'sucesso' => true,
        'mensagem' => 'Reacao registrada.',
        'reacoes' => contagens_reacoes_depoimento($pdo, (int) $depoimentoId),
    ]);
} catch (Throwable $erro) {
    error_log('Erro ao salvar reacao de depoimento: ' . $erro->getMessage());

    if (isset($pdo) && $pdo instanceof PDO) {
        registrar_log_seguranca($pdo, 'depoimento_reacao_erro', $erro->getMessage());
    }

    resposta_json([
        'sucesso' => false,
        'mensagem' => $erro instanceof RuntimeException ? $erro->getMessage() : 'Nao foi possivel registrar a reacao agora.',
    ], $erro instanceof RuntimeException ? 400 : 500);
}
