<?php
declare(strict_types=1);

require_once __DIR__ . '/_funcoes.php';

exigir_permissao('ponto.editar');

$erro = '';
$id = (int) ($_GET['id'] ?? 0);
$ponto = ponto_buscar_ponto($pdo, $id);

if (!$ponto) {
    http_response_code(404);
    exit('Registro não encontrado.');
}

$lojas = ponto_lojas($pdo, false);
$trajetosPorLoja = ponto_trajetos_ativos_por_loja($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validar_csrf();

    try {
        $dados = ponto_dados_post();
        $dados['id'] = $id;

        $stmt = $pdo->prepare(
            'UPDATE pontos_trabalho
             SET data = :data, dia_semana = :dia_semana, status_dia = :status_dia, loja_id = :loja_id,
                 trajeto_ida_id = :trajeto_ida_id, trajeto_volta_id = :trajeto_volta_id, entrada = :entrada,
                 cafe_saida = :cafe_saida, cafe_retorno = :cafe_retorno, almoco_saida = :almoco_saida,
                 almoco_retorno = :almoco_retorno, saida = :saida, transporte_observacao = :transporte_observacao,
                 gasto_transporte = :gasto_transporte, bilhetes_perdidos = :bilhetes_perdidos,
                 valor_bilhetes_perdidos = :valor_bilhetes_perdidos, observacoes = :observacoes
             WHERE id = :id'
        );
        $stmt->execute($dados);
        registrar_auditoria('ponto', 'ponto_editado', 'pontos_trabalho', $id, $ponto, $dados, 'sucesso', null, 'Registro de ponto editado');

        redirect('index.php?mes=' . (int) date('n', strtotime($dados['data'])) . '&ano=' . (int) date('Y', strtotime($dados['data'])));
    } catch (Throwable $e) {
        registrar_auditoria('ponto', 'erro_editar', 'pontos_trabalho', $id, $ponto, $_POST, 'erro', $e->getMessage(), 'Falha ao editar ponto');
        $erro = ponto_mensagem_erro($e);
        $ponto = array_merge($ponto, $_POST);
    }
}

$acao = 'Salvar alterações';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Editar ponto | SONI PONTO</title>
  <link rel="stylesheet" href="<?= e(asset_url('assets/css/style.css')) ?>">
</head>
<body>
  <?php ponto_render_header('SONI PONTO'); ?>

  <main class="app-container">
    <section class="page-title">
      <span>SONI PONTO</span>
      <h1>Editar ponto</h1>
    </section>

    <?php ponto_render_nav('index.php'); ?>
    <?php if ($erro): ?><div class="alerta erro"><?= e($erro) ?></div><?php endif; ?>
    <?php require __DIR__ . '/_form-ponto.php'; ?>
  </main>
  <script src="../../assets/js/ponto.js"></script>
</body>
</html>
