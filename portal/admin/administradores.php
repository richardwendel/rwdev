<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/../includes/admin_ui.php';
require_once __DIR__ . '/../includes/auditoria.php';

exigir_admin();
exigir_permissao('admins.visualizar');

$erro = '';
$sucesso = $_SESSION['flash_admin'] ?? '';
$linkConvite = $_SESSION['flash_admin_link'] ?? '';
unset($_SESSION['flash_admin'], $_SESSION['flash_admin_link']);

$perfis = perfis_admin();
$permissoesPorModulo = permissoes_admin_disponiveis();
$adminLogado = admin_atual() ?? [];

function admin_link_convite(string $token): string
{
    return rtrim(BASE_URL, '/') . '/portal/admin/ativar-admin.php?token=' . $token;
}

function admin_permissoes_do_usuario(PDO $pdo, int $adminId): array
{
    if (!admin_tabela_existe($pdo, 'admin_permissoes')) {
        return [];
    }

    $stmt = $pdo->prepare('SELECT permissao FROM admin_permissoes WHERE admin_id = :admin_id');
    $stmt->execute([':admin_id' => $adminId]);

    return $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
}

function admin_salvar_permissoes(PDO $pdo, int $adminId, string $perfil, array $permissoes): void
{
    if (!admin_tabela_existe($pdo, 'admin_permissoes')) {
        return;
    }

    $stmt = $pdo->prepare('DELETE FROM admin_permissoes WHERE admin_id = :admin_id');
    $stmt->execute([':admin_id' => $adminId]);

    if ($perfil === 'superadministrador') {
        return;
    }

    $stmt = $pdo->prepare('INSERT INTO admin_permissoes (admin_id, permissao) VALUES (:admin_id, :permissao)');
    foreach ($permissoes as $permissao) {
        $stmt->execute([':admin_id' => $adminId, ':permissao' => $permissao]);
    }
}

function admin_buscar(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM admins WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    $admin = $stmt->fetch();

    return $admin ?: null;
}

$editarId = (int) ($_GET['editar'] ?? 0);
$adminEditar = $editarId > 0 ? admin_buscar($pdo, $editarId) : null;
$permissoesEditar = $adminEditar ? admin_permissoes_do_usuario($pdo, (int) $adminEditar['id']) : [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validar_csrf();
    $acao = (string) ($_POST['acao'] ?? '');

    try {
        if ($acao === 'salvar') {
            exigir_permissao('admins.editar');

            $id = (int) ($_POST['id'] ?? 0);
            $nome = trim((string) ($_POST['nome'] ?? ''));
            $email = trim((string) ($_POST['email'] ?? ''));
            $perfil = (string) ($_POST['perfil'] ?? 'visualizador');
            $ativo = isset($_POST['ativo']) ? 1 : 0;
            $senha = (string) ($_POST['senha'] ?? '');
            $permissoes = admin_permissoes_selecionadas($_POST['permissoes'] ?? []);

            if ($nome === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new RuntimeException('Informe nome e e-mail validos.');
            }

            if (!array_key_exists($perfil, $perfis)) {
                throw new RuntimeException('Perfil invalido.');
            }

            if ($perfil === 'visualizador') {
                $permissoes = array_values(array_filter($permissoes, static fn (string $permissao): bool => substr($permissao, -11) === '.visualizar'));
            }

            $pdo->beginTransaction();

            if ($id > 0) {
                $adminAlvo = admin_buscar($pdo, $id);
                if (!$adminAlvo) {
                    throw new RuntimeException('Administrador nao encontrado.');
                }

                $perfilAtual = (string) ($adminAlvo['perfil'] ?? 'superadministrador');
                if ($perfilAtual === 'superadministrador' && (int) $adminLogado['id'] !== (int) $adminAlvo['id'] && (string) $adminLogado['perfil'] !== 'superadministrador') {
                    throw new RuntimeException('Somente superadministrador pode alterar outro superadministrador.');
                }

                if ((int) $adminAlvo['id'] === (int) $adminLogado['id'] && $ativo !== 1) {
                    throw new RuntimeException('Voce nao pode desativar a propria conta.');
                }

                $sqlSenha = $senha !== '' ? ', senha = :senha' : '';
                $stmt = $pdo->prepare(
                    'UPDATE admins
                     SET nome = :nome, email = :email, perfil = :perfil, ativo = :ativo' . $sqlSenha . '
                     WHERE id = :id'
                );
                $params = [
                    ':nome' => $nome,
                    ':email' => $email,
                    ':perfil' => $perfil,
                    ':ativo' => $ativo,
                    ':id' => $id,
                ];
                if ($senha !== '') {
                    $params[':senha'] = password_hash($senha, PASSWORD_DEFAULT);
                }
                $stmt->execute($params);

                admin_salvar_permissoes($pdo, $id, $perfil, $permissoes);
                admin_registrar_evento($pdo, 'admin_permissoes_alteradas', $email, 'Administrador atualizado: ' . $id);
                registrar_auditoria(
                    'administradores',
                    $ativo === 1 ? 'administrador_atualizado' : 'administrador_desativado',
                    'admins',
                    $id,
                    $adminAlvo,
                    ['nome' => $nome, 'email' => $email, 'perfil' => $perfil, 'ativo' => $ativo, 'permissoes' => $permissoes],
                    'sucesso',
                    null,
                    'Administrador atualizado'
                );
                $_SESSION['flash_admin'] = 'Administrador atualizado.';
            } else {
                exigir_permissao('admins.criar');

                if ($senha === '') {
                    throw new RuntimeException('Informe uma senha para cadastro manual.');
                }

                $stmt = $pdo->prepare(
                    'INSERT INTO admins (nome, email, senha, perfil, ativo, criado_por)
                     VALUES (:nome, :email, :senha, :perfil, :ativo, :criado_por)'
                );
                $stmt->execute([
                    ':nome' => $nome,
                    ':email' => $email,
                    ':senha' => password_hash($senha, PASSWORD_DEFAULT),
                    ':perfil' => $perfil,
                    ':ativo' => $ativo,
                    ':criado_por' => (int) $adminLogado['id'],
                ]);
                $novoId = (int) $pdo->lastInsertId();
                admin_salvar_permissoes($pdo, $novoId, $perfil, $permissoes);
                admin_registrar_evento($pdo, 'admin_criado', $email, 'Administrador criado manualmente: ' . $novoId);
                registrar_auditoria(
                    'administradores',
                    'administrador_criado',
                    'admins',
                    $novoId,
                    [],
                    ['nome' => $nome, 'email' => $email, 'perfil' => $perfil, 'ativo' => $ativo, 'permissoes' => $permissoes],
                    'sucesso',
                    null,
                    'Administrador cadastrado manualmente'
                );
                $_SESSION['flash_admin'] = 'Administrador cadastrado.';
            }

            $pdo->commit();
            unset($_SESSION['admin_atual']);
            redirect('administradores.php');
        }

        if ($acao === 'convidar') {
            exigir_permissao('admins.convidar');

            $nome = trim((string) ($_POST['convite_nome'] ?? ''));
            $email = trim((string) ($_POST['convite_email'] ?? ''));
            $perfil = (string) ($_POST['convite_perfil'] ?? 'administrador_modulo');
            $permissoes = admin_permissoes_selecionadas($_POST['convite_permissoes'] ?? []);

            if ($nome === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new RuntimeException('Informe nome e e-mail validos para o convite.');
            }

            if (!array_key_exists($perfil, $perfis) || $perfil === 'superadministrador') {
                throw new RuntimeException('Convites devem usar perfil operacional, nao superadministrador.');
            }

            if ($perfil === 'visualizador') {
                $permissoes = array_values(array_filter($permissoes, static fn (string $permissao): bool => substr($permissao, -11) === '.visualizar'));
            }

            $stmtExiste = $pdo->prepare('SELECT id FROM admins WHERE email = :email LIMIT 1');
            $stmtExiste->execute([':email' => $email]);
            if ($stmtExiste->fetch()) {
                throw new RuntimeException('Este e-mail ja esta cadastrado como administrador.');
            }

            $token = bin2hex(random_bytes(32));
            $tokenHash = hash('sha256', $token);

            $pdo->beginTransaction();
            $stmtAdmin = $pdo->prepare(
                'INSERT INTO admins (nome, email, senha, perfil, ativo, criado_por)
                 VALUES (:nome, :email, "", :perfil, 0, :criado_por)'
            );
            $stmtAdmin->execute([
                ':nome' => $nome,
                ':email' => $email,
                ':perfil' => $perfil,
                ':criado_por' => (int) $adminLogado['id'],
            ]);
            $adminId = (int) $pdo->lastInsertId();
            admin_salvar_permissoes($pdo, $adminId, $perfil, $permissoes);

            $stmtConvite = $pdo->prepare(
                'INSERT INTO convites_admin (admin_id, token_hash, nome, email, perfil, permissoes_json, criado_por, expira_em)
                 VALUES (:admin_id, :token_hash, :nome, :email, :perfil, :permissoes_json, :criado_por, DATE_ADD(NOW(), INTERVAL 48 HOUR))'
            );
            $stmtConvite->execute([
                ':admin_id' => $adminId,
                ':token_hash' => $tokenHash,
                ':nome' => $nome,
                ':email' => $email,
                ':perfil' => $perfil,
                ':permissoes_json' => json_encode($permissoes, JSON_UNESCAPED_UNICODE),
                ':criado_por' => (int) $adminLogado['id'],
            ]);

            $pdo->commit();
            admin_registrar_evento($pdo, 'admin_convite_criado', $email, 'Convite administrativo criado.');
            registrar_auditoria(
                'convites_admin',
                'convite_criado',
                'convites_admin',
                (int) $pdo->lastInsertId(),
                [],
                ['admin_id' => $adminId, 'nome' => $nome, 'email' => $email, 'perfil' => $perfil, 'permissoes' => $permissoes],
                'sucesso',
                null,
                'Convite administrativo criado'
            );
            $_SESSION['flash_admin'] = 'Convite administrativo criado.';
            $_SESSION['flash_admin_link'] = admin_link_convite($token);
            redirect('administradores.php');
        }

        if ($acao === 'revogar') {
            exigir_permissao('admins.convidar');
            $conviteId = (int) ($_POST['convite_id'] ?? 0);
            $stmt = $pdo->prepare('UPDATE convites_admin SET status = "revogado" WHERE id = :id AND status = "pendente"');
            $stmt->execute([':id' => $conviteId]);
            admin_registrar_evento($pdo, 'admin_convite_revogado', null, 'Convite administrativo revogado: ' . $conviteId);
            registrar_auditoria('convites_admin', 'convite_revogado', 'convites_admin', $conviteId, [], ['status' => 'revogado'], 'sucesso', null, 'Convite administrativo revogado');
            $_SESSION['flash_admin'] = 'Convite revogado.';
            redirect('administradores.php');
        }
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        registrar_auditoria('administradores', 'erro_operacao', 'admins', null, [], [], 'erro', $e->getMessage(), 'Falha em operacao administrativa');
        $erro = $e->getMessage();
    }
}

$pdo->exec('UPDATE convites_admin SET status = "expirado" WHERE status = "pendente" AND expira_em < NOW()');

$admins = $pdo->query(
    'SELECT a.*, criador.nome AS criado_por_nome
     FROM admins a
     LEFT JOIN admins criador ON criador.id = a.criado_por
     ORDER BY a.id ASC'
)->fetchAll();

$convites = $pdo->query(
    'SELECT c.*, a.ativo
     FROM convites_admin c
     LEFT JOIN admins a ON a.id = c.admin_id
     ORDER BY c.id DESC
     LIMIT 20'
)->fetchAll();

$permissoesPadraoMarquinhos = permissoes_mestre_dos_magos();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Administradores | RWDEV</title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
  <?php admin_render_header(); ?>

  <main class="app-container">
    <section class="page-title">
      <span>Seguranca administrativa</span>
      <h1>Administradores</h1>
    </section>

    <?php if ($erro): ?><div class="alerta erro"><?= e($erro) ?></div><?php endif; ?>
    <?php if ($sucesso): ?><div class="alerta sucesso"><?= e($sucesso) ?></div><?php endif; ?>
    <?php if ($linkConvite): ?>
      <div class="alerta sucesso invite-actions">
        <input id="convite-admin-link" readonly value="<?= e($linkConvite) ?>" aria-label="Link do convite administrativo">
        <button type="button" class="btn small" data-copy-target="#convite-admin-link">Copiar link</button>
        <span class="copy-feedback" aria-live="polite"></span>
      </div>
    <?php endif; ?>

    <?php if (usuario_pode('admins.convidar')): ?>
      <form class="panel form-grid two-cols" method="post">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="acao" value="convidar">
        <h2 class="form-section-title">Convidar administrador</h2>
        <label>Nome<input name="convite_nome" value="Marquinhos" required></label>
        <label>E-mail<input name="convite_email" type="email" required></label>
        <label>Perfil
          <select name="convite_perfil">
            <option value="administrador_modulo">Administrador de modulo</option>
            <option value="visualizador">Visualizador</option>
          </select>
        </label>
        <div></div>
        <div class="checkbox-grid full">
          <?php foreach ($permissoesPorModulo as $modulo => $permissoes): ?>
            <fieldset class="permission-group">
              <legend><?= e($modulo) ?></legend>
              <?php foreach ($permissoes as $permissao): ?>
                <label><input type="checkbox" name="convite_permissoes[]" value="<?= e($permissao) ?>" <?= in_array($permissao, $permissoesPadraoMarquinhos, true) ? 'checked' : '' ?>> <?= e($permissao) ?></label>
              <?php endforeach; ?>
            </fieldset>
          <?php endforeach; ?>
        </div>
        <button type="submit">Gerar convite administrativo</button>
      </form>
    <?php endif; ?>

    <?php if (usuario_pode('admins.criar') || usuario_pode('admins.editar')): ?>
      <form class="panel form-grid two-cols" method="post">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="acao" value="salvar">
        <input type="hidden" name="id" value="<?= (int) ($adminEditar['id'] ?? 0) ?>">
        <h2 class="form-section-title"><?= $adminEditar ? 'Editar administrador' : 'Cadastrar manualmente' ?></h2>
        <label>Nome<input name="nome" value="<?= e((string) ($adminEditar['nome'] ?? '')) ?>" required></label>
        <label>E-mail<input name="email" type="email" value="<?= e((string) ($adminEditar['email'] ?? '')) ?>" required></label>
        <label>Senha<input name="senha" type="password" autocomplete="new-password" <?= $adminEditar ? '' : 'required' ?>></label>
        <label>Perfil
          <select name="perfil">
            <?php foreach ($perfis as $valor => $rotulo): ?>
              <option value="<?= e($valor) ?>" <?= (string) ($adminEditar['perfil'] ?? 'visualizador') === $valor ? 'selected' : '' ?>><?= e($rotulo) ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <label class="ponto-checkbox"><input type="checkbox" name="ativo" <?= (int) ($adminEditar['ativo'] ?? 1) === 1 ? 'checked' : '' ?>> Ativo</label>
        <div class="checkbox-grid full">
          <?php foreach ($permissoesPorModulo as $modulo => $permissoes): ?>
            <fieldset class="permission-group">
              <legend><?= e($modulo) ?></legend>
              <?php foreach ($permissoes as $permissao): ?>
                <label><input type="checkbox" name="permissoes[]" value="<?= e($permissao) ?>" <?= in_array($permissao, $permissoesEditar, true) ? 'checked' : '' ?>> <?= e($permissao) ?></label>
              <?php endforeach; ?>
            </fieldset>
          <?php endforeach; ?>
        </div>
        <button type="submit">Salvar administrador</button>
      </form>
    <?php endif; ?>

    <section class="panel">
      <h2>Administradores cadastrados</h2>
      <div class="table-wrap">
        <table>
          <thead><tr><th>ID</th><th>Nome</th><th>E-mail</th><th>Perfil</th><th>Status</th><th>Ultimo acesso</th><th>Acoes</th></tr></thead>
          <tbody>
            <?php foreach ($admins as $admin): ?>
              <tr>
                <td><?= (int) $admin['id'] ?></td>
                <td><?= e((string) $admin['nome']) ?><br><small>Criado por: <?= e((string) ($admin['criado_por_nome'] ?? '-')) ?></small></td>
                <td><?= e((string) $admin['email']) ?></td>
                <td><?= e($perfis[(string) ($admin['perfil'] ?? 'superadministrador')] ?? (string) ($admin['perfil'] ?? '')) ?></td>
                <td><span class="status <?= (int) ($admin['ativo'] ?? 1) === 1 ? 'status-concluido' : 'status-expirado' ?>"><?= (int) ($admin['ativo'] ?? 1) === 1 ? 'ativo' : 'inativo' ?></span></td>
                <td><?= !empty($admin['ultimo_acesso']) ? date('d/m/Y H:i', strtotime((string) $admin['ultimo_acesso'])) : '-' ?></td>
                <td><?php if (usuario_pode('admins.editar')): ?><a href="administradores.php?editar=<?= (int) $admin['id'] ?>">Editar</a><?php endif; ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>

    <section class="panel">
      <h2>Convites administrativos</h2>
      <div class="table-wrap">
        <table>
          <thead><tr><th>Nome</th><th>E-mail</th><th>Perfil</th><th>Status</th><th>Expira em</th><th>Acoes</th></tr></thead>
          <tbody>
            <?php foreach ($convites as $convite): ?>
              <tr>
                <td><?= e((string) $convite['nome']) ?></td>
                <td><?= e((string) $convite['email']) ?></td>
                <td><?= e($perfis[(string) $convite['perfil']] ?? (string) $convite['perfil']) ?></td>
                <td><span class="status status-<?= e((string) $convite['status']) ?>"><?= e((string) $convite['status']) ?></span></td>
                <td><?= date('d/m/Y H:i', strtotime((string) $convite['expira_em'])) ?></td>
                <td>
                  <?php if ($convite['status'] === 'pendente' && usuario_pode('admins.convidar')): ?>
                    <form method="post" class="inline-form">
                      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                      <input type="hidden" name="acao" value="revogar">
                      <input type="hidden" name="convite_id" value="<?= (int) $convite['id'] ?>">
                      <button class="btn small danger" type="submit">Revogar</button>
                    </form>
                  <?php endif; ?>
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
