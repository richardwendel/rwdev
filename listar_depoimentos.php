<?php
declare(strict_types=1);

define('RWDEV_JSON_ENDPOINT', true);

// Endpoint publico que entrega somente depoimentos aprovados.
require_once __DIR__ . '/portal/config/conexao.php';

header('Content-Type: application/json; charset=utf-8');

if (!function_exists('registrar_log_seguranca')) {
    function registrar_log_seguranca(PDO $pdo, string $evento, string $detalhes = ''): void
    {
        try {
            $colunas = $pdo->query('SHOW COLUMNS FROM logs_seguranca')->fetchAll(PDO::FETCH_COLUMN);

            if (!$colunas) {
                return;
            }

            $dados = [];

            foreach (['acao', 'evento', 'tipo'] as $colunaEvento) {
                if (in_array($colunaEvento, $colunas, true)) {
                    $dados[$colunaEvento] = $evento;
                    break;
                }
            }

            foreach (['detalhes', 'descricao', 'mensagem'] as $colunaDetalhes) {
                if (in_array($colunaDetalhes, $colunas, true)) {
                    $dados[$colunaDetalhes] = $detalhes;
                    break;
                }
            }

            foreach (['ip', 'ip_usuario'] as $colunaIp) {
                if (in_array($colunaIp, $colunas, true)) {
                    $dados[$colunaIp] = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
                    break;
                }
            }

            foreach (['criado_em', 'data_criacao', 'created_at'] as $colunaData) {
                if (in_array($colunaData, $colunas, true)) {
                    $dados[$colunaData] = date('Y-m-d H:i:s');
                    break;
                }
            }

            if (!$dados) {
                return;
            }

            $colunasSql = array_keys($dados);
            $campos = implode(', ', $colunasSql);
            $marcadores = ':' . implode(', :', $colunasSql);
            $stmt = $pdo->prepare("INSERT INTO logs_seguranca ({$campos}) VALUES ({$marcadores})");
            $stmt->execute($dados);
        } catch (Throwable $erro) {
            error_log('Falha ao registrar logs_seguranca: ' . $erro->getMessage());
        }
    }
}

try {
    error_log('[depoimentos] inicio do carregamento');

    if (!isset($pdo)) {
        throw new Exception('Variavel $pdo nao encontrada');
    }

    $colunasDepoimentos = $pdo->query('SHOW COLUMNS FROM depoimentos')->fetchAll(PDO::FETCH_COLUMN);
    $temRespostaAdmin = in_array('resposta_admin', $colunasDepoimentos, true);
    $temRespondidoEm = in_array('respondido_em', $colunasDepoimentos, true);
    $camposResposta = $temRespostaAdmin
        ? ', resposta_admin' . ($temRespondidoEm ? ', respondido_em' : '')
        : '';

    // Busca apenas depoimentos aprovados, conforme a regra informada para exibicao publica.
    $sql = 'SELECT id, nome, cidade, rede_social, foto, depoimento, tempo_conhece, criado_em' . $camposResposta . '
            FROM depoimentos
            WHERE status=\'aprovado\'
            ORDER BY criado_em DESC';

    error_log('[depoimentos] consulta executada: ' . preg_replace('/\s+/', ' ', trim($sql)));

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $depoimentos = $stmt->fetchAll();
    $idsDepoimentos = array_map(static fn (array $depoimento): int => (int) $depoimento['id'], $depoimentos);
    $contagensReacoes = [];

    foreach ($idsDepoimentos as $depoimentoId) {
        $contagensReacoes[$depoimentoId] = [
            'like' => 0,
            'love' => 0,
            'haha' => 0,
            'sad' => 0,
        ];
    }

    $stmtTabelaReacoes = $pdo->prepare(
        'SELECT COUNT(*)
         FROM information_schema.tables
         WHERE table_schema = DATABASE()
           AND table_name = :tabela'
    );
    $stmtTabelaReacoes->execute([':tabela' => 'depoimento_reacoes']);
    $tabelaReacoesExiste = (int) $stmtTabelaReacoes->fetchColumn() > 0;

    if ($idsDepoimentos && $tabelaReacoesExiste) {
        $marcadores = implode(', ', array_fill(0, count($idsDepoimentos), '?'));
        $stmtReacoes = $pdo->prepare(
            "SELECT depoimento_id, tipo, COUNT(*) AS total
             FROM depoimento_reacoes
             WHERE depoimento_id IN ({$marcadores})
             GROUP BY depoimento_id, tipo"
        );
        $stmtReacoes->execute($idsDepoimentos);

        foreach ($stmtReacoes->fetchAll() as $reacao) {
            $depoimentoId = (int) $reacao['depoimento_id'];
            $tipo = (string) $reacao['tipo'];

            if (isset($contagensReacoes[$depoimentoId][$tipo])) {
                $contagensReacoes[$depoimentoId][$tipo] = (int) $reacao['total'];
            }
        }
    }

    foreach ($depoimentos as &$depoimento) {
        $depoimentoId = (int) $depoimento['id'];
        $depoimento['resposta_admin'] = $temRespostaAdmin ? trim((string) ($depoimento['resposta_admin'] ?? '')) : '';
        $depoimento['reacoes'] = $contagensReacoes[$depoimentoId] ?? [
            'like' => 0,
            'love' => 0,
            'haha' => 0,
            'sad' => 0,
        ];
    }
    unset($depoimento);

    error_log('[depoimentos] quantidade de registros encontrados: ' . count($depoimentos));
    registrar_log_seguranca($pdo, 'depoimentos_ok', 'Total encontrado: ' . count($depoimentos));

    $dados = [
        'sucesso' => true,
        'depoimentos' => $depoimentos,
    ];

    echo json_encode($dados);
} catch (Throwable $erro) {
    // Evita expor detalhes do banco para visitantes.
    error_log('Erro ao listar depoimentos: ' . $erro->getMessage());
    error_log('[depoimentos] erro SQL: ' . $erro->getMessage());

    if (isset($pdo) && $pdo instanceof PDO) {
        registrar_log_seguranca($pdo, 'depoimentos_erro', $erro->getMessage());
    }

    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Nao foi possivel carregar os depoimentos agora.',
    ]);
}
