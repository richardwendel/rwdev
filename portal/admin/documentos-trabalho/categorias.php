<?php
declare(strict_types=1);

require_once __DIR__ . '/_funcoes.php';

exigir_permissao('documentos.editar');

$erro = '';
$sucesso = '';
$editarId = (int) ($_GET['editar'] ?? 0);
$categoriaEditar = null;

if ($editarId > 0) {
    $stmtEditar = $pdo->prepare('SELECT * FROM documentos_trabalho_categorias WHERE id = :id LIMIT 1');
    $stmtEditar->execute([':id' => $editarId]);
    $categoriaEditar = $stmtEditar->fetch() ?: null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validar_csrf();

    try {
        $id = (int) ($_POST['id'] ?? 0);
        $nome = trim($_POST['nome'] ?? '');
        $ativo = isset($_POST['ativo']) ? 1 : 0;

        if ($nome === '') {
            throw new RuntimeException('Informe o nome da categoria.');
        }

        if ($id > 0) {
            $stmt = $pdo->prepare('UPDATE documentos_trabalho_categorias SET nome = :nome, ativo = :ativo WHERE id = :id');
            $stmt->execute([':nome' => $nome, ':ativo' => $ativo, ':id' => $id]);
            $sucesso = 'Categoria atualizada.';
        } else {
            $stmt = $pdo->prepare('INSERT INTO documentos_trabalho_categorias (nome, ativo) VALUES (:nome, :ativo)');
            $stmt->execute([':nome' => $nome, ':ativo' => $ativo]);
            $sucesso = 'Categoria cadastrada.';
        }
    } catch (Throwable $e) {
        $erro = docs_mensagem_erro($e);
    }
}

$categoriasLista = $pdo->query('SELECT * FROM documentos_trabalho_categorias ORDER BY ativo DESC, nome')->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Categorias | Documentos do Trabalho</title>
  <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
  <?php docs_render_header(); ?>

  <main class="app-container">
    <section class="page-title">
      <span>Documentos do Trabalho</span>
      <h1>Categorias</h1>
    </section>

    <?php docs_render_nav('categorias.php'); ?>
    <?php if ($erro): ?><div class="alerta erro"><?= e($erro) ?></div><?php endif; ?>
    <?php if ($sucesso): ?><div class="alerta sucesso"><?= e($sucesso) ?></div><?php endif; ?>

    <form class="panel form-grid two-cols" method="post">
      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="id" value="<?= (int) ($categoriaEditar['id'] ?? 0) ?>">
      <label>Nome da categoria<input name="nome" value="<?= e((string) ($categoriaEditar['nome'] ?? '')) ?>" required></label>
      <label class="ponto-checkbox"><input type="checkbox" name="ativo" <?= (int) ($categoriaEditar['ativo'] ?? 1) === 1 ? 'checked' : '' ?>> Ativa</label>
      <button type="submit"><?= $categoriaEditar ? 'Salvar categoria' : 'Cadastrar categoria' ?></button>
    </form>

    <section class="panel">
      <h2>Categorias cadastradas</h2>
      <div class="table-wrap">
        <table>
          <thead><tr><th>Nome</th><th>Status</th><th>Ações</th></tr></thead>
          <tbody>
            <?php foreach ($categoriasLista as $categoria): ?>
              <tr>
                <td><?= e((string) $categoria['nome']) ?></td>
                <td><span class="status <?= (int) $categoria['ativo'] === 1 ? 'status-concluido' : 'status-expirado' ?>"><?= (int) $categoria['ativo'] === 1 ? 'ativa' : 'inativa' ?></span></td>
                <td><a href="categorias.php?editar=<?= (int) $categoria['id'] ?>">Editar</a></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>
  </main>
</body>
</html>
