<?php
declare(strict_types=1);

require_once __DIR__ . '/_funcoes.php';

exigir_permissao('resumo.visualizar');

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
     ORDER BY p.data'
);
$stmt->execute($params);
$pontos = $stmt->fetchAll();

$porLoja = [];
$totalLiquido = 0;
$totalTransporte = 0.0;
$totalBilhetes = 0;
$totalValorBilhetes = 0.0;
$contadoresStatus = [
    'trabalhado' => 0,
    'folga_semanal' => 0,
    'folga_domingo' => 0,
    'integracao_treinamento' => 0,
    'feriado' => 0,
    'atestado' => 0,
    'falta' => 0,
    'ferias' => 0,
];

foreach ($pontos as $ponto) {
    $status = (string) ($ponto['status_dia'] ?? 'trabalhado');

    if (isset($contadoresStatus[$status])) {
        $contadoresStatus[$status]++;
    }

    if (!ponto_dia_trabalhado($status)) {
        continue;
    }

    $calculo = ponto_calcular($ponto);
    $lojaChave = $ponto['codigo_loja']
        ? 'Loja ' . $ponto['codigo_loja'] . ' - ' . $ponto['loja_nome']
        : 'Sem loja';

    if (!isset($porLoja[$lojaChave])) {
        $porLoja[$lojaChave] = ['dias' => 0, 'liquido' => 0, 'transporte' => 0.0];
    }

    $porLoja[$lojaChave]['dias']++;
    $porLoja[$lojaChave]['liquido'] += $calculo['liquido'] ?? 0;
    $porLoja[$lojaChave]['transporte'] += (float) $ponto['gasto_transporte'];
    $totalLiquido += $calculo['liquido'] ?? 0;
    $totalTransporte += (float) $ponto['gasto_transporte'];
    $totalBilhetes += (int) $ponto['bilhetes_perdidos'];
    $totalValorBilhetes += (float) $ponto['valor_bilhetes_perdidos'];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Resumo mensal | SONI PONTO</title>
  <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
  <?php ponto_render_header('SONI PONTO'); ?>

  <main class="app-container">
    <section class="page-title">
      <span>SONI PONTO</span>
      <h1>Resumo mensal</h1>
    </section>

    <?php ponto_render_nav('resumo.php'); ?>

    <section class="panel">
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
    </section>

    <div class="metrics-grid">
      <article class="metric-card"><span>Dias trabalhados</span><strong><?= $contadoresStatus['trabalhado'] ?></strong></article>
      <article class="metric-card"><span>Folgas semanais</span><strong><?= $contadoresStatus['folga_semanal'] ?></strong></article>
      <article class="metric-card"><span>Folgas de domingo</span><strong><?= $contadoresStatus['folga_domingo'] ?></strong></article>
      <article class="metric-card"><span>Integrações</span><strong><?= $contadoresStatus['integracao_treinamento'] ?></strong></article>
      <article class="metric-card"><span>Feriados</span><strong><?= $contadoresStatus['feriado'] ?></strong></article>
      <article class="metric-card"><span>Atestados</span><strong><?= $contadoresStatus['atestado'] ?></strong></article>
      <article class="metric-card"><span>Faltas</span><strong><?= $contadoresStatus['falta'] ?></strong></article>
      <article class="metric-card"><span>Férias</span><strong><?= $contadoresStatus['ferias'] ?></strong></article>
      <article class="metric-card"><span>Horas líquidas</span><strong><?= e(ponto_formatar_minutos($totalLiquido)) ?></strong></article>
      <article class="metric-card"><span>Transporte</span><strong><?= e(ponto_moeda($totalTransporte)) ?></strong></article>
      <article class="metric-card destaque"><span>Valor perdido</span><strong><?= e(ponto_moeda($totalValorBilhetes)) ?></strong></article>
    </div>

    <section class="panel">
      <div class="panel-head">
        <h2>Total por loja</h2>
        <span><?= $totalBilhetes ?> bilhete(s) perdido(s)</span>
      </div>
      <div class="table-wrap">
        <table>
          <thead><tr><th>Loja</th><th>Dias</th><th>Horas líquidas</th><th>Transporte</th></tr></thead>
          <tbody>
            <?php if (!$porLoja): ?><tr><td colspan="4">Nenhum registro trabalhado no período.</td></tr><?php endif; ?>
            <?php foreach ($porLoja as $loja => $dados): ?>
              <tr>
                <td><?= e($loja) ?></td>
                <td><?= (int) $dados['dias'] ?></td>
                <td><?= e(ponto_formatar_minutos((int) $dados['liquido'])) ?></td>
                <td><?= e(ponto_moeda((float) $dados['transporte'])) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>
  </main>
</body>
</html>
