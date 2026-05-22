<?php
require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/../includes/auth.php';

exigir_cliente();

$clienteId = (int) $_SESSION['cliente_id'];

$stmtProjetos = $pdo->prepare('SELECT * FROM projetos WHERE cliente_id = :cliente_id ORDER BY criado_em DESC');
$stmtProjetos->execute([':cliente_id' => $clienteId]);
$projetos = $stmtProjetos->fetchAll();

$stmtSolicitacoes = $pdo->prepare(
    'SELECT s.*, p.nome AS projeto_nome
     FROM solicitacoes s
     INNER JOIN projetos p ON p.id = s.projeto_id
     WHERE s.cliente_id = :cliente_id
     ORDER BY s.criado_em DESC
     LIMIT 5'
);
$stmtSolicitacoes->execute([':cliente_id' => $clienteId]);
$solicitacoes = $stmtSolicitacoes->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard | Canal do Cliente RWDEV</title>
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

  <main class="app-shell">
    <aside class="sidebar">
      <strong>Portal RWDEV</strong>
      <span><?= e($_SESSION['cliente_nome']) ?></span>
      <nav>
        <a class="ativo" href="dashboard.php">Dashboard</a>
        <a href="nova-solicitacao.php">Nova solicitação</a>
        <a href="minhas-solicitacoes.php">Minhas solicitações</a>
        <a href="../logout.php">Sair</a>
      </nav>
    </aside>

    <section class="app-content">
      <section class="page-title">
        <span>Olá, <?= e($_SESSION['cliente_nome']) ?></span>
        <h1>Seus projetos RWDEV</h1>
      </section>

      <div class="metrics-grid">
        <article class="metric-card">
          <span>Projetos</span>
          <strong><?= count($projetos) ?></strong>
        </article>
        <article class="metric-card">
          <span>Solicitações recentes</span>
          <strong><?= count($solicitacoes) ?></strong>
        </article>
      </div>

      <section class="panel">
        <div class="panel-head">
          <h2>Projetos/sites</h2>
          <a class="btn" href="nova-solicitacao.php">Solicitar alteração</a>
        </div>

        <div class="cards-grid">
          <?php foreach ($projetos as $projeto): ?>
            <article class="project-card">
              <h3><?= e($projeto['nome']) ?></h3>
              <p><?= e($projeto['descricao']) ?></p>
              <?php if ($projeto['dominio']): ?>
                <a href="<?= e($projeto['dominio']) ?>" target="_blank" rel="noopener noreferrer"><?= e($projeto['dominio']) ?></a>
              <?php endif; ?>
            </article>
          <?php endforeach; ?>

          <?php if (!$projetos): ?>
            <p class="empty">Nenhum projeto cadastrado ainda.</p>
          <?php endif; ?>
        </div>
      </section>

      <section class="panel">
        <div class="panel-head">
          <h2>Últimas solicitações</h2>
          <a href="minhas-solicitacoes.php">Ver todas</a>
        </div>

        <?php foreach ($solicitacoes as $solicitacao): ?>
          <div class="list-row">
            <div>
              <strong>#<?= (int) $solicitacao['id'] ?> - <?= e($solicitacao['projeto_nome']) ?></strong>
              <span><?= e($solicitacao['tipo_alteracao']) ?> em <?= e($solicitacao['pagina']) ?></span>
            </div>
            <span class="status <?= e(classe_status($solicitacao['status'])) ?>"><?= e($solicitacao['status']) ?></span>
          </div>
        <?php endforeach; ?>

        <?php if (!$solicitacoes): ?>
          <p class="empty">Você ainda não enviou solicitações.</p>
        <?php endif; ?>
      </section>
    </section>
  </main>
</body>
</html>
