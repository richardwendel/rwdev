<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/auditoria.php';

$token = trim((string) ($_GET['token'] ?? $_POST['token'] ?? ''));
$tokenHash = $token !== '' ? hash('sha256', $token) : '';
$erro = '';
$sucesso = '';
$convite = null;

if ($tokenHash !== '' && admin_tabela_existe($pdo, 'convites_admin')) {
    $stmt = $pdo->prepare(
        'SELECT c.*, a.ativo AS admin_ativo
         FROM convites_admin c
         INNER JOIN admins a ON a.id = c.admin_id
         WHERE c.token_hash = :token_hash
         LIMIT 1'
    );
    $stmt->execute([':token_hash' => $tokenHash]);
    $convite = $stmt->fetch() ?: null;
}

if (!$convite) {
    $erro = 'Convite invalido. Solicite um novo link ao superadministrador.';
} elseif ($convite['status'] === 'usado' || !empty($convite['usado_em'])) {
    $erro = 'Este convite ja foi utilizado.';
} elseif ($convite['status'] === 'revogado') {
    $erro = 'Este convite foi revogado.';
} elseif (strtotime((string) $convite['expira_em']) < time()) {
    $stmt = $pdo->prepare('UPDATE convites_admin SET status = "expirado" WHERE id = :id AND status = "pendente"');
    $stmt->execute([':id' => (int) $convite['id']]);
    $erro = 'Este convite expirou. Solicite um novo link.';
} elseif ($convite['status'] !== 'pendente') {
    $erro = 'Este convite nao esta disponivel.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $convite && $erro === '') {
    validar_csrf();

    $senha = (string) ($_POST['senha'] ?? '');
    $confirmarSenha = (string) ($_POST['confirmar_senha'] ?? '');

    if (strlen($senha) < 8) {
        $erro = 'A senha deve ter pelo menos 8 caracteres.';
    } elseif ($senha !== $confirmarSenha) {
        $erro = 'A confirmacao de senha nao confere.';
    } else {
        try {
            $pdo->beginTransaction();

            $stmtAdmin = $pdo->prepare('UPDATE admins SET senha = :senha, ativo = 1 WHERE id = :id');
            $stmtAdmin->execute([
                ':senha' => password_hash($senha, PASSWORD_DEFAULT),
                ':id' => (int) $convite['admin_id'],
            ]);

            $stmtConvite = $pdo->prepare('UPDATE convites_admin SET status = "usado", usado_em = NOW() WHERE id = :id AND status = "pendente"');
            $stmtConvite->execute([':id' => (int) $convite['id']]);

            if ($stmtConvite->rowCount() !== 1) {
                throw new RuntimeException('Este convite nao esta mais disponivel.');
            }

            $pdo->commit();
            admin_registrar_evento($pdo, 'admin_conta_ativada', (string) $convite['email'], 'Conta administrativa ativada por convite.');
            registrar_auditoria('convites_admin', 'convite_utilizado', 'convites_admin', (int) $convite['id'], ['status' => 'pendente'], ['status' => 'usado', 'admin_id' => (int) $convite['admin_id'], 'email' => (string) $convite['email']], 'sucesso', null, 'Conta administrativa ativada por convite');
            $sucesso = 'Conta ativada. Voce ja pode acessar o painel administrativo.';
            $convite = null;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $erro = 'Nao foi possivel ativar a conta agora.';
            registrar_auditoria('convites_admin', 'erro_ativacao', 'convites_admin', (int) ($convite['id'] ?? 0), [], ['email' => (string) ($convite['email'] ?? '')], 'erro', $e->getMessage(), 'Falha na ativacao de conta administrativa');
            error_log('Falha ativacao admin: ' . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Ativar administrador | RWDEV</title>
  <link rel="stylesheet" href="<?= e(asset_url('assets/css/style.css')) ?>">
</head>
<body class="auth-page">
  <main class="auth-card">
    <img class="auth-logo" src="../../images/logos/meu novo logo.png" alt="Logo RWDEV">
    <div class="auth-brand">
      <strong>RWDEV</strong>
      <span>Admin</span>
    </div>

    <h1>Ativar acesso administrativo</h1>

    <?php if ($erro): ?><div class="alerta erro"><?= e($erro) ?></div><?php endif; ?>
    <?php if ($sucesso): ?>
      <div class="alerta sucesso"><?= e($sucesso) ?></div>
      <a class="auth-link" href="login.php">Ir para o login</a>
    <?php endif; ?>

    <?php if ($convite && !$sucesso): ?>
      <p><b>Nome:</b> <?= e((string) $convite['nome']) ?></p>
      <p><b>E-mail:</b> <?= e((string) $convite['email']) ?></p>
      <form method="post">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="token" value="<?= e($token) ?>">
        <label>Senha<input name="senha" type="password" minlength="8" required autocomplete="new-password"></label>
        <label>Confirmar senha<input name="confirmar_senha" type="password" minlength="8" required autocomplete="new-password"></label>
        <button type="submit">Ativar conta</button>
      </form>
    <?php endif; ?>
  </main>
</body>
</html>
