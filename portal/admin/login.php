<?php
require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/auditoria.php';

if (isset($_SESSION['admin_id'])) {
    redirect('dashboard.php');
}

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validar_csrf();

    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    $stmt = $pdo->prepare('SELECT * FROM admins WHERE email = :email LIMIT 1');
    $stmt->execute([':email' => $email]);
    $admin = $stmt->fetch();

    $ativo = $admin && admin_coluna_existe($pdo, 'ativo') ? (int) $admin['ativo'] : 1;

    if ($admin && $ativo === 1 && password_verify($senha, $admin['senha'])) {
        session_regenerate_id(true);
        unset($_SESSION['cliente_id'], $_SESSION['cliente_nome']);
        $_SESSION['admin_id'] = (int) $admin['id'];
        $_SESSION['admin_nome'] = $admin['nome'];
        $_SESSION['admin_ultimo_acesso_anterior'] = $admin['ultimo_acesso'] ?? null;
        admin_atual(true);

        if (admin_coluna_existe($pdo, 'ultimo_acesso')) {
            $stmtAcesso = $pdo->prepare('UPDATE admins SET ultimo_acesso = NOW() WHERE id = :id');
            $stmtAcesso->execute([':id' => (int) $admin['id']]);
        }

        admin_registrar_evento($pdo, 'login_sucesso', $email, 'Login administrativo realizado.');
        registrar_auditoria('autenticacao', 'login_sucesso', 'admins', (int) $admin['id'], [], ['email' => $email], 'sucesso', null, 'Login administrativo realizado');
        redirect('dashboard.php');
    }

    admin_registrar_evento($pdo, $admin && $ativo !== 1 ? 'login_bloqueado' : 'login_falha', $email, 'Login administrativo recusado.');
    registrar_auditoria('autenticacao', $admin && $ativo !== 1 ? 'conta_inativa' : 'login_falha', 'admins', $admin ? (int) $admin['id'] : null, [], ['email' => $email], $admin && $ativo !== 1 ? 'negado' : 'erro', 'Login administrativo recusado', 'Tentativa de login administrativo');
    $erro = 'E-mail ou senha invalidos.';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin | Canal RWDEV</title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="auth-page">
  <main class="auth-card">
    <img class="auth-logo" src="../../images/logos/meu novo logo.png" alt="Logo RWDEV">

    <div class="auth-brand">
      <strong>RWDEV</strong>
      <span>Admin</span>
    </div>

    <h1>Acesso administrativo</h1>
    <p>Gerencie clientes, projetos e solicitações.</p>

    <?php if ($erro): ?>
      <div class="alerta erro"><?= e($erro) ?></div>
    <?php endif; ?>

    <form method="post">
      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

      <label for="email">E-mail</label>
      <input id="email" name="email" type="email" required autocomplete="email">

      <label for="senha">Senha</label>
      <input id="senha" name="senha" type="password" required autocomplete="current-password">

      <button type="submit">Entrar</button>
    </form>

    <a class="auth-link" href="../../index.html">← Voltar para o site</a>
  </main>
</body>
</html>
