<?php
require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/../includes/auth.php';

exigir_cliente();

$clienteId = (int) $_SESSION['cliente_id'];

$stmt = $pdo->prepare(
    'SELECT s.*, p.nome AS projeto_nome
     FROM solicitacoes s
     INNER JOIN projetos p ON p.id = s.projeto_id
     WHERE s.cliente_id = :cliente_id
     ORDER BY s.criado_em DESC'
);
$stmt->execute([':cliente_id' => $clienteId]);
$solicitacoes = $stmt->fetchAll();

$ids = array_column($solicitacoes, 'id');
$arquivosPorSolicitacao = [];

if ($ids) {
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmtArquivos = $pdo->prepare("SELECT * FROM arquivos_solicitacao WHERE solicitacao_id IN ($placeholders) ORDER BY criado_em");
    $stmtArquivos->execute($ids);

    foreach ($stmtArquivos->fetchAll() as $arquivo) {
        $arquivosPorSolicitacao[(int) $arquivo['solicitacao_id']][] = $arquivo;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Minhas Solicitações | Canal do Cliente RWDEV</title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
  <header class="app-header">
    <a href="dashboard.php" class="marca">RWDEV Cliente</a>
    <nav>
      <a href="dashboard.php">Dashboard</a>
      <a href="nova-solicitacao.php">Nova solicitação</a>
      <a href="minhas-solicitacoes.php">Solicitações</a>
      <a href="../logout.php">Sair</a>
    </nav>
  </header>

  <main class="app-container">
    <section class="page-title">
      <span>Acompanhamento</span>
      <h1>Minhas solicitações</h1>
    </section>

    <section class="panel">
      <?php foreach ($solicitacoes as $solicitacao): ?>
        <article class="ticket">
          <div class="ticket-head">
            <div>
              <strong>#<?= (int) $solicitacao['id'] ?> - <?= e($solicitacao['projeto_nome']) ?></strong>
              <span><?= date('d/m/Y H:i', strtotime($solicitacao['criado_em'])) ?></span>
            </div>
            <span class="status <?= e(classe_status($solicitacao['status'])) ?>"><?= e($solicitacao['status']) ?></span>
          </div>

          <p><b>Página:</b> <?= e($solicitacao['pagina']) ?></p>
          <p><b>Tipo:</b> <?= e($solicitacao['tipo_alteracao']) ?></p>
          <p><?= nl2br(e($solicitacao['descricao'])) ?></p>

          <?php if (!empty($solicitacao['resposta_admin'])): ?>
            <div class="resposta">
              <b>Resposta RWDEV:</b>
              <p><?= nl2br(e($solicitacao['resposta_admin'])) ?></p>
            </div>
          <?php endif; ?>

          <?php if (!empty($arquivosPorSolicitacao[(int) $solicitacao['id']])): ?>
            <div class="file-list">
              <?php foreach ($arquivosPorSolicitacao[(int) $solicitacao['id']] as $arquivo): ?>
                <a href="../<?= e($arquivo['caminho']) ?>" target="_blank" rel="noopener noreferrer">
                  <?= e($arquivo['nome_original']) ?> (<?= e(resumo_tamanho((int) $arquivo['tamanho'])) ?>)
                </a>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </article>
      <?php endforeach; ?>

      <?php if (!$solicitacoes): ?>
        <p class="empty">Nenhuma solicitação encontrada.</p>
      <?php endif; ?>
    </section>
  </main>
</body>
</html>
