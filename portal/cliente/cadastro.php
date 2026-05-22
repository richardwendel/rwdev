<?php
require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/../includes/funcoes.php';

if (isset($_SESSION['cliente_id'])) {
    redirect('dashboard.php');
}

$token = trim($_GET['token'] ?? $_POST['token'] ?? '');
$erro = '';
$sucesso = '';
$convite = null;

if ($token !== '' && !preg_match('/^[a-f0-9]{64}$/', $token)) {
    $token = '';
}

if ($token !== '') {
    $stmt = $pdo->prepare(
        "SELECT * FROM convites_cliente
         WHERE token = :token
           AND status = 'pendente'
           AND expira_em > NOW()
         LIMIT 1"
    );
    $stmt->execute([':token' => $token]);
    $convite = $stmt->fetch();
}

if (!$convite) {
    $mensagemInvalida = 'Link de convite inválido ou expirado. Solicite um novo acesso à RWDEV.';
} else {
    $mensagemInvalida = '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $convite) {
    validar_csrf();

    try {
        $senha = $_POST['senha'] ?? '';
        $confirmarSenha = $_POST['confirmar_senha'] ?? '';
        $email = trim($_POST['email'] ?? '');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Informe um e-mail válido.');
        }

        if (strlen($senha) < 8) {
            throw new RuntimeException('A senha deve ter pelo menos 8 caracteres.');
        }

        if ($senha !== $confirmarSenha) {
            throw new RuntimeException('As senhas não conferem.');
        }

        $stmtCliente = $pdo->prepare('SELECT id FROM clientes WHERE email = :email LIMIT 1');
        $stmtCliente->execute([':email' => $email]);

        if ($stmtCliente->fetch()) {
            throw new RuntimeException('Já existe um cadastro com este e-mail.');
        }

        $pdo->beginTransaction();

        $stmtInserir = $pdo->prepare(
            'INSERT INTO clientes (nome, email, senha, empresa, telefone, status)
             VALUES (:nome, :email, :senha, :empresa, :telefone, "ativo")'
        );
        $stmtInserir->execute([
            ':nome' => $convite['nome'],
            ':email' => $email,
            ':senha' => password_hash($senha, PASSWORD_DEFAULT),
            ':empresa' => $convite['empresa'],
            ':telefone' => $convite['telefone'],
        ]);

        $stmtAtualizar = $pdo->prepare(
            "UPDATE convites_cliente
             SET status = 'usado', usado_em = NOW()
             WHERE id = :id AND status = 'pendente'"
        );
        $stmtAtualizar->execute([':id' => $convite['id']]);

        if ($stmtAtualizar->rowCount() !== 1) {
            throw new RuntimeException('Este convite não está mais disponível.');
        }

        $pdo->commit();

        $_SESSION['flash_login'] = 'Cadastro criado com sucesso. Entre com seu e-mail e senha.';
        redirect('login.php');
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $erro = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Criar acesso | Portal do Cliente RWDEV</title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="auth-page">
  <main class="auth-card">
    <img class="auth-logo" src="../../images/logos/meu novo logo.png" alt="Logo RWDEV">

    <div class="auth-brand">
      <strong>RWDEV</strong>
      <span>Convite privado</span>
    </div>

    <?php if ($mensagemInvalida): ?>
      <h1>Convite indisponível</h1>
      <div class="alerta erro"><?= e($mensagemInvalida) ?></div>
      <a class="auth-link" href="../../">Voltar para o site RWDEV</a>
    <?php else: ?>
      <h1>Criar acesso ao Portal do Cliente</h1>
      <p>Confira seus dados, informe seu e-mail e crie uma senha para acessar seu espaço RWDEV.</p>

      <?php if ($erro): ?><div class="alerta erro"><?= e($erro) ?></div><?php endif; ?>

      <div class="invite-summary">
        <p><b>Nome:</b> <?= e($convite['nome']) ?></p>
        <p><b>Empresa:</b> <?= e($convite['empresa']) ?></p>
        <p><b>WhatsApp:</b> <?= e($convite['telefone']) ?></p>
      </div>

      <form method="post">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="token" value="<?= e($token) ?>">

        <label for="email">Seu e-mail</label>
        <input id="email" name="email" type="email" required autocomplete="email">

        <label for="senha">Senha</label>
        <input id="senha" name="senha" type="password" required minlength="8" autocomplete="new-password">

        <label for="confirmar_senha">Confirmar senha</label>
        <input id="confirmar_senha" name="confirmar_senha" type="password" required minlength="8" autocomplete="new-password">

        <button type="submit">Criar meu acesso</button>
      </form>
    <?php endif; ?>
  </main>
</body>
</html>
