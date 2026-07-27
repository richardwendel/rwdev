<?php
declare(strict_types=1);

require_once __DIR__ . '/_funcoes.php';

exigir_permissao(isset($_GET['duplicar']) ? 'ponto.duplicar' : 'ponto.criar');

$erro = '';
$origemDuplicacao = null;
$lojas = ponto_lojas($pdo);
$trajetosPorLoja = ponto_trajetos_ativos_por_loja($pdo);
$ponto = [
    'data' => date('Y-m-d'),
    'status_dia' => 'trabalhado',
    'transporte_previsto' => 0,
    'transporte_recebido' => 0,
    'gasto_transporte' => 0,
    'bilhetes_perdidos' => 0,
    'valor_bilhetes_perdidos' => 0,
];
$acao = 'Cadastrar ponto';

if (isset($_GET['duplicar'])) {
    $origem = ponto_buscar_ponto($pdo, (int) $_GET['duplicar']);

    if ($origem) {
        $origemDuplicacao = $origem;
        $ponto = $origem;
        unset($ponto['id'], $ponto['criado_em'], $ponto['atualizado_em']);
        $data = new DateTime((string) $origem['data']);
        $data->modify('+1 day');
        $ponto['data'] = $data->format('Y-m-d');
        $ponto['dia_semana'] = ponto_dia_semana($ponto['data']);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigir_permissao('ponto.criar');
    validar_csrf();

    try {
        $dados = ponto_dados_post();
        ponto_exigir_competencia_aberta($pdo, $dados['data']);
        $stmt = $pdo->prepare(
            'INSERT INTO pontos_trabalho
             (data, dia_semana, status_dia, loja_id, trajeto_ida_id, trajeto_volta_id, entrada, cafe_saida, cafe_retorno, almoco_saida, almoco_retorno, saida, transporte_observacao, transporte_previsto, transporte_recebido, gasto_transporte, bilhetes_perdidos, valor_bilhetes_perdidos, observacoes)
             VALUES
             (:data, :dia_semana, :status_dia, :loja_id, :trajeto_ida_id, :trajeto_volta_id, :entrada, :cafe_saida, :cafe_retorno, :almoco_saida, :almoco_retorno, :saida, :transporte_observacao, :transporte_previsto, :transporte_recebido, :gasto_transporte, :bilhetes_perdidos, :valor_bilhetes_perdidos, :observacoes)'
        );
        $stmt->execute($dados);
        $novoId = (int) $pdo->lastInsertId();
        ponto_historico($pdo, 'pontos_trabalho', $novoId, 'criacao', [], $dados);

        registrar_auditoria(
            'ponto',
            $origemDuplicacao ? 'ponto_duplicado' : 'ponto_criado',
            'pontos_trabalho',
            $novoId,
            $origemDuplicacao ?: [],
            $dados,
            'sucesso',
            null,
            $origemDuplicacao ? 'Registro de ponto duplicado' : 'Registro de ponto criado'
        );

        redirect('index.php?mes=' . (int) date('n', strtotime($dados['data'])) . '&ano=' . (int) date('Y', strtotime($dados['data'])));
    } catch (Throwable $e) {
        registrar_auditoria('ponto', $origemDuplicacao ? 'erro_duplicar' : 'erro_criar', 'pontos_trabalho', null, $origemDuplicacao ?: [], $_POST, 'erro', $e->getMessage(), 'Falha ao salvar ponto');
        $erro = ponto_mensagem_erro($e);
        $ponto = array_merge($ponto, $_POST);
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Novo ponto | SONI PONTO</title>
  <link rel="stylesheet" href="<?= e(asset_url('assets/css/style.css')) ?>">
</head>
<body>
  <?php ponto_render_header('SONI PONTO'); ?>

  <main class="app-container">
    <section class="page-title">
      <span>SONI PONTO</span>
      <h1>Novo ponto</h1>
    </section>

    <?php ponto_render_nav('novo.php'); ?>
    <?php if ($erro): ?><div class="alerta erro"><?= e($erro) ?></div><?php endif; ?>
    <?php require __DIR__ . '/_form-ponto.php'; ?>
  </main>
  <script src="../../assets/js/ponto.js"></script>
</body>
</html>
