<?php
declare(strict_types=1);

require_once __DIR__ . '/_funcoes.php';

$erro = '';
$sucesso = '';
$lojas = ponto_lojas($pdo, false);
$editarId = (int) ($_GET['editar'] ?? 0);
$trajetoEditar = null;

if ($editarId > 0) {
    $stmtEditar = $pdo->prepare('SELECT * FROM trajetos_trabalho WHERE id = :id LIMIT 1');
    $stmtEditar->execute([':id' => $editarId]);
    $trajetoEditar = $stmtEditar->fetch() ?: null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validar_csrf();

    try {
        $id = (int) ($_POST['id'] ?? 0);
        $valorIda = ponto_decimal($_POST['valor_ida'] ?? '');
        $valorVolta = ponto_decimal($_POST['valor_volta'] ?? '');
        $valorTotal = ponto_decimal($_POST['valor_total'] ?? '');

        if ($valorTotal <= 0 && ($valorIda > 0 || $valorVolta > 0)) {
            $valorTotal = $valorIda + $valorVolta;
        }

        $dados = [
            ':loja_id' => (int) ($_POST['loja_id'] ?? 0),
            ':nome_trajeto' => trim($_POST['nome_trajeto'] ?? ''),
            ':tipo_transporte' => trim($_POST['tipo_transporte'] ?? ''),
            ':valor_ida' => $valorIda,
            ':valor_volta' => $valorVolta,
            ':valor_total' => $valorTotal,
            ':tempo_medio' => trim($_POST['tempo_medio'] ?? ''),
            ':observacoes' => trim($_POST['observacoes'] ?? ''),
            ':ativo' => isset($_POST['ativo']) ? 1 : 0,
        ];

        if ($dados[':loja_id'] <= 0 || $dados[':nome_trajeto'] === '') {
            throw new RuntimeException('Loja e nome do trajeto são obrigatórios.');
        }

        if ($id > 0) {
            $dados[':id'] = $id;
            $stmt = $pdo->prepare(
                'UPDATE trajetos_trabalho
                 SET loja_id = :loja_id, nome_trajeto = :nome_trajeto, tipo_transporte = :tipo_transporte,
                     valor_ida = :valor_ida, valor_volta = :valor_volta, valor_total = :valor_total,
                     tempo_medio = :tempo_medio, observacoes = :observacoes, ativo = :ativo
                 WHERE id = :id'
            );
            $stmt->execute($dados);
            $sucesso = 'Trajeto atualizado.';
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO trajetos_trabalho
                 (loja_id, nome_trajeto, tipo_transporte, valor_ida, valor_volta, valor_total, tempo_medio, observacoes, ativo)
                 VALUES
                 (:loja_id, :nome_trajeto, :tipo_transporte, :valor_ida, :valor_volta, :valor_total, :tempo_medio, :observacoes, :ativo)'
            );
            $stmt->execute($dados);
            $sucesso = 'Trajeto cadastrado.';
        }
    } catch (Throwable $e) {
        $erro = ponto_mensagem_erro($e);
    }
}

$trajetos = $pdo->query(
    'SELECT t.*, l.codigo_loja, l.nome AS loja_nome
     FROM trajetos_trabalho t
     INNER JOIN lojas_trabalho l ON l.id = t.loja_id
     ORDER BY t.ativo DESC, l.codigo_loja, t.nome_trajeto'
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Trajetos | SONI PONTO</title>
  <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
  <?php ponto_render_header('SONI PONTO'); ?>

  <main class="app-container">
    <section class="page-title">
      <span>SONI PONTO</span>
      <h1>Trajetos de trabalho</h1>
    </section>

    <?php ponto_render_nav('trajetos.php'); ?>
    <?php if ($erro): ?><div class="alerta erro"><?= e($erro) ?></div><?php endif; ?>
    <?php if ($sucesso): ?><div class="alerta sucesso"><?= e($sucesso) ?></div><?php endif; ?>

    <form class="panel form-grid two-cols" method="post">
      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="id" value="<?= (int) ($trajetoEditar['id'] ?? 0) ?>">
      <label>Loja
        <select name="loja_id" required>
          <option value="">Selecione</option>
          <?php foreach ($lojas as $loja): ?>
            <option value="<?= (int) $loja['id'] ?>" <?= (int) ($trajetoEditar['loja_id'] ?? 0) === (int) $loja['id'] ? 'selected' : '' ?>>Loja <?= e((string) $loja['codigo_loja']) ?> - <?= e((string) $loja['nome']) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>Nome do trajeto<input name="nome_trajeto" value="<?= e((string) ($trajetoEditar['nome_trajeto'] ?? '')) ?>" required></label>
      <label>Tipo de transporte<input name="tipo_transporte" placeholder="Ônibus, trem, van..." value="<?= e((string) ($trajetoEditar['tipo_transporte'] ?? '')) ?>"></label>
      <label>Tempo médio<input name="tempo_medio" placeholder="1h20" value="<?= e((string) ($trajetoEditar['tempo_medio'] ?? '')) ?>"></label>
      <label>Valor ida<input name="valor_ida" inputmode="decimal" value="<?= e(number_format((float) ($trajetoEditar['valor_ida'] ?? 0), 2, ',', '.')) ?>"></label>
      <label>Valor volta<input name="valor_volta" inputmode="decimal" value="<?= e(number_format((float) ($trajetoEditar['valor_volta'] ?? 0), 2, ',', '.')) ?>"></label>
      <label>Valor total<input name="valor_total" inputmode="decimal" value="<?= e(number_format((float) ($trajetoEditar['valor_total'] ?? 0), 2, ',', '.')) ?>"></label>
      <label class="ponto-checkbox"><input type="checkbox" name="ativo" <?= (int) ($trajetoEditar['ativo'] ?? 1) === 1 ? 'checked' : '' ?>> Ativo</label>
      <label class="full">Observações<textarea name="observacoes" rows="3"><?= e((string) ($trajetoEditar['observacoes'] ?? '')) ?></textarea></label>
      <button type="submit"><?= $trajetoEditar ? 'Salvar trajeto' : 'Cadastrar trajeto' ?></button>
    </form>

    <section class="panel">
      <h2>Trajetos cadastrados</h2>
      <div class="table-wrap">
        <table>
          <thead><tr><th>Loja</th><th>Trajeto</th><th>Transporte</th><th>Tempo</th><th>Total</th><th>Status</th><th>Ações</th></tr></thead>
          <tbody>
            <?php if (!$trajetos): ?><tr><td colspan="7">Nenhum trajeto cadastrado.</td></tr><?php endif; ?>
            <?php foreach ($trajetos as $trajeto): ?>
              <tr>
                <td>Loja <?= e((string) $trajeto['codigo_loja']) ?></td>
                <td><?= e((string) $trajeto['nome_trajeto']) ?></td>
                <td><?= e((string) $trajeto['tipo_transporte']) ?></td>
                <td><?= e((string) $trajeto['tempo_medio']) ?></td>
                <td><?= e(ponto_moeda((float) $trajeto['valor_total'])) ?></td>
                <td><span class="status <?= (int) $trajeto['ativo'] === 1 ? 'status-concluido' : 'status-expirado' ?>"><?= (int) $trajeto['ativo'] === 1 ? 'ativo' : 'inativo' ?></span></td>
                <td><a href="trajetos.php?editar=<?= (int) $trajeto['id'] ?>">Editar</a></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>
  </main>
</body>
</html>
