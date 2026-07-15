<?php
declare(strict_types=1);

require_once __DIR__ . '/_funcoes.php';

exigir_permissao('ponto.excluir');

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$ponto = ponto_buscar_ponto($pdo, $id);

if (!$ponto) {
    http_response_code(404);
    exit('Registro não encontrado.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validar_csrf();

    $stmt = $pdo->prepare('DELETE FROM pontos_trabalho WHERE id = :id');
    $stmt->execute([':id' => $id]);

    redirect('index.php?mes=' . (int) date('n', strtotime($ponto['data'])) . '&ano=' . (int) date('Y', strtotime($ponto['data'])));
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Excluir ponto | SONI PONTO</title>
  <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
  <?php ponto_render_header('SONI PONTO'); ?>

  <main class="app-container narrow">
    <section class="page-title">
      <span>SONI PONTO</span>
      <h1>Excluir ponto</h1>
    </section>

    <?php ponto_render_nav('index.php'); ?>

    <section class="panel">
      <h2>Confirmar exclusão</h2>
      <p>Você está prestes a excluir o ponto de <?= e(date('d/m/Y', strtotime($ponto['data']))) ?>, <?= e($ponto['dia_semana']) ?>, Loja <?= e((string) $ponto['codigo_loja']) ?>.</p>
      <form class="ponto-actions" method="post">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="id" value="<?= (int) $ponto['id'] ?>">
        <button class="btn danger" type="submit">Excluir definitivamente</button>
        <a class="btn outline" href="index.php">Cancelar</a>
      </form>
    </section>
  </main>
</body>
</html>
