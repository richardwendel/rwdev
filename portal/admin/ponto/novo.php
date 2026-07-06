<?php
declare(strict_types=1);

require_once __DIR__ . '/_funcoes.php';

$erro = '';
$lojas = ponto_lojas($pdo);
$ponto = [
    'data' => date('Y-m-d'),
    'gasto_transporte' => 0,
    'bilhetes_perdidos' => 0,
    'valor_bilhetes_perdidos' => 0,
];
$acao = 'Cadastrar ponto';

if (isset($_GET['duplicar'])) {
    $origem = ponto_buscar_ponto($pdo, (int) $_GET['duplicar']);

    if ($origem) {
        $ponto = $origem;
        unset($ponto['id'], $ponto['criado_em'], $ponto['atualizado_em']);
        $data = new DateTime((string) $origem['data']);
        $data->modify('+1 day');
        $ponto['data'] = $data->format('Y-m-d');
        $ponto['dia_semana'] = ponto_dia_semana($ponto['data']);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validar_csrf();

    try {
        $dados = ponto_dados_post();
        $stmt = $pdo->prepare(
            'INSERT INTO pontos_trabalho
             (data, dia_semana, loja_id, entrada, cafe_saida, cafe_retorno, almoco_saida, almoco_retorno, saida, transporte_observacao, gasto_transporte, bilhetes_perdidos, valor_bilhetes_perdidos, observacoes)
             VALUES
             (:data, :dia_semana, :loja_id, :entrada, :cafe_saida, :cafe_retorno, :almoco_saida, :almoco_retorno, :saida, :transporte_observacao, :gasto_transporte, :bilhetes_perdidos, :valor_bilhetes_perdidos, :observacoes)'
        );
        $stmt->execute($dados);

        redirect('index.php?mes=' . (int) date('n', strtotime($dados['data'])) . '&ano=' . (int) date('Y', strtotime($dados['data'])));
    } catch (Throwable $e) {
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
  <link rel="stylesheet" href="../../assets/css/style.css">
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
