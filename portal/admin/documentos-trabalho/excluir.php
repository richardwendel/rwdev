<?php
declare(strict_types=1);

require_once __DIR__ . '/_funcoes.php';

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$documento = docs_buscar($pdo, $id);

if (!$documento) {
    http_response_code(404);
    exit('Documento não encontrado.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validar_csrf();

    $stmt = $pdo->prepare('DELETE FROM documentos_trabalho WHERE id = :id');
    $stmt->execute([':id' => $id]);

    $arquivo = docs_upload_dir() . '/' . basename((string) $documento['arquivo']);
    if (is_file($arquivo)) {
        unlink($arquivo);
    }

    redirect('index.php');
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Excluir documento | RWDEV Admin</title>
  <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
  <?php docs_render_header(); ?>

  <main class="app-container narrow">
    <section class="page-title">
      <span>Documentos do Trabalho</span>
      <h1>Excluir documento</h1>
    </section>

    <?php docs_render_nav('index.php'); ?>

    <section class="panel">
      <h2>Confirmar exclusão</h2>
      <p>Excluir o documento <b><?= e((string) $documento['titulo']) ?></b> e o arquivo enviado?</p>
      <form class="ponto-actions" method="post">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="id" value="<?= (int) $documento['id'] ?>">
        <button class="btn danger" type="submit">Excluir definitivamente</button>
        <a class="btn outline" href="visualizar.php?id=<?= (int) $documento['id'] ?>">Cancelar</a>
      </form>
    </section>
  </main>
</body>
</html>
