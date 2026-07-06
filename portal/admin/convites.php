<?php
require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/../includes/auth.php';

exigir_admin();

$erro = '';
$sucesso = $_SESSION['flash_convite'] ?? '';
unset($_SESSION['flash_convite']);

$pdo->exec("UPDATE convites_cliente SET status = 'expirado' WHERE status = 'pendente' AND expira_em < NOW()");

$paginasPadraoConvite = ['Início', 'Sobre', 'Serviços', 'Contato', 'Blog', 'Portfólio', 'Política de Privacidade'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validar_csrf();

    try {
        $nome = trim($_POST['nome'] ?? '');
        $empresa = trim($_POST['empresa'] ?? '');
        $telefone = trim($_POST['telefone'] ?? '');
        $projetoNome = trim($_POST['projeto_nome'] ?? '');
        $projetoDominio = trim($_POST['projeto_dominio'] ?? '');
        $projetoDescricao = trim($_POST['projeto_descricao'] ?? '');
        $paginasSelecionadas = $_POST['paginas_padrao'] ?? [];
        $paginasPersonalizadasTexto = trim($_POST['paginas_personalizadas'] ?? '');

        if (!is_array($paginasSelecionadas)) {
            $paginasSelecionadas = [];
        }

        if (!$nome || !$telefone) {
            throw new RuntimeException('Nome e WhatsApp são obrigatórios.');
        }

        if (!$projetoNome) {
            throw new RuntimeException('Informe o nome do projeto/site.');
        }

        if (strlen(preg_replace('/\D+/', '', $telefone)) < 10) {
            throw new RuntimeException('Informe um WhatsApp válido com DDD.');
        }

        $paginas = [];

        foreach ($paginasSelecionadas as $pagina) {
            $pagina = trim((string) $pagina);
            if (in_array($pagina, $paginasPadraoConvite, true)) {
                $paginas[] = $pagina;
            }
        }

        if ($paginasPersonalizadasTexto !== '') {
            $personalizadas = preg_split('/,|\r\n|\r|\n/', $paginasPersonalizadasTexto);
            foreach ($personalizadas as $pagina) {
                $pagina = trim($pagina);
                if ($pagina !== '') {
                    $paginas[] = $pagina;
                }
            }
        }

        $paginas = array_values(array_unique($paginas));

        if (!$paginas) {
            throw new RuntimeException('Selecione ou informe pelo menos uma página do site.');
        }

        do {
            $token = bin2hex(random_bytes(32));
            $stmtToken = $pdo->prepare('SELECT id FROM convites_cliente WHERE token = :token LIMIT 1');
            $stmtToken->execute([':token' => $token]);
        } while ($stmtToken->fetch());

        $stmt = $pdo->prepare(
            'INSERT INTO convites_cliente
             (token, nome, empresa, email, telefone, projeto_nome, projeto_dominio, projeto_descricao, paginas_json, expira_em)
             VALUES
             (:token, :nome, :empresa, NULL, :telefone, :projeto_nome, :projeto_dominio, :projeto_descricao, :paginas_json, DATE_ADD(NOW(), INTERVAL 48 HOUR))'
        );
        $stmt->execute([
            ':token' => $token,
            ':nome' => $nome,
            ':empresa' => $empresa,
            ':telefone' => $telefone,
            ':projeto_nome' => $projetoNome,
            ':projeto_dominio' => $projetoDominio,
            ':projeto_descricao' => $projetoDescricao,
            ':paginas_json' => json_encode($paginas, JSON_UNESCAPED_UNICODE),
        ]);

        $_SESSION['flash_convite'] = 'Convite criado com sucesso.';
        redirect('convites.php?novo=' . (int) $pdo->lastInsertId());
    } catch (Throwable $e) {
        $erro = $e->getMessage();
    }
}

$convites = $pdo->query('SELECT * FROM convites_cliente ORDER BY id DESC')->fetchAll();

function link_convite(string $token): string
{
    return rtrim(BASE_URL, '/') . '/portal/cliente/cadastro.php?token=' . $token;
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
      <a href="depoimentos.php">Depoimentos</a>
      <a href="diagnostico-metricas.php">&#128202; Diagnóstico</a>
      <a href="ponto/index.php">SONI PONTO</a>
      <a href="documentos-trabalho/index.php">DOCUMENTOS</a>
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

    <form class="panel form-grid two-cols" method="post" data-prevent-double-submit>
      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

      <h2 class="form-section-title">Dados do cliente</h2>
      <label>Nome do cliente<input name="nome" required></label>
      <label>Empresa<input name="empresa"></label>
      <label>WhatsApp<input name="telefone" placeholder="11999999999" required></label>

      <h2 class="form-section-title">Dados do projeto/site</h2>
      <label>Nome do projeto<input name="projeto_nome" required></label>
      <label>Domínio do site<input name="projeto_dominio" placeholder="https://cliente.com.br"></label>
      <label class="full">Descrição opcional<textarea name="projeto_descricao" rows="4"></textarea></label>

      <h2 class="form-section-title">Páginas do site</h2>
      <div class="checkbox-grid full">
        <?php foreach ($paginasPadraoConvite as $pagina): ?>
          <label><input type="checkbox" name="paginas_padrao[]" value="<?= e($pagina) ?>" <?= in_array($pagina, ['Início', 'Sobre', 'Serviços', 'Contato'], true) ? 'checked' : '' ?>> <?= e($pagina) ?></label>
        <?php endforeach; ?>
      </div>

      <label class="full">Páginas personalizadas
        <input name="paginas_personalizadas" placeholder="Galeria, Orçamento, Depoimentos">
      </label>

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
              <th>Projeto</th>
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
                <td><?= e($convite['projeto_nome']) ?><br><small><?= e($convite['projeto_dominio']) ?></small></td>
                <td><span class="status status-<?= e($convite['status']) ?>"><?= e($convite['status']) ?></span></td>
                <td><?= date('d/m/Y H:i', strtotime($convite['expira_em'])) ?></td>
                <td>
                  <div class="invite-actions">
                    <input id="convite-<?= (int) $convite['id'] ?>" readonly value="<?= e($link) ?>" aria-label="Link do convite">
                    <button type="button" class="btn small" data-copy-target="#convite-<?= (int) $convite['id'] ?>">Copiar link do convite</button>
                    <span class="copy-feedback" aria-live="polite"></span>
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
