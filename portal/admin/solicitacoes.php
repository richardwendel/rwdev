<?php
require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/../includes/auth.php';

exigir_admin();

$erro = '';
$sucesso = '';
$idSelecionado = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validar_csrf();

    $solicitacaoId = (int) ($_POST['solicitacao_id'] ?? 0);
    $status = $_POST['status'] ?? 'Recebido';
    $resposta = trim($_POST['resposta_admin'] ?? '');

    if (!in_array($status, status_solicitacao(), true)) {
        $status = 'Recebido';
    }

    $stmt = $pdo->prepare(
        'UPDATE solicitacoes
         SET status = :status, resposta_admin = :resposta_admin
         WHERE id = :id'
    );
    $stmt->execute([
        ':status' => $status,
        ':resposta_admin' => $resposta,
        ':id' => $solicitacaoId,
    ]);

    $sucesso = 'Solicitação atualizada.';
    $idSelecionado = $solicitacaoId;
}

$solicitacoes = $pdo->query(
    'SELECT s.*, c.nome AS cliente_nome, c.empresa, p.nome AS projeto_nome
     FROM solicitacoes s
     INNER JOIN clientes c ON c.id = s.cliente_id
     INNER JOIN projetos p ON p.id = s.projeto_id
     ORDER BY s.criado_em DESC'
)->fetchAll();

$selecionada = null;
$arquivos = [];

if ($idSelecionado) {
    $stmtSelecionada = $pdo->prepare(
        'SELECT s.*, c.nome AS cliente_nome, c.email, c.empresa, p.nome AS projeto_nome
         FROM solicitacoes s
         INNER JOIN clientes c ON c.id = s.cliente_id
         INNER JOIN projetos p ON p.id = s.projeto_id
         WHERE s.id = :id
         LIMIT 1'
    );
    $stmtSelecionada->execute([':id' => $idSelecionado]);
    $selecionada = $stmtSelecionada->fetch();

    if ($selecionada) {
        $stmtArquivos = $pdo->prepare('SELECT * FROM arquivos_solicitacao WHERE solicitacao_id = :id ORDER BY criado_em');
        $stmtArquivos->execute([':id' => $idSelecionado]);
        $arquivos = $stmtArquivos->fetchAll();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Solicitações | Admin RWDEV</title>
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
      <a href="depoimentos.php">Depoimentos</a>
      <a href="diagnostico-metricas.php">&#128202; Diagnóstico</a>
      <a href="../logout.php">Sair</a>
    </nav>
  </header>

  <main class="app-container split">
    <section>
      <div class="page-title">
        <span>Administração</span>
        <h1>Solicitações recebidas</h1>
      </div>

      <?php if ($sucesso): ?><div class="alerta sucesso"><?= e($sucesso) ?></div><?php endif; ?>
      <?php if ($erro): ?><div class="alerta erro"><?= e($erro) ?></div><?php endif; ?>

      <div class="panel">
        <?php foreach ($solicitacoes as $solicitacao): ?>
          <a class="list-row clickable" href="solicitacoes.php?id=<?= (int) $solicitacao['id'] ?>">
            <div>
              <strong>#<?= (int) $solicitacao['id'] ?> - <?= e($solicitacao['cliente_nome']) ?></strong>
              <span><?= e($solicitacao['projeto_nome']) ?> - <?= e($solicitacao['pagina']) ?></span>
            </div>
            <span class="status <?= e(classe_status($solicitacao['status'])) ?>"><?= e($solicitacao['status']) ?></span>
          </a>
        <?php endforeach; ?>
      </div>
    </section>

    <aside class="panel detail-panel">
      <?php if ($selecionada): ?>
        <h2>Solicitação #<?= (int) $selecionada['id'] ?></h2>
        <p><b>Cliente:</b> <?= e($selecionada['cliente_nome']) ?> <?= $selecionada['empresa'] ? '(' . e($selecionada['empresa']) . ')' : '' ?></p>
        <p><b>E-mail:</b> <?= e($selecionada['email']) ?></p>
        <p><b>Projeto:</b> <?= e($selecionada['projeto_nome']) ?></p>
        <p><b>Página:</b> <?= e($selecionada['pagina']) ?></p>
        <p><b>Tipo:</b> <?= e($selecionada['tipo_alteracao']) ?></p>
        <p><?= nl2br(e($selecionada['descricao'])) ?></p>

        <?php if ($arquivos): ?>
          <h3>Arquivos enviados</h3>
          <div class="file-list">
            <?php foreach ($arquivos as $arquivo): ?>
              <a href="../<?= e($arquivo['caminho']) ?>" target="_blank" rel="noopener noreferrer">
                <?= e($arquivo['nome_original']) ?> (<?= e(resumo_tamanho((int) $arquivo['tamanho'])) ?>)
              </a>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <form method="post" class="form-grid">
          <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="solicitacao_id" value="<?= (int) $selecionada['id'] ?>">

          <label>Status
            <select name="status">
              <?php foreach (status_solicitacao() as $status): ?>
                <option <?= $selecionada['status'] === $status ? 'selected' : '' ?>><?= e($status) ?></option>
              <?php endforeach; ?>
            </select>
          </label>

          <label>Resposta para o cliente
            <textarea name="resposta_admin" rows="6"><?= e($selecionada['resposta_admin']) ?></textarea>
          </label>

          <button type="submit">Atualizar solicitação</button>
        </form>
      <?php else: ?>
        <h2>Selecione uma solicitação</h2>
        <p class="empty">Clique em uma solicitação para ver detalhes, arquivos e alterar status.</p>
      <?php endif; ?>
    </aside>
  </main>
</body>
</html>
