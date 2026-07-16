<?php
require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/../includes/admin_ui.php';

exigir_admin();
exigir_permissao('clientes.visualizar');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigir_permissao('convites_clientes.criar');
    redirect('convites.php');
}

$clientes = $pdo->query('SELECT * FROM clientes ORDER BY criado_em DESC')->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Clientes | Admin RWDEV</title>
  <link rel="stylesheet" href="<?= e(asset_url('assets/css/style.css')) ?>">
</head>
<body>
  <?php admin_render_header(); ?>

  <main class="app-container">
    <section class="page-title">
      <span>Administração</span>
      <h1>Clientes</h1>
    </section>

    <section class="panel">
      <div class="panel-head">
        <div>
          <h2>Cadastro por convite</h2>
          <p class="empty">Novos clientes devem criar acesso somente por link privado de convite.</p>
        </div>
        <a class="btn" href="convites.php">Gerar convite</a>
      </div>
    </section>

    <section class="panel">
      <h2>Clientes cadastrados</h2>
      <div class="table-wrap">
        <table>
          <thead><tr><th>Nome</th><th>E-mail</th><th>Empresa</th><th>Status</th><th>Criado em</th></tr></thead>
          <tbody>
            <?php foreach ($clientes as $cliente): ?>
              <tr>
                <td><?= e($cliente['nome']) ?></td>
                <td><?= e($cliente['email']) ?></td>
                <td><?= e($cliente['empresa']) ?></td>
                <td><?= e($cliente['status']) ?></td>
                <td><?= date('d/m/Y', strtotime($cliente['criado_em'])) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>
  </main>
</body>
</html>
