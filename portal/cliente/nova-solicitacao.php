<?php
require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/notificacoes.php';

exigir_cliente();

$clienteId = (int) $_SESSION['cliente_id'];
$erro = '';
$sucesso = '';

$stmtProjetos = $pdo->prepare('SELECT * FROM projetos WHERE cliente_id = :cliente_id AND status = "ativo" ORDER BY nome');
$stmtProjetos->execute([':cliente_id' => $clienteId]);
$projetos = $stmtProjetos->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validar_csrf();

    try {
        $projetoId = (int) ($_POST['projeto_id'] ?? 0);
        $pagina = trim($_POST['pagina'] ?? '');
        $outraPagina = trim($_POST['outra_pagina'] ?? '');
        $tipo = trim($_POST['tipo_alteracao'] ?? '');
        $descricao = trim($_POST['descricao'] ?? '');

        if ($pagina === 'Outra') {
            $pagina = $outraPagina;
        }

        if (!$projetoId || !$pagina || !$tipo || !$descricao) {
            throw new RuntimeException('Preencha todos os campos obrigatorios.');
        }

        $stmtProjeto = $pdo->prepare('SELECT * FROM projetos WHERE id = :id AND cliente_id = :cliente_id AND status = "ativo" LIMIT 1');
        $stmtProjeto->execute([':id' => $projetoId, ':cliente_id' => $clienteId]);
        $projeto = $stmtProjeto->fetch();

        if (!$projeto) {
            throw new RuntimeException('Projeto invalido.');
        }

        $pdo->beginTransaction();

        $stmt = $pdo->prepare(
            'INSERT INTO solicitacoes (cliente_id, projeto_id, pagina, tipo_alteracao, descricao)
             VALUES (:cliente_id, :projeto_id, :pagina, :tipo_alteracao, :descricao)'
        );
        $stmt->execute([
            ':cliente_id' => $clienteId,
            ':projeto_id' => $projetoId,
            ':pagina' => $pagina,
            ':tipo_alteracao' => $tipo,
            ':descricao' => $descricao,
        ]);

        $solicitacaoId = (int) $pdo->lastInsertId();

        if (!empty($_FILES['arquivos'])) {
            validar_e_salvar_uploads($_FILES['arquivos'], $clienteId, $solicitacaoId, $pdo);
        }

        $stmtCliente = $pdo->prepare('SELECT * FROM clientes WHERE id = :id LIMIT 1');
        $stmtCliente->execute([':id' => $clienteId]);
        $cliente = $stmtCliente->fetch() ?: [];

        $pdo->commit();

        avisar_admin_nova_solicitacao($cliente, $projeto, $solicitacaoId);
        $sucesso = 'Solicitação enviada com sucesso.';
    } catch (Throwable $erroCapturado) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $erro = $erroCapturado->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Nova Solicitação | Canal do Cliente RWDEV</title>
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

  <main class="app-container narrow">
    <section class="page-title">
      <span>Solicitações</span>
      <h1>Nova solicitação de alteração</h1>
    </section>

    <?php if ($erro): ?><div class="alerta erro"><?= e($erro) ?></div><?php endif; ?>
    <?php if ($sucesso): ?><div class="alerta sucesso"><?= e($sucesso) ?></div><?php endif; ?>

    <form class="panel form-grid" method="post" enctype="multipart/form-data">
      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

      <label for="projeto_id">Projeto/site</label>
      <select id="projeto_id" name="projeto_id" required>
        <option value="">Selecione</option>
        <?php foreach ($projetos as $projeto): ?>
          <option value="<?= (int) $projeto['id'] ?>"><?= e($projeto['nome']) ?></option>
        <?php endforeach; ?>
      </select>

      <label for="pagina">Página do site</label>
      <select id="pagina" name="pagina" required data-toggle-outra="#outraPaginaWrap">
        <?php foreach (paginas_padrao() as $pagina): ?>
          <option><?= e($pagina) ?></option>
        <?php endforeach; ?>
      </select>

      <div id="outraPaginaWrap" class="hidden">
        <label for="outra_pagina">Informe a página</label>
        <input id="outra_pagina" name="outra_pagina" type="text" maxlength="120">
      </div>

      <label for="tipo_alteracao">Tipo de alteração</label>
      <select id="tipo_alteracao" name="tipo_alteracao" required>
        <?php foreach (tipos_alteracao() as $tipo): ?>
          <option><?= e($tipo) ?></option>
        <?php endforeach; ?>
      </select>

      <label for="descricao">Descrição detalhada</label>
      <textarea id="descricao" name="descricao" rows="7" required placeholder="Explique o que precisa ser alterado, onde aparece e qual resultado espera."></textarea>

      <label for="arquivos">Arquivos de apoio</label>
      <input id="arquivos" name="arquivos[]" type="file" multiple accept=".jpg,.jpeg,.png,.pdf,.docx">
      <p class="hint">Aceita JPG, JPEG, PNG, PDF e DOCX. Máximo de 5 arquivos, 5MB cada.</p>

      <button type="submit">Enviar solicitação</button>
    </form>
  </main>

  <script src="../assets/js/main.js"></script>
</body>
</html>
