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
$configuracao = ponto_configuracao_vigente($pdo, sprintf('%04d-%02d-01', $ano, $mes));
$minutosPrevistos = isset($configuracao['minutos_jornada']) ? (int) $configuracao['minutos_jornada'] : null;
$diasNoMes = cal_days_in_month(CAL_GREGORIAN, $mes, $ano);
$resumo = ponto_resumo_registros($pontos, $minutosPrevistos, $diasNoMes);
$stmtDireitos = $pdo->prepare(
    "SELECT tipo, situacao, COALESCE(SUM(quantidade), 0) total
     FROM ponto_direitos WHERE YEAR(data_aquisicao) <= :ano GROUP BY tipo, situacao"
);
$stmtDireitos->execute([':ano' => $ano]);
$direitos = [];
foreach ($stmtDireitos->fetchAll() as $direito) {
    $direitos[$direito['tipo']][$direito['situacao']] = (float) $direito['total'];
}
$escala = ponto_escala_domingo($pdo, sprintf('%04d-%02d-%02d', $ano, $mes, $diasNoMes));
$stmtReembolsos = $pdo->prepare(
    'SELECT COALESCE(SUM(diferenca_calculada),0) calculado,
            COALESCE(SUM(valor_solicitado),0) solicitado,
            COALESCE(SUM(valor_aprovado),0) aprovado,
            COALESCE(SUM(valor_reembolsado),0) reembolsado,
            COALESCE(SUM(GREATEST(IF(valor_aprovado>0,valor_aprovado,diferenca_calculada)-valor_reembolsado,0)),0) saldo_pendente
     FROM ponto_reembolsos_transporte r
     INNER JOIN pontos_trabalho p ON p.id=r.ponto_id
     WHERE YEAR(p.data)=:ano AND MONTH(p.data)=:mes'
);
$stmtReembolsos->execute([':ano'=>$ano, ':mes'=>$mes]);
$reembolsosResumo = $stmtReembolsos->fetch() ?: ['calculado'=>0,'solicitado'=>0,'aprovado'=>0,'reembolsado'=>0];
$saldoReembolso = (float)($reembolsosResumo['saldo_pendente'] ?? 0);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Resumo mensal | SONI PONTO</title>
  <link rel="stylesheet" href="<?= e(asset_url('assets/css/style.css')) ?>">
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
      <article class="metric-card"><span>Dias remunerados*</span><strong><?= (int) $resumo['dias_remunerados'] ?></strong></article>
      <article class="metric-card"><span>Folgas semanais</span><strong><?= $contadoresStatus['folga_semanal'] ?></strong></article>
      <article class="metric-card"><span>Folgas de domingo</span><strong><?= $contadoresStatus['folga_domingo'] ?></strong></article>
      <article class="metric-card"><span>Integrações</span><strong><?= $contadoresStatus['integracao_treinamento'] ?></strong></article>
      <article class="metric-card"><span>Feriados</span><strong><?= $contadoresStatus['feriado'] ?></strong></article>
      <article class="metric-card"><span>Feriados folgados</span><strong><?= (int) $resumo['feriados_folgados'] ?></strong></article>
      <article class="metric-card"><span>Feriados trabalhados</span><strong><?= (int) $resumo['feriados_trabalhados'] ?></strong></article>
      <article class="metric-card"><span>Atestados</span><strong><?= $contadoresStatus['atestado'] ?></strong></article>
      <article class="metric-card"><span>Faltas</span><strong><?= $contadoresStatus['falta'] ?></strong></article>
      <article class="metric-card"><span>Férias</span><strong><?= $contadoresStatus['ferias'] ?></strong></article>
      <article class="metric-card"><span>Horas líquidas</span><strong><?= e(ponto_formatar_minutos($totalLiquido)) ?></strong></article>
      <article class="metric-card"><span>Horas extras</span><strong><?= e(ponto_formatar_minutos((int) $resumo['horas_extras'])) ?></strong></article>
      <article class="metric-card"><span>Horas negativas</span><strong><?= e(ponto_formatar_minutos((int) $resumo['horas_negativas'])) ?></strong></article>
      <article class="metric-card"><span>Transporte</span><strong><?= e(ponto_moeda($totalTransporte)) ?></strong></article>
      <article class="metric-card"><span>Transporte previsto</span><strong><?= e(ponto_moeda((float) $resumo['transporte_previsto'])) ?></strong></article>
      <article class="metric-card"><span>Transporte recebido</span><strong><?= e(ponto_moeda((float) $resumo['transporte_recebido'])) ?></strong></article>
      <article class="metric-card"><span>Saldo de vale-transporte</span><strong><?= e(ponto_moeda((float) $resumo['economia'])) ?></strong></article>
      <article class="metric-card"><span>Total pago do próprio bolso</span><strong><?= e(ponto_moeda((float) $resumo['proprio_bolso'])) ?></strong></article>
      <article class="metric-card"><span>Diferenças calculadas</span><strong><?= e(ponto_moeda((float)$reembolsosResumo['calculado'])) ?></strong></article>
      <article class="metric-card"><span>Total solicitado</span><strong><?= e(ponto_moeda((float)$reembolsosResumo['solicitado'])) ?></strong></article>
      <article class="metric-card"><span>Total aprovado</span><strong><?= e(ponto_moeda((float)$reembolsosResumo['aprovado'])) ?></strong></article>
      <article class="metric-card"><span>Total reembolsado</span><strong><?= e(ponto_moeda((float)$reembolsosResumo['reembolsado'])) ?></strong></article>
      <article class="metric-card"><span>Saldo pendente</span><strong><?= e(ponto_moeda($saldoReembolso)) ?></strong></article>
      <article class="metric-card"><span>Dias sem registro</span><strong><?= (int) $resumo['dias_sem_registro'] ?></strong></article>
      <article class="metric-card destaque"><span>Valor perdido</span><strong><?= e(ponto_moeda($totalValorBilhetes)) ?></strong></article>
    </div>
    <p><small>* Estimativa administrativa conforme a regra vigente; não constitui conclusão jurídica.
      <?= $minutosPrevistos === null ? 'Jornada não configurada: extras e saldo aguardam configuração.' : 'Jornada prevista: ' . e(ponto_formatar_minutos($minutosPrevistos)) . '.' ?></small></p>

    <section class="panel">
      <h2>Ciclo de domingos</h2>
      <p><strong><?= min(2, (int) $escala['trabalhados_no_ciclo']) ?> de 2</strong> domingos trabalhados.
      Próximo domingo: <?= e(date('d/m/Y', strtotime($escala['proximo_domingo']))) ?> —
      <?= $escala['folga_prevista'] ? 'folga prevista' : 'trabalho previsto' ?>.</p>
    </section>

    <section class="panel">
      <h2>Direitos compensatórios</h2>
      <div class="table-wrap"><table>
        <thead><tr><th>Direito</th><th>Adquiridas</th><th>Agendadas</th><th>Utilizadas</th><th>Pendentes</th></tr></thead>
        <tbody><?php foreach (['folga_domingo'=>'Folga de domingo','folga_feriado'=>'Folga de feriado'] as $tipo=>$label): ?>
          <tr><td><?= e($label) ?></td><td><?= (float) array_sum($direitos[$tipo] ?? []) ?></td>
          <td><?= (float) ($direitos[$tipo]['agendada'] ?? 0) ?></td><td><?= (float) ($direitos[$tipo]['utilizada'] ?? 0) ?></td>
          <td><?= (float) ($direitos[$tipo]['pendente'] ?? 0) ?></td></tr>
        <?php endforeach; ?></tbody>
      </table></div>
    </section>

    <section class="panel">
      <h2>Linha do tempo mensal</h2>
      <div class="ponto-timeline"><?php foreach ($pontos as $dia): ?>
        <a class="status-dia status-<?= e((string) $dia['status_dia']) ?>" href="editar.php?id=<?= (int) $dia['id'] ?>"
          title="<?= e(ponto_status_dia_label($dia['status_dia'])) ?>"><?= e(date('d', strtotime($dia['data']))) ?></a>
      <?php endforeach; ?></div>
      <?php if ($resumo['alertas']): ?><h3>Alertas de jornada</h3><ul>
        <?php foreach ($resumo['alertas'] as $alerta): ?><li><?= e(date('d/m/Y', strtotime($alerta['data']))) ?>: <?= e(implode(' ', $alerta['mensagens'])) ?></li><?php endforeach; ?>
      </ul><?php endif; ?>
    </section>

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
