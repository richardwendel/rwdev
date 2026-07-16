<?php
declare(strict_types=1);

// Tela administrativa para revisar depoimentos enviados pelo site.
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../includes/admin_ui.php';

exigir_admin();
exigir_permissao('depoimentos.visualizar');

// Mensagem de retorno exibida depois de aprovar, recusar ou excluir.
$sucesso = $_SESSION['flash_depoimento'] ?? '';
unset($_SESSION['flash_depoimento']);

$colunasDepoimentos = $pdo->query('SHOW COLUMNS FROM depoimentos')->fetchAll(PDO::FETCH_COLUMN);
$temRespostaAdmin = in_array('resposta_admin', $colunasDepoimentos, true);

// Lista todos os depoimentos pendentes, conforme regra de aprovação manual.
$stmt = $pdo->prepare(
    'SELECT *
     FROM depoimentos
     WHERE status = "pendente"
     ORDER BY criado_em DESC'
);
$stmt->execute();
$depoimentos = $stmt->fetchAll();

// Gera URL segura para foto salva no diretório público de uploads.
function foto_depoimento_admin(?string $foto): string
{
    if (!$foto) {
        return '';
    }

    return '../../' . ltrim($foto, '/');
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Depoimentos | Admin RWDEV</title>
  <link rel="stylesheet" href="<?= e(asset_url('assets/css/style.css')) ?>">
</head>
<body>
  <?php admin_render_header(); ?>

  <main class="app-container">
    <section class="page-title">
      <span>Administração</span>
      <h1>Depoimentos pendentes</h1>
      <p>Revise as mensagens enviadas antes de liberar a exibição pública no site.</p>
    </section>

    <?php if ($sucesso): ?><div class="alerta sucesso"><?= e($sucesso) ?></div><?php endif; ?>

    <section class="panel">
      <div class="panel-head">
        <h2>Fila de aprovação</h2>
        <span class="status status-pendente"><?= count($depoimentos) ?> pendente(s)</span>
      </div>

      <?php if (!$depoimentos): ?>
        <p class="empty">Nenhum depoimento pendente no momento.</p>
      <?php endif; ?>

      <?php foreach ($depoimentos as $depoimento): ?>
        <article class="testimonial-review">
          <div class="testimonial-review-photo">
            <?php if ($depoimento['foto']): ?>
              <img src="<?= e(foto_depoimento_admin($depoimento['foto'])) ?>" alt="Foto de <?= e($depoimento['nome']) ?>">
            <?php else: ?>
              <span>Sem foto</span>
            <?php endif; ?>
          </div>

          <div class="testimonial-review-content">
            <div class="testimonial-review-head">
              <div>
                <h3><?= e($depoimento['nome']) ?></h3>
                <p><?= e($depoimento['cidade']) ?></p>
              </div>
              <span class="status status-pendente"><?= e($depoimento['status']) ?></span>
            </div>

            <p><b>Rede social:</b>
              <?php if ($depoimento['rede_social']): ?>
                <a href="<?= e($depoimento['rede_social']) ?>" target="_blank" rel="noopener noreferrer"><?= e($depoimento['rede_social']) ?></a>
              <?php else: ?>
                Não informado
              <?php endif; ?>
            </p>
            <p><b>Tempo que conhece:</b> <?= e($depoimento['tempo_conhece']) ?></p>
            <p><b>Data de envio:</b> <?= date('d/m/Y H:i', strtotime($depoimento['criado_em'])) ?></p>
            <p class="testimonial-review-text"><?= nl2br(e($depoimento['depoimento'])) ?></p>

            <?php if ($temRespostaAdmin): ?>
              <form class="testimonial-response-form" method="post" action="aprovar_depoimento.php">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="id" value="<?= (int) $depoimento['id'] ?>">
                <label for="resposta_admin_<?= (int) $depoimento['id'] ?>">Resposta da RWDEV (opcional)</label>
                <textarea id="resposta_admin_<?= (int) $depoimento['id'] ?>" name="resposta_admin" rows="4" placeholder="Escreva uma resposta curta para aparecer abaixo do depoimento aprovado."></textarea>
                <button type="submit">Aprovar com resposta</button>
              </form>
            <?php endif; ?>

            <div class="testimonial-review-actions">
              <?php if (!$temRespostaAdmin): ?>
                <form method="post" action="aprovar_depoimento.php">
                  <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                  <input type="hidden" name="id" value="<?= (int) $depoimento['id'] ?>">
                  <button type="submit">Aprovar</button>
                </form>
              <?php endif; ?>

              <form method="post" action="recusar_depoimento.php">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="id" value="<?= (int) $depoimento['id'] ?>">
                <button type="submit" class="btn outline">Recusar</button>
              </form>

              <form method="post" action="excluir_depoimento.php" onsubmit="return confirm('Excluir este depoimento definitivamente?');">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="id" value="<?= (int) $depoimento['id'] ?>">
                <button type="submit" class="btn danger">Excluir</button>
              </form>
            </div>
          </div>
        </article>
      <?php endforeach; ?>
    </section>
  </main>
</body>
</html>
