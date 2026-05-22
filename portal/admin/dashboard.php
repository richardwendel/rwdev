<?php
require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/../includes/auth.php';

exigir_admin();

$totais = [
    'clientes' => (int) $pdo->query('SELECT COUNT(*) FROM clientes')->fetchColumn(),
    'projetos' => (int) $pdo->query('SELECT COUNT(*) FROM projetos')->fetchColumn(),
    'solicitacoes' => (int) $pdo->query('SELECT COUNT(*) FROM solicitacoes')->fetchColumn(),
    'recebidas' => (int) $pdo->query('SELECT COUNT(*) FROM solicitacoes WHERE status = "Recebido"')->fetchColumn(),
];

$recentes = $pdo->query(
    'SELECT s.*, c.nome AS cliente_nome, p.nome AS projeto_nome
     FROM solicitacoes s
     INNER JOIN clientes c ON c.id = s.cliente_id
     INNER JOIN projetos p ON p.id = s.projeto_id
     ORDER BY s.criado_em DESC
     LIMIT 8'
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard Admin | RWDEV</title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
  <header class="app-header admin">
    <a href="dashboard.php" class="marca">RWDEV Admin</a>
    <nav>
      <a href="dashboard.php">Dashboard</a>
      <a href="clientes.php">Clientes</a>
      <a href="convites.php">Convites</a>
      <a href="projetos.php">Projetos</a>
      <a href="solicitacoes.php">Solicitações</a>
      <a href="../logout.php">Sair</a>
    </nav>
  </header>

  <main class="app-container">
    <section class="page-title">
      <span>Olá, <?= e($_SESSION['admin_nome']) ?></span>
      <h1>Painel administrativo</h1>
    </section>

    <div class="metrics-grid">
      <article class="metric-card"><span>Clientes</span><strong><?= $totais['clientes'] ?></strong></article>
      <article class="metric-card"><span>Projetos</span><strong><?= $totais['projetos'] ?></strong></article>
      <article class="metric-card"><span>Solicitações</span><strong><?= $totais['solicitacoes'] ?></strong></article>
      <article class="metric-card destaque"><span>Recebidas</span><strong><?= $totais['recebidas'] ?></strong></article>
    </div>

    <section class="panel">
      <div class="panel-head">
        <h2>Solicitações recentes</h2>
        <a href="solicitacoes.php">Ver todas</a>
      </div>

      <?php foreach ($recentes as $solicitacao): ?>
        <a class="list-row clickable" href="solicitacoes.php?id=<?= (int) $solicitacao['id'] ?>">
          <div>
            <strong>#<?= (int) $solicitacao['id'] ?> - <?= e($solicitacao['cliente_nome']) ?></strong>
            <span><?= e($solicitacao['projeto_nome']) ?> - <?= e($solicitacao['tipo_alteracao']) ?></span>
          </div>
          <span class="status <?= e(classe_status($solicitacao['status'])) ?>"><?= e($solicitacao['status']) ?></span>
        </a>
      <?php endforeach; ?>
    </section>
  </main>
</body>
</html>
