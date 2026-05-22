<?php
require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/../includes/funcoes.php';

if (isset($_SESSION['cliente_id'])) {
    redirect('dashboard.php');
}

$erro = '';
$sucesso = $_SESSION['flash_login'] ?? '';
unset($_SESSION['flash_login']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validar_csrf();

    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    $stmt = $pdo->prepare('SELECT * FROM clientes WHERE email = :email AND status = "ativo" LIMIT 1');
    $stmt->execute([':email' => $email]);
    $cliente = $stmt->fetch();

    if ($cliente && password_verify($senha, $cliente['senha'])) {
        session_regenerate_id(true);
        $_SESSION['cliente_id'] = (int) $cliente['id'];
        $_SESSION['cliente_nome'] = $cliente['nome'];
        redirect('dashboard.php');
    }

    $erro = 'E-mail ou senha invalidos.';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login do Cliente | RWDEV</title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="auth-page">
  <main class="auth-card">
    <img class="auth-logo" src="../../images/logos/meu novo logo.png" alt="Logo RWDEV">

    <div class="auth-brand">
      <strong>RWDEV</strong>
      <span>Portal do Cliente</span>
    </div>

    <h1>Bem-vindo ao Portal do Cliente RWDEV</h1>
    <p>Portal exclusivo para clientes RWDEV. O acesso é feito somente por convite.</p>

    <?php if ($erro): ?>
      <div class="alerta erro"><?= e($erro) ?></div>
    <?php endif; ?>

    <?php if ($sucesso): ?>
      <div class="alerta sucesso"><?= e($sucesso) ?></div>
    <?php endif; ?>

    <form method="post">
      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

      <label for="email">E-mail</label>
      <input id="email" name="email" type="email" required autocomplete="email">

      <label for="senha">Senha</label>
      <input id="senha" name="senha" type="password" required autocomplete="current-password">

      <button type="submit">Entrar</button>
    </form>

    <a class="auth-link" href="../../">Voltar para o site RWDEV</a>
  </main>
</body>
</html>
