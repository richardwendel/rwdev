<?php
require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/notificacoes.php';

exigir_cliente();

$clienteId = (int) $_SESSION['cliente_id'];
$erro = '';

if (empty($_SESSION['solicitacao_form_token'])) {
    $_SESSION['solicitacao_form_token'] = bin2hex(random_bytes(32));
}

$stmtProjetos = $pdo->prepare('SELECT * FROM projetos WHERE cliente_id = :cliente_id AND status = "ativo" ORDER BY nome');
$stmtProjetos->execute([':cliente_id' => $clienteId]);
$projetos = $stmtProjetos->fetchAll();
$paginasPorProjeto = [];

if ($projetos) {
    $idsProjetos = array_map(static fn ($projeto) => (int) $projeto['id'], $projetos);
    $placeholders = implode(',', array_fill(0, count($idsProjetos), '?'));
    $stmtPaginas = $pdo->prepare("SELECT projeto_id, nome_pagina FROM paginas_projeto WHERE projeto_id IN ($placeholders) ORDER BY id");
    $stmtPaginas->execute($idsProjetos);

    foreach ($stmtPaginas->fetchAll() as $paginaProjeto) {
        $paginasPorProjeto[(int) $paginaProjeto['projeto_id']][] = $paginaProjeto['nome_pagina'];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validar_csrf();

    try {
        $formToken = $_POST['form_token'] ?? '';

        if (!hash_equals($_SESSION['solicitacao_form_token'] ?? '', $formToken)) {
            throw new RuntimeException('Esta solicitação já foi enviada. Confira em Minhas solicitações.');
        }

        unset($_SESSION['solicitacao_form_token']);

        $projetoId = (int) ($_POST['projeto_id'] ?? 0);
        $pagina = trim($_POST['pagina'] ?? '');
        $paginaSelecionada = $pagina;
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

        $paginasPermitidas = $paginasPorProjeto[$projetoId] ?? paginas_padrao();
        $paginasPermitidas[] = 'Outra';

        if (!in_array($paginaSelecionada, $paginasPermitidas, true)) {
            throw new RuntimeException('Pagina invalida para este projeto.');
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
        $_SESSION['flash_solicitacao'] = 'Solicitação enviada com sucesso.';
        redirect('minhas-solicitacoes.php');
    } catch (Throwable $erroCapturado) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $erro = $erroCapturado->getMessage();

        if (empty($_SESSION['solicitacao_form_token'])) {
            $_SESSION['solicitacao_form_token'] = bin2hex(random_bytes(32));
        }
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

    <form class="panel form-grid" method="post" enctype="multipart/form-data" data-prevent-double-submit>
      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="form_token" value="<?= e($_SESSION['solicitacao_form_token']) ?>">

      <label for="projeto_id">Projeto/site</label>
      <select id="projeto_id" name="projeto_id" required>
        <option value="">Selecione</option>
        <?php foreach ($projetos as $projeto): ?>
          <?php
            $paginasProjeto = $paginasPorProjeto[(int) $projeto['id']] ?? paginas_padrao();
            if (!in_array('Outra', $paginasProjeto, true)) {
                $paginasProjeto[] = 'Outra';
            }
          ?>
          <option value="<?= (int) $projeto['id'] ?>" data-pages="<?= e(json_encode($paginasProjeto, JSON_UNESCAPED_UNICODE)) ?>"><?= e($projeto['nome']) ?></option>
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
