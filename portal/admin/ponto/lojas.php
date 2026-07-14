<?php
declare(strict_types=1);

require_once __DIR__ . '/_funcoes.php';

$erro = '';
$sucesso = '';
$editarId = (int) ($_GET['editar'] ?? 0);
$lojaEditar = null;

if ($editarId > 0) {
    $stmtEditar = $pdo->prepare('SELECT * FROM lojas_trabalho WHERE id = :id LIMIT 1');
    $stmtEditar->execute([':id' => $editarId]);
    $lojaEditar = $stmtEditar->fetch() ?: null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validar_csrf();

    try {
        $id = (int) ($_POST['id'] ?? 0);
        $dados = [
            ':codigo_loja' => trim($_POST['codigo_loja'] ?? ''),
            ':numero_interno' => trim($_POST['numero_interno'] ?? ''),
            ':nome' => trim($_POST['nome'] ?? ''),
            ':endereco' => trim($_POST['endereco'] ?? ''),
            ':cidade' => trim($_POST['cidade'] ?? ''),
            ':responsavel' => trim($_POST['responsavel'] ?? ''),
            ':telefone' => trim($_POST['telefone'] ?? ''),
            ':horario_padrao' => trim($_POST['horario_padrao'] ?? ''),
            ':cor_identificacao' => trim($_POST['cor_identificacao'] ?? ''),
            ':observacoes' => trim($_POST['observacoes'] ?? ''),
            ':ativo' => isset($_POST['ativo']) ? 1 : 0,
        ];

        if ($dados[':codigo_loja'] === '' || $dados[':nome'] === '') {
            throw new RuntimeException('Código e nome da loja são obrigatórios.');
        }

        if ($id > 0) {
            $dados[':id'] = $id;
            $stmt = $pdo->prepare(
                'UPDATE lojas_trabalho
                 SET codigo_loja = :codigo_loja, numero_interno = :numero_interno, nome = :nome,
                     endereco = :endereco, cidade = :cidade, responsavel = :responsavel, telefone = :telefone,
                     horario_padrao = :horario_padrao, cor_identificacao = :cor_identificacao,
                     observacoes = :observacoes, ativo = :ativo
                 WHERE id = :id'
            );
            $stmt->execute($dados);
            $sucesso = 'Loja atualizada.';
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO lojas_trabalho
                 (codigo_loja, numero_interno, nome, endereco, cidade, responsavel, telefone, horario_padrao, cor_identificacao, observacoes, ativo)
                 VALUES
                 (:codigo_loja, :numero_interno, :nome, :endereco, :cidade, :responsavel, :telefone, :horario_padrao, :cor_identificacao, :observacoes, :ativo)'
            );
            $stmt->execute($dados);
            $sucesso = 'Loja cadastrada.';
        }
    } catch (Throwable $e) {
        $erro = ponto_mensagem_erro($e);
    }
}

$lojas = $pdo->query('SELECT * FROM lojas_trabalho ORDER BY ativo DESC, codigo_loja, nome')->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Lojas | SONI PONTO</title>
  <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
  <?php ponto_render_header('SONI PONTO'); ?>

  <main class="app-container">
    <section class="page-title">
      <span>SONI PONTO</span>
      <h1>Lojas de trabalho</h1>
    </section>

    <?php ponto_render_nav('lojas.php'); ?>
    <?php if ($erro): ?><div class="alerta erro"><?= e($erro) ?></div><?php endif; ?>
    <?php if ($sucesso): ?><div class="alerta sucesso"><?= e($sucesso) ?></div><?php endif; ?>

    <form class="panel form-grid two-cols" method="post">
      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="id" value="<?= (int) ($lojaEditar['id'] ?? 0) ?>">
      <label>Código da loja<input name="codigo_loja" value="<?= e((string) ($lojaEditar['codigo_loja'] ?? '')) ?>" required></label>
      <label>Número interno<input name="numero_interno" value="<?= e((string) ($lojaEditar['numero_interno'] ?? '')) ?>" placeholder="04 ou pendente"></label>
      <label>Nome<input name="nome" value="<?= e((string) ($lojaEditar['nome'] ?? '')) ?>" required></label>
      <label>Endereço<input name="endereco" value="<?= e((string) ($lojaEditar['endereco'] ?? '')) ?>"></label>
      <label>Cidade<input name="cidade" value="<?= e((string) ($lojaEditar['cidade'] ?? '')) ?>"></label>
      <label>Responsável<input name="responsavel" value="<?= e((string) ($lojaEditar['responsavel'] ?? '')) ?>"></label>
      <label>Telefone<input name="telefone" value="<?= e((string) ($lojaEditar['telefone'] ?? '')) ?>"></label>
      <label>Horário padrão<input name="horario_padrao" value="<?= e((string) ($lojaEditar['horario_padrao'] ?? '')) ?>"></label>
      <label>Cor<input name="cor_identificacao" type="color" value="<?= e((string) ($lojaEditar['cor_identificacao'] ?? '#2563eb')) ?>"></label>
      <label class="full">Observações<textarea name="observacoes" rows="3"><?= e((string) ($lojaEditar['observacoes'] ?? '')) ?></textarea></label>
      <label class="ponto-checkbox"><input type="checkbox" name="ativo" <?= (int) ($lojaEditar['ativo'] ?? 1) === 1 ? 'checked' : '' ?>> Ativa</label>
      <button type="submit"><?= $lojaEditar ? 'Salvar loja' : 'Cadastrar loja' ?></button>
    </form>

    <section class="panel">
      <h2>Lojas cadastradas</h2>
      <div class="table-wrap">
        <table>
          <thead><tr><th>Código</th><th>Número</th><th>Nome</th><th>Cidade</th><th>Contato</th><th>Status</th><th>Ações</th></tr></thead>
          <tbody>
            <?php foreach ($lojas as $loja): ?>
              <tr>
                <td><?= e((string) $loja['codigo_loja']) ?></td>
                <td><?= e((string) ($loja['numero_interno'] ?? '')) ?></td>
                <td><?= e((string) $loja['nome']) ?></td>
                <td><?= e((string) $loja['cidade']) ?></td>
                <td><span class="loja-cor" style="--loja-cor: <?= e((string) ($loja['cor_identificacao'] ?? '#2563eb')) ?>"></span><?= e((string) ($loja['responsavel'] ?? '')) ?><br><small><?= e((string) ($loja['telefone'] ?? '')) ?></small></td>
                <td><span class="status <?= (int) $loja['ativo'] === 1 ? 'status-concluido' : 'status-expirado' ?>"><?= (int) $loja['ativo'] === 1 ? 'ativa' : 'inativa' ?></span></td>
                <td><a href="lojas.php?editar=<?= (int) $loja['id'] ?>">Editar</a></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>
  </main>
</body>
</html>
