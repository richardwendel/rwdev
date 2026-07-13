<?php
require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/../includes/auth.php';

exigir_admin();

$erro = '';
$sucesso = '';
$clientes = $pdo->query('SELECT id, nome, empresa FROM clientes WHERE status = "ativo" ORDER BY nome')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validar_csrf();

    try {
        $clienteId = (int) ($_POST['cliente_id'] ?? 0);
        $nome = trim($_POST['nome'] ?? '');
        $dominio = trim($_POST['dominio'] ?? '');
        $descricao = trim($_POST['descricao'] ?? '');
        $status = $_POST['status'] ?? 'ativo';
        $paginas = trim($_POST['paginas'] ?? '');

        if (!$clienteId || !$nome) {
            throw new RuntimeException('Cliente e nome do projeto são obrigatórios.');
        }

        $pdo->beginTransaction();

        $stmt = $pdo->prepare(
            'INSERT INTO projetos (cliente_id, nome, dominio, descricao, status)
             VALUES (:cliente_id, :nome, :dominio, :descricao, :status)'
        );
        $stmt->execute([
            ':cliente_id' => $clienteId,
            ':nome' => $nome,
            ':dominio' => $dominio,
            ':descricao' => $descricao,
            ':status' => in_array($status, ['ativo', 'inativo'], true) ? $status : 'ativo',
        ]);

        $projetoId = (int) $pdo->lastInsertId();
        $listaPaginas = array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $paginas)));

        if (!$listaPaginas) {
            $listaPaginas = paginas_padrao();
        }

        $stmtPagina = $pdo->prepare('INSERT INTO paginas_projeto (projeto_id, nome_pagina) VALUES (:projeto_id, :nome_pagina)');
        foreach ($listaPaginas as $pagina) {
            $stmtPagina->execute([':projeto_id' => $projetoId, ':nome_pagina' => $pagina]);
        }

        $pdo->commit();
        $sucesso = 'Projeto cadastrado com sucesso.';
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $erro = $e->getMessage();
    }
}

$projetos = $pdo->query(
    'SELECT p.*, c.nome AS cliente_nome
     FROM projetos p
     INNER JOIN clientes c ON c.id = p.cliente_id
     ORDER BY p.criado_em DESC'
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Projetos | Admin RWDEV</title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
  <header class="app-header admin">
    <a href="dashboard.php" class="marca">RWDEV Admin</a>
    <nav>
      <a href="dashboard.php"><span class="admin-menu-item">🏠 Dashboard</span></a>
      <a href="clientes.php"><span class="admin-menu-item">👥 Clientes</span></a>
      <a href="convites.php"><span class="admin-menu-item">✉️ Convites</span></a>
      <a href="projetos.php"><span class="admin-menu-item">📁 Projetos</span></a>
      <a href="solicitacoes.php"><span class="admin-menu-item">📋 Solicitações</span></a>
      <a href="depoimentos.php"><span class="admin-menu-item">💬 Depoimentos</span></a>
      <a href="diagnostico-metricas.php"><span class="admin-menu-item">📊 Diagnóstico</span></a>
      <a href="ponto/index.php"><span class="admin-menu-item">⏱️ Soni Ponto</span></a>
      <a href="documentos-trabalho/index.php"><span class="admin-menu-item">📄 Documentos</span></a>
      <a href="../logout.php"><span class="admin-menu-item">🚪 Sair</span></a>
    </nav>
  </header>

  <main class="app-container">
    <section class="page-title">
      <span>Administração</span>
      <h1>Projetos/sites</h1>
    </section>

    <?php if ($erro): ?><div class="alerta erro"><?= e($erro) ?></div><?php endif; ?>
    <?php if ($sucesso): ?><div class="alerta sucesso"><?= e($sucesso) ?></div><?php endif; ?>

    <form class="panel form-grid two-cols" method="post">
      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

      <label>Cliente
        <select name="cliente_id" required>
          <option value="">Selecione</option>
          <?php foreach ($clientes as $cliente): ?>
            <option value="<?= (int) $cliente['id'] ?>"><?= e($cliente['nome']) ?> <?= $cliente['empresa'] ? '- ' . e($cliente['empresa']) : '' ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>Nome do projeto<input name="nome" required></label>
      <label>Domínio<input name="dominio" placeholder="https://cliente.com.br"></label>
      <label>Status
        <select name="status"><option value="ativo">Ativo</option><option value="inativo">Inativo</option></select>
      </label>
      <label class="full">Descrição<textarea name="descricao" rows="4"></textarea></label>
      <label class="full">Páginas do projeto<textarea name="paginas" rows="5" placeholder="Início&#10;Sobre&#10;Serviços&#10;Contato"></textarea></label>

      <button type="submit">Cadastrar projeto</button>
    </form>

    <section class="panel">
      <h2>Projetos cadastrados</h2>
      <div class="table-wrap">
        <table>
          <thead><tr><th>Projeto</th><th>Cliente</th><th>Domínio</th><th>Status</th></tr></thead>
          <tbody>
            <?php foreach ($projetos as $projeto): ?>
              <tr>
                <td><?= e($projeto['nome']) ?></td>
                <td><?= e($projeto['cliente_nome']) ?></td>
                <td><?= e($projeto['dominio']) ?></td>
                <td><?= e($projeto['status']) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>
  </main>
</body>
</html>
