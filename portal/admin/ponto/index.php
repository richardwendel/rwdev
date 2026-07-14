<?php
declare(strict_types=1);

require_once __DIR__ . '/_funcoes.php';

$mes = max(1, min(12, (int) ($_GET['mes'] ?? date('n'))));
$ano = max(2020, min(2100, (int) ($_GET['ano'] ?? date('Y'))));
$lojaFiltro = (int) ($_GET['loja_id'] ?? 0);
$lojas = ponto_lojas($pdo, false);

$where = ['MONTH(p.data) = :mes', 'YEAR(p.data) = :ano'];
$params = [':mes' => $mes, ':ano' => $ano];

if ($lojaFiltro > 0) {
    $where[] = 'p.loja_id = :loja_id';
    $params[':loja_id'] = $lojaFiltro;
}

$stmt = $pdo->prepare(
    'SELECT p.*, l.codigo_loja, l.nome AS loja_nome
     FROM pontos_trabalho p
     LEFT JOIN lojas_trabalho l ON l.id = p.loja_id
     WHERE ' . implode(' AND ', $where) . '
     ORDER BY p.data DESC, p.entrada DESC, p.id DESC'
);
$stmt->execute($params);
$pontos = $stmt->fetchAll();

$totalLiquido = 0;
$totalTransporte = 0.0;
$totalBilhetes = 0;
$totalValorBilhetes = 0.0;
$diasTrabalhados = 0;

foreach ($pontos as $pontoResumo) {
    if (!ponto_dia_trabalhado($pontoResumo['status_dia'] ?? 'trabalhado')) {
        continue;
    }

    $calculoResumo = ponto_calcular($pontoResumo);
    $diasTrabalhados++;
    $totalLiquido += $calculoResumo['liquido'] ?? 0;
    $totalTransporte += (float) $pontoResumo['gasto_transporte'];
    $totalBilhetes += (int) $pontoResumo['bilhetes_perdidos'];
    $totalValorBilhetes += (float) $pontoResumo['valor_bilhetes_perdidos'];
}

$stmtUltimo = $pdo->query('SELECT id FROM pontos_trabalho ORDER BY data DESC, id DESC LIMIT 1');
$ultimoPontoId = (int) ($stmtUltimo->fetchColumn() ?: 0);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SONI PONTO | Admin RWDEV</title>
  <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
  <?php ponto_render_header('SONI PONTO'); ?>

  <main class="app-container">
    <section class="page-title">
      <span>Administração interna</span>
      <h1>SONI PONTO</h1>
    </section>

    <?php ponto_render_nav('index.php'); ?>

    <div class="metrics-grid">
      <article class="metric-card"><span>Dias trabalhados</span><strong><?= $diasTrabalhados ?></strong></article>
      <article class="metric-card"><span>Horas líquidas</span><strong><?= e(ponto_formatar_minutos($totalLiquido)) ?></strong></article>
      <article class="metric-card"><span>Transporte</span><strong><?= e(ponto_moeda($totalTransporte)) ?></strong></article>
      <article class="metric-card destaque"><span>Bilhetes perdidos</span><strong><?= $totalBilhetes ?></strong></article>
    </div>

    <section class="panel">
      <div class="panel-head">
        <h2>Registros de ponto</h2>
        <div class="ponto-actions">
          <a class="btn" href="novo.php">Novo ponto</a>
          <?php if ($ultimoPontoId): ?><a class="btn outline" href="novo.php?duplicar=<?= $ultimoPontoId ?>">Duplicar último</a><?php endif; ?>
          <a class="btn outline" href="resumo.php?mes=<?= $mes ?>&ano=<?= $ano ?>&loja_id=<?= $lojaFiltro ?>">Resumo</a>
        </div>
      </div>

      <form class="ponto-filtros" method="get">
        <label>Mês
          <select name="mes">
            <?php for ($m = 1; $m <= 12; $m++): ?>
              <option value="<?= $m ?>" <?= $m === $mes ? 'selected' : '' ?>><?= str_pad((string) $m, 2, '0', STR_PAD_LEFT) ?></option>
            <?php endfor; ?>
          </select>
        </label>
        <label>Ano<input name="ano" inputmode="numeric" value="<?= $ano ?>"></label>
        <label>Loja
          <select name="loja_id">
            <option value="0">Todas</option>
            <?php foreach ($lojas as $loja): ?>
              <option value="<?= (int) $loja['id'] ?>" <?= (int) $loja['id'] === $lojaFiltro ? 'selected' : '' ?>>Loja <?= e((string) $loja['codigo_loja']) ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <button class="btn small" type="submit">Filtrar</button>
      </form>

      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Data</th><th>Status</th><th>Loja</th><th>Entrada</th><th>Saída</th><th>Café</th><th>Almoço</th><th>Permanência</th><th>Líquido</th><th>Transporte</th><th>Ações</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!$pontos): ?>
              <tr><td colspan="11">Nenhum ponto encontrado para o filtro.</td></tr>
            <?php endif; ?>
            <?php foreach ($pontos as $ponto): ?>
              <?php $calculo = ponto_calcular($ponto); ?>
              <?php $diaTrabalhado = ponto_dia_trabalhado($ponto['status_dia'] ?? 'trabalhado'); ?>
              <tr>
                <td><?= date('d/m/Y', strtotime($ponto['data'])) ?><br><small><?= e($ponto['dia_semana']) ?></small></td>
                <td><?= e(ponto_status_dia_label($ponto['status_dia'] ?? 'trabalhado')) ?></td>
                <td><?php if ($ponto['codigo_loja']): ?>Loja <?= e((string) $ponto['codigo_loja']) ?><br><small><?= e((string) $ponto['loja_nome']) ?></small><?php else: ?>-<?php endif; ?></td>
                <td title="<?= e(ponto_formatar_hora_completa($ponto['entrada'])) ?>"><?= $diaTrabalhado ? e(ponto_formatar_hora($ponto['entrada'])) : '-' ?><?php if ($diaTrabalhado && ponto_hora_tem_segundos($ponto['entrada'])): ?><br><small><?= e(ponto_formatar_hora_completa($ponto['entrada'])) ?></small><?php endif; ?></td>
                <td title="<?= e(ponto_formatar_hora_completa($ponto['saida'])) ?>"><?= $diaTrabalhado ? e(ponto_formatar_hora($ponto['saida'])) : '-' ?><?php if ($diaTrabalhado && ponto_hora_tem_segundos($ponto['saida'])): ?><br><small><?= e(ponto_formatar_hora_completa($ponto['saida'])) ?></small><?php endif; ?></td>
                <td><?= e(ponto_formatar_minutos($calculo['cafe'])) ?></td>
                <td><?= e(ponto_formatar_minutos($calculo['almoco'])) ?></td>
                <td><?= e(ponto_formatar_minutos($calculo['permanencia'])) ?></td>
                <td><strong><?= e(ponto_formatar_minutos($calculo['liquido'])) ?></strong></td>
                <td><?= $diaTrabalhado ? e(ponto_moeda((float) $ponto['gasto_transporte'])) : '-' ?></td>
                <td class="ponto-table-actions">
                  <a href="editar.php?id=<?= (int) $ponto['id'] ?>">Editar</a>
                  <a href="novo.php?duplicar=<?= (int) $ponto['id'] ?>">Duplicar</a>
                  <a class="danger-link" href="excluir.php?id=<?= (int) $ponto['id'] ?>">Excluir</a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>
  </main>
</body>
</html>
