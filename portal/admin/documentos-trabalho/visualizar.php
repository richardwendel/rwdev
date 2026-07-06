<?php
declare(strict_types=1);

require_once __DIR__ . '/_funcoes.php';

$id = (int) ($_GET['id'] ?? 0);
$documento = docs_buscar($pdo, $id);

if (!$documento) {
    http_response_code(404);
    exit('Documento não encontrado.');
}

if (($_GET['arquivo'] ?? '') === '1') {
    $arquivo = docs_upload_dir() . '/' . basename((string) $documento['arquivo']);

    if (!is_file($arquivo)) {
        http_response_code(404);
        exit('Arquivo não encontrado.');
    }

    $extensao = docs_extensao((string) $documento['arquivo']);
    $mime = [
        'pdf' => 'application/pdf',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
    ][$extensao] ?? 'application/octet-stream';

    header('Content-Type: ' . $mime);
    header('Content-Disposition: inline; filename="' . basename((string) $documento['arquivo']) . '"');
    header('Content-Length: ' . filesize($arquivo));
    readfile($arquivo);
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Visualizar documento | RWDEV Admin</title>
  <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
  <?php docs_render_header(); ?>

  <main class="app-container split">
    <section>
      <div class="page-title">
        <span>Documentos do Trabalho</span>
        <h1><?= e((string) $documento['titulo']) ?></h1>
      </div>

      <?php docs_render_nav('index.php'); ?>

      <section class="panel">
        <div class="panel-head">
          <h2>Detalhes</h2>
          <div class="ponto-actions">
            <a class="btn" href="visualizar.php?id=<?= (int) $documento['id'] ?>&arquivo=1" target="_blank" rel="noopener noreferrer">Abrir arquivo</a>
            <a class="btn outline" href="editar.php?id=<?= (int) $documento['id'] ?>">Editar</a>
          </div>
        </div>
        <p><b>Categoria:</b> <?= e((string) $documento['categoria']) ?></p>
        <p><b>Empresa:</b> <?= e((string) $documento['empresa']) ?: '-' ?></p>
        <p><b>Cargo:</b> <?= e((string) $documento['cargo']) ?: '-' ?></p>
        <p><b>Data do documento:</b> <?= $documento['data_documento'] ? date('d/m/Y', strtotime($documento['data_documento'])) : '-' ?></p>
        <p><b>Validade:</b> <?= $documento['data_validade'] ? date('d/m/Y', strtotime($documento['data_validade'])) : '-' ?></p>
        <p><b>Arquivo:</b> <?= e((string) $documento['arquivo']) ?></p>
        <p><b>Vínculo SONI PONTO:</b>
          <?= $documento['ponto_data'] ? date('d/m/Y', strtotime($documento['ponto_data'])) . ' - ' . e((string) $documento['ponto_dia']) . ' - Loja ' . e((string) $documento['codigo_loja']) : '-' ?>
        </p>
        <p><b>Status:</b> <?= (int) $documento['ativo'] === 1 ? 'ativo' : 'inativo' ?></p>
        <p><?= nl2br(e((string) $documento['observacoes'])) ?></p>
      </section>
    </section>

    <aside class="panel detail-panel">
      <h2>Prévia protegida</h2>
      <?php if (in_array(docs_extensao((string) $documento['arquivo']), ['jpg', 'jpeg', 'png'], true)): ?>
        <img class="docs-preview" src="visualizar.php?id=<?= (int) $documento['id'] ?>&arquivo=1" alt="Prévia do documento">
      <?php elseif (docs_extensao((string) $documento['arquivo']) === 'pdf'): ?>
        <iframe class="docs-frame" src="visualizar.php?id=<?= (int) $documento['id'] ?>&arquivo=1" title="Prévia do PDF"></iframe>
      <?php else: ?>
        <p class="empty">Prévia indisponível.</p>
      <?php endif; ?>
    </aside>
  </main>
</body>
</html>
