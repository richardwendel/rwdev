<?php
require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/../includes/auth.php';

exigir_admin();

$erro = '';
$sucesso = '';

$pdo->exec("UPDATE convites_cliente SET status = 'expirado' WHERE status = 'pendente' AND expira_em < NOW()");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validar_csrf();

    try {
        $nome = trim($_POST['nome'] ?? '');
        $empresa = trim($_POST['empresa'] ?? '');
        $telefone = trim($_POST['telefone'] ?? '');

        if (!$nome || !$telefone) {
            throw new RuntimeException('Nome e WhatsApp são obrigatórios.');
        }

        if (strlen(preg_replace('/\D+/', '', $telefone)) < 10) {
            throw new RuntimeException('Informe um WhatsApp válido com DDD.');
        }

        $token = bin2hex(random_bytes(32));
        $expiraEm = (new DateTimeImmutable('+48 hours'))->format('Y-m-d H:i:s');

        $stmt = $pdo->prepare(
            'INSERT INTO convites_cliente (token, nome, empresa, email, telefone, expira_em)
             VALUES (:token, :nome, :empresa, NULL, :telefone, :expira_em)'
        );
        $stmt->execute([
            ':token' => $token,
            ':nome' => $nome,
            ':empresa' => $empresa,
            ':telefone' => $telefone,
            ':expira_em' => $expiraEm,
        ]);

        $sucesso = 'Convite criado com sucesso.';
    } catch (Throwable $e) {
        $erro = $e->getMessage();
    }
}

$convites = $pdo->query('SELECT * FROM convites_cliente ORDER BY criado_em DESC')->fetchAll();

function link_convite(string $token): string
{
    return 'https://rwdev.com.br/portal/cliente/cadastro.php?token=' . $token;
}

function link_whatsapp(?string $telefone, string $link): string
{
    $mensagem = "Olá, tudo bem? Aqui está seu link exclusivo para criar acesso ao Portal do Cliente RWDEV:\n"
        . $link . "\n\n"
        . "Esse link é individual e expira em 48 horas.";
    $numero = preg_replace('/\D+/', '', (string) $telefone);

    if ($numero && !str_starts_with($numero, '55')) {
        $numero = '55' . $numero;
    }

    return 'https://wa.me/' . $numero . '?text=' . rawurlencode($mensagem);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Convites | Admin RWDEV</title>
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
      <span>Administração</span>
      <h1>Convites de acesso</h1>
    </section>

    <?php if ($erro): ?><div class="alerta erro"><?= e($erro) ?></div><?php endif; ?>
    <?php if ($sucesso): ?><div class="alerta sucesso"><?= e($sucesso) ?></div><?php endif; ?>

    <form class="panel form-grid two-cols" method="post">
      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

      <label>Nome do cliente<input name="nome" required></label>
      <label>Empresa<input name="empresa"></label>
      <label>WhatsApp<input name="telefone" placeholder="11999999999" required></label>

      <button type="submit">Gerar convite</button>
    </form>

    <section class="panel">
      <h2>Convites gerados</h2>
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Cliente</th>
              <th>WhatsApp</th>
              <th>Status</th>
              <th>Expira em</th>
              <th>Link</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($convites as $convite): ?>
              <?php $link = link_convite($convite['token']); ?>
              <tr>
                <td><?= e($convite['nome']) ?><br><small><?= e($convite['empresa']) ?></small></td>
                <td><?= e($convite['telefone']) ?></td>
                <td><span class="status status-<?= e($convite['status']) ?>"><?= e($convite['status']) ?></span></td>
                <td><?= date('d/m/Y H:i', strtotime($convite['expira_em'])) ?></td>
                <td>
                  <div class="invite-actions">
                    <input readonly value="<?= e($link) ?>" aria-label="Link do convite">
                    <button type="button" class="btn small" data-copy="<?= e($link) ?>">Copiar link do convite</button>
                    <a class="btn small outline" href="<?= e(link_whatsapp($convite['telefone'], $link)) ?>" target="_blank" rel="noopener noreferrer">Enviar pelo WhatsApp</a>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>
  </main>

  <script src="../assets/js/main.js"></script>
</body>
</html>
