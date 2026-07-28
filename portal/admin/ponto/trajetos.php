<?php
declare(strict_types=1);

require_once __DIR__ . '/_funcoes.php';

exigir_permissao('trajetos.visualizar');

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
        $dados = [
            ':loja_id' => (int) ($_POST['loja_id'] ?? 0),
            ':nome_trajeto' => trim($_POST['nome_trajeto'] ?? ''),
            ':tipo_transporte' => trim($_POST['tipo_transporte'] ?? ''),
            ':valor_ida' => ponto_decimal($_POST['valor_ida'] ?? ''),
            ':valor_volta' => ponto_decimal($_POST['valor_volta'] ?? ''),
            ':valor_total' => ponto_decimal($_POST['valor_total'] ?? ''),
            ':tempo_medio' => trim($_POST['tempo_medio'] ?? ''),
            ':padrao_loja' => isset($_POST['padrao_loja']) ? 1 : 0,
            ':observacoes' => trim($_POST['observacoes'] ?? ''),
            ':ativo' => isset($_POST['ativo']) ? 1 : 0,
        ];
        $dadosAuditoria = array_combine(
            array_map(static fn (string $campo): string => ltrim($campo, ':'), array_keys($dados)),
            array_values($dados)
        );

        if ($dados[':loja_id'] <= 0 || $dados[':nome_trajeto'] === '') {
            throw new RuntimeException('Loja e nome do trajeto são obrigatórios.');
        }
        $soma = round((float)$dados[':valor_ida'] + (float)$dados[':valor_volta'], 2);
        if (abs((float)$dados[':valor_total'] - $soma) > 0.01) {
            $dados[':valor_total'] = $soma;
            $dadosAuditoria['valor_total'] = $soma;
        }
        exigir_permissao($id > 0 ? 'trajetos.editar' : 'trajetos.criar');
        $pdo->beginTransaction();
        if ($id > 0) {
            if ($dados[':padrao_loja'] === 1) {
                $stmtPadrao = $pdo->prepare('UPDATE trajetos_trabalho SET padrao_loja = 0 WHERE loja_id = :loja_id AND id <> :id');
                $stmtPadrao->execute([':loja_id'=>$dados[':loja_id'], ':id'=>$id]);
            }
            $trajetoAntes = $trajetoEditar ?: [];
            $dados[':id'] = $id;
            $stmt = $pdo->prepare(
                'UPDATE trajetos_trabalho
                 SET loja_id = :loja_id, nome_trajeto = :nome_trajeto, tipo_transporte = :tipo_transporte,
                     valor_ida = :valor_ida, valor_volta = :valor_volta, valor_total = :valor_total,
                     tempo_medio = :tempo_medio, padrao_loja = :padrao_loja,
                     observacoes = :observacoes, ativo = :ativo
                 WHERE id = :id'
            );
            $stmt->execute($dados);
            ponto_historico($pdo, 'trajetos_trabalho', $id, 'alteracao_trajeto', $trajetoAntes, $dados);
            if ((float)($trajetoAntes['valor_total'] ?? 0) !== (float)$dadosAuditoria['valor_total']) {
                ponto_historico($pdo, 'trajetos_trabalho', $id, 'alteracao_tarifa', $trajetoAntes, $dados);
            }
            $acaoAuditoria = 'trajeto_editado';
            if ($trajetoAntes && (int) ($trajetoAntes['ativo'] ?? 0) !== (int) $dadosAuditoria['ativo']) {
                $acaoAuditoria = (int) $dadosAuditoria['ativo'] === 1 ? 'trajeto_ativado' : 'trajeto_desativado';
            }
            registrar_auditoria('trajetos', $acaoAuditoria, 'trajetos_trabalho', $id, $trajetoAntes, $dadosAuditoria, 'sucesso', null, 'Trajeto atualizado');
            $sucesso = 'Trajeto atualizado.';
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO trajetos_trabalho
                 (loja_id, nome_trajeto, tipo_transporte, valor_ida, valor_volta, valor_total, tempo_medio, padrao_loja, observacoes, ativo)
                 VALUES
                 (:loja_id, :nome_trajeto, :tipo_transporte, :valor_ida, :valor_volta, :valor_total, :tempo_medio, :padrao_loja, :observacoes, :ativo)'
            );
            $stmt->execute($dados);
            $novoTrajetoId = (int)$pdo->lastInsertId();
            if ($dados[':padrao_loja'] === 1) {
                $stmtPadrao = $pdo->prepare('UPDATE trajetos_trabalho SET padrao_loja = 0 WHERE loja_id = :loja_id AND id <> :id');
                $stmtPadrao->execute([':loja_id'=>$dados[':loja_id'], ':id'=>$novoTrajetoId]);
            }
            ponto_historico($pdo, 'trajetos_trabalho', $novoTrajetoId, 'criacao', [], $dados);
            registrar_auditoria('trajetos', 'trajeto_criado', 'trajetos_trabalho', $novoTrajetoId, [], $dadosAuditoria, 'sucesso', null, 'Trajeto cadastrado');
            $sucesso = 'Trajeto cadastrado.';
        }
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        registrar_auditoria('trajetos', 'erro_salvar', 'trajetos_trabalho', (int) ($_POST['id'] ?? 0) ?: null, $trajetoEditar ?: [], $_POST, 'erro', $e->getMessage(), 'Falha ao salvar trajeto');
        $erro = ponto_mensagem_erro($e);
    }
}

$trajetos = $pdo->query(
    'SELECT t.*, l.codigo_loja, l.nome AS loja_nome
     FROM trajetos_trabalho t
     INNER JOIN lojas_trabalho l ON l.id = t.loja_id
     WHERE t.ativo = 1
     ORDER BY t.ativo DESC, l.codigo_loja, t.nome_trajeto'
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Trajetos | SONI PONTO</title>
  <link rel="stylesheet" href="<?= e(asset_url('assets/css/style.css')) ?>">
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
            <option value="<?= (int) $loja['id'] ?>" <?= (int) ($trajetoEditar['loja_id'] ?? 0) === (int) $loja['id'] ? 'selected' : '' ?>><?= e((string) $loja['codigo_loja']) ?> - <?= e((string) $loja['nome']) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>Nome do trajeto<input name="nome_trajeto" placeholder="Econômico, Rápido, Via Aracaré..." value="<?= e((string) ($trajetoEditar['nome_trajeto'] ?? '')) ?>" required></label>
      <label>Tipo de transporte<input name="tipo_transporte" value="<?= e((string) ($trajetoEditar['tipo_transporte'] ?? '')) ?>" placeholder="Ônibus, trem, metrô..."></label>
      <label>Valor ida<input name="valor_ida" inputmode="decimal" value="<?= e((string) ($trajetoEditar['valor_ida'] ?? '0.00')) ?>"></label>
      <label>Valor volta<input name="valor_volta" inputmode="decimal" value="<?= e((string) ($trajetoEditar['valor_volta'] ?? '0.00')) ?>"></label>
      <label>Total diário<input name="valor_total" inputmode="decimal" value="<?= e((string) ($trajetoEditar['valor_total'] ?? '0.00')) ?>"><small>O back-end confirma ida + volta.</small></label>
      <label>Tempo médio<input name="tempo_medio" value="<?= e((string) ($trajetoEditar['tempo_medio'] ?? '')) ?>"></label>
      <label class="full">Observações<textarea name="observacoes" rows="3"><?= e((string) ($trajetoEditar['observacoes'] ?? '')) ?></textarea></label>
      <label class="ponto-checkbox"><input type="checkbox" name="ativo" <?= (int) ($trajetoEditar['ativo'] ?? 1) === 1 ? 'checked' : '' ?>> Ativo</label>
      <label class="ponto-checkbox"><input type="checkbox" name="padrao_loja" <?= (int) ($trajetoEditar['padrao_loja'] ?? 0) === 1 ? 'checked' : '' ?>> Trajeto padrão da loja</label>
      <button type="submit"><?= $trajetoEditar ? 'Salvar trajeto' : 'Cadastrar trajeto' ?></button>
    </form>

    <section class="panel">
      <h2>Trajetos cadastrados</h2>
      <div class="table-wrap">
        <table>
          <thead><tr><th>Loja</th><th>Trajeto</th><th>Valores</th><th>Observações</th><th>Status</th><th>Ações</th></tr></thead>
          <tbody>
            <?php if (!$trajetos): ?><tr><td colspan="6">Nenhum trajeto cadastrado.</td></tr><?php endif; ?>
            <?php foreach ($trajetos as $trajeto): ?>
              <tr>
                <td><?= e((string) $trajeto['codigo_loja']) ?><br><small><?= e((string) $trajeto['loja_nome']) ?></small></td>
                <td><?= e((string) $trajeto['nome_trajeto']) ?></td>
                <td>Ida <?= e(ponto_moeda((float)$trajeto['valor_ida'])) ?><br>Volta <?= e(ponto_moeda((float)$trajeto['valor_volta'])) ?><br><strong><?= e(ponto_moeda((float)$trajeto['valor_total'])) ?></strong></td>
                <td><?= e((string) ($trajeto['observacoes'] ?? '')) ?></td>
                <td><span class="status <?= (int) $trajeto['ativo'] === 1 ? 'status-concluido' : 'status-expirado' ?>"><?= (int) $trajeto['ativo'] === 1 ? 'ativo' : 'inativo' ?></span></td>
                <td><a href="trajetos.php?editar=<?= (int) $trajeto['id'] ?>">Editar</a> · <a href="trechos.php?trajeto_id=<?= (int)$trajeto['id'] ?>">Trechos</a></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>
  </main>
</body>
</html>
