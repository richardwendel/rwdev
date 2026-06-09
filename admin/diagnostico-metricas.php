<?php
declare(strict_types=1);

require_once __DIR__ . '/../portal/config/conexao.php';
require_once __DIR__ . '/../portal/includes/funcoes.php';

if (empty($_SESSION['admin_id'])) {
    redirect('/portal/admin/login.php');
}

function contador_metrica(PDO $pdo, string $sql, array $params = []): int
{
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return (int) $stmt->fetchColumn();
}

function porcentagem_metrica(int $parte, int $total): float
{
    if ($total <= 0) {
        return 0.0;
    }

    return round(($parte / $total) * 100, 1);
}

function formatar_percentual(float $valor): string
{
    return number_format($valor, 1, ',', '.') . '%';
}

function classificar_origem(?string $referer): string
{
    $referer = strtolower(trim((string) $referer));

    if ($referer === '') {
        return 'Direto';
    }

    $host = parse_url($referer, PHP_URL_HOST);
    $base = $host ? strtolower((string) $host) : $referer;

    if (str_contains($base, 'google.')) {
        return 'Google';
    }

    if (str_contains($base, 'instagram.')) {
        return 'Instagram';
    }

    if (str_contains($base, 'facebook.') || str_contains($base, 'fb.')) {
        return 'Facebook';
    }

    if (str_contains($base, 'linkedin.')) {
        return 'LinkedIn';
    }

    if (str_contains($base, 'whatsapp.') || str_contains($base, 'wa.me')) {
        return 'WhatsApp';
    }

    return 'Outros';
}

function rotulo_evento(string $evento): string
{
    return [
        'page_view' => 'Visita unica',
        'diagnosis_start' => 'Diagnostico iniciado',
        'diagnosis_completed' => 'Diagnostico concluido',
        'whatsapp_click' => 'Clique no WhatsApp',
    ][$evento] ?? $evento;
}

function hash_curto(?string $hash): string
{
    $hash = (string) $hash;

    if (strlen($hash) <= 20) {
        return $hash;
    }

    return substr($hash, 0, 16) . '...';
}

$periodoInicio = 'DATE_SUB(NOW(), INTERVAL 30 DAY)';

$visitasHoje = contador_metrica(
    $pdo,
    "SELECT COUNT(*) FROM diagnostico_eventos
     WHERE event_type = 'page_view'
       AND created_at >= CURDATE()
       AND created_at < DATE_ADD(CURDATE(), INTERVAL 1 DAY)"
);
$visitas7Dias = contador_metrica(
    $pdo,
    "SELECT COUNT(*) FROM diagnostico_eventos
     WHERE event_type = 'page_view'
       AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)"
);
$visitas30Dias = contador_metrica(
    $pdo,
    "SELECT COUNT(*) FROM diagnostico_eventos
     WHERE event_type = 'page_view'
       AND created_at >= {$periodoInicio}"
);
$diagnosticosIniciados = contador_metrica(
    $pdo,
    "SELECT COUNT(*) FROM diagnostico_eventos
     WHERE event_type = 'diagnosis_start'
       AND created_at >= {$periodoInicio}"
);
$diagnosticosConcluidos = contador_metrica(
    $pdo,
    "SELECT COUNT(*) FROM diagnostico_eventos
     WHERE event_type = 'diagnosis_completed'
       AND created_at >= {$periodoInicio}"
);
$cliquesWhatsapp = contador_metrica(
    $pdo,
    "SELECT COUNT(*) FROM diagnostico_eventos
     WHERE event_type = 'whatsapp_click'
       AND created_at >= {$periodoInicio}"
);

$taxaInicio = porcentagem_metrica($diagnosticosIniciados, $visitas30Dias);
$taxaConclusao = porcentagem_metrica($diagnosticosConcluidos, $diagnosticosIniciados);
$taxaCliqueWhatsapp = porcentagem_metrica($cliquesWhatsapp, $diagnosticosConcluidos);
$taxaConversaoGeral = porcentagem_metrica($cliquesWhatsapp, $visitas30Dias);

$origens = [
    'Google' => 0,
    'Instagram' => 0,
    'Facebook' => 0,
    'LinkedIn' => 0,
    'WhatsApp' => 0,
    'Direto' => 0,
    'Outros' => 0,
];

$stmtOrigens = $pdo->prepare(
    "SELECT referer, COUNT(*) AS total
     FROM diagnostico_eventos
     WHERE event_type = 'page_view'
       AND created_at >= {$periodoInicio}
     GROUP BY referer"
);
$stmtOrigens->execute();

foreach ($stmtOrigens->fetchAll() as $linhaOrigem) {
    $origem = classificar_origem($linhaOrigem['referer'] ?? '');
    $origens[$origem] += (int) $linhaOrigem['total'];
}

$totalOrigens = array_sum($origens);
arsort($origens);
$origemPrincipal = array_key_first($origens);

$insights = [
    formatar_percentual($taxaInicio) . ' dos visitantes iniciaram o diagnostico.',
    formatar_percentual(porcentagem_metrica($diagnosticosConcluidos, $visitas30Dias)) . ' dos visitantes concluiram o diagnostico.',
    formatar_percentual($taxaConversaoGeral) . ' dos visitantes clicaram no WhatsApp.',
];

if (($origens['Instagram'] ?? 0) > ($origens['Google'] ?? 0)) {
    $insights[] = 'Instagram gerou mais trafego que Google.';
} elseif (($origens['Google'] ?? 0) > 0) {
    $insights[] = 'Google esta entre as principais origens de trafego.';
}

if ($taxaConclusao >= 50.0) {
    $insights[] = 'Pagina possui boa retencao no diagnostico.';
} elseif ($visitas30Dias > 0 && $taxaConclusao < 25.0) {
    $insights[] = 'Alerta: a taxa de conclusao esta baixa e merece acompanhamento.';
}

if ($taxaConversaoGeral < 5.0 && $visitas30Dias >= 10) {
    $insights[] = 'Alerta: poucos visitantes estao chegando ao WhatsApp.';
}

$itensPorPagina = 12;
$paginaAtual = max(1, (int) ($_GET['pagina'] ?? 1));
$totalEventos = contador_metrica($pdo, 'SELECT COUNT(*) FROM diagnostico_eventos');
$totalPaginas = max(1, (int) ceil($totalEventos / $itensPorPagina));
$paginaAtual = min($paginaAtual, $totalPaginas);
$offset = ($paginaAtual - 1) * $itensPorPagina;

$stmtEventos = $pdo->prepare(
    'SELECT event_type, page, referer, ip_hash, user_agent_hash, created_at
     FROM diagnostico_eventos
     ORDER BY created_at DESC
     LIMIT :limite OFFSET :offset'
);
$stmtEventos->bindValue(':limite', $itensPorPagina, PDO::PARAM_INT);
$stmtEventos->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmtEventos->execute();
$eventosRecentes = $stmtEventos->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex, nofollow">
  <title>Metricas do Diagnostico | RWDEV Admin</title>
  <link rel="stylesheet" href="/portal/assets/css/style.css">
  <style>
    .diagnostico-admin-grid {
      display: grid;
      grid-template-columns: repeat(3, minmax(0, 1fr));
      gap: 14px;
      margin-bottom: 22px;
    }

    .diagnostico-admin-grid .metric-card small,
    .funil-card small {
      color: var(--muted);
      display: block;
      font-size: 12px;
      font-weight: 700;
      margin-top: 6px;
    }

    .funil-grid {
      display: grid;
      grid-template-columns: repeat(4, minmax(0, 1fr));
      gap: 12px;
    }

    .funil-card,
    .origem-item,
    .insight-item {
      background: #f8fafc;
      border: 1px solid var(--line);
      border-radius: var(--radius);
      padding: 16px;
    }

    .funil-card strong {
      display: block;
      font-size: 28px;
      margin-top: 8px;
    }

    .funil-taxas {
      display: grid;
      grid-template-columns: repeat(3, minmax(0, 1fr));
      gap: 12px;
      margin-top: 14px;
    }

    .origens-grid {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 12px;
    }

    .origem-topo {
      align-items: center;
      display: flex;
      justify-content: space-between;
      gap: 12px;
      margin-bottom: 10px;
    }

    .origem-barra {
      background: #e5eaf3;
      border-radius: 999px;
      height: 8px;
      overflow: hidden;
    }

    .origem-barra span {
      background: linear-gradient(135deg, var(--primary), var(--gold));
      display: block;
      height: 100%;
    }

    .insights-list {
      display: grid;
      gap: 10px;
    }

    .insight-item {
      color: var(--ink);
      font-weight: 700;
      margin: 0;
    }

    .evento-tipo {
      background: #e0edff;
      border-radius: 999px;
      color: #0753bd;
      display: inline-flex;
      font-size: 12px;
      font-weight: 800;
      padding: 6px 9px;
      white-space: nowrap;
    }

    .hash-cell {
      color: var(--muted);
      font-family: Consolas, Monaco, monospace;
      font-size: 12px;
    }

    .paginacao-admin {
      display: flex;
      flex-wrap: wrap;
      gap: 8px;
      justify-content: flex-end;
      margin-top: 16px;
    }

    .paginacao-admin a,
    .paginacao-admin span {
      align-items: center;
      border: 1px solid var(--line);
      border-radius: var(--radius);
      display: inline-flex;
      font-weight: 800;
      min-height: 38px;
      padding: 8px 12px;
    }

    .paginacao-admin span {
      background: var(--primary-dark);
      color: #fff;
    }

    @media (max-width: 920px) {
      .diagnostico-admin-grid,
      .funil-grid,
      .funil-taxas,
      .origens-grid {
        grid-template-columns: 1fr;
      }
    }
  </style>
</head>
<body>
  <header class="app-header admin">
    <a href="/portal/admin/dashboard.php" class="marca">RWDEV Admin</a>
    <nav>
      <a href="/portal/admin/dashboard.php">Dashboard</a>
      <a href="/portal/admin/clientes.php">Clientes</a>
      <a href="/portal/admin/depoimentos.php">Depoimentos</a>
      <a href="/admin/diagnostico-metricas.php">Diagnostico</a>
      <a href="/portal/logout.php">Sair</a>
    </nav>
  </header>

  <main class="app-container">
    <section class="page-title">
      <span>Ferramenta /diagnostico</span>
      <h1>Metricas do diagnostico</h1>
    </section>

    <div class="diagnostico-admin-grid">
      <article class="metric-card"><span>&#128202; Visitas Unicas Hoje</span><strong><?= $visitasHoje ?></strong><small>page_view unico por visitante/dia</small></article>
      <article class="metric-card"><span>&#128202; Visitas Unicas nos Ultimos 7 Dias</span><strong><?= $visitas7Dias ?></strong><small>ultimos 7 dias</small></article>
      <article class="metric-card"><span>&#128202; Visitas Unicas nos Ultimos 30 Dias</span><strong><?= $visitas30Dias ?></strong><small>ultimos 30 dias</small></article>
      <article class="metric-card"><span>&#128202; Diagnosticos Iniciados</span><strong><?= $diagnosticosIniciados ?></strong><small>ultimos 30 dias</small></article>
      <article class="metric-card"><span>&#128202; Diagnosticos Concluidos</span><strong><?= $diagnosticosConcluidos ?></strong><small>ultimos 30 dias</small></article>
      <article class="metric-card destaque"><span>&#128202; Cliques no WhatsApp</span><strong><?= $cliquesWhatsapp ?></strong><small>ultimos 30 dias</small></article>
    </div>

    <section class="panel">
      <div class="panel-head">
        <h2>Funil de conversao</h2>
        <span>Ultimos 30 dias</span>
      </div>

      <div class="funil-grid">
        <article class="funil-card"><span>Visitantes</span><strong><?= $visitas30Dias ?></strong></article>
        <article class="funil-card"><span>Iniciaram diagnostico</span><strong><?= $diagnosticosIniciados ?></strong><small><?= formatar_percentual($taxaInicio) ?> dos visitantes</small></article>
        <article class="funil-card"><span>Concluiram diagnostico</span><strong><?= $diagnosticosConcluidos ?></strong><small><?= formatar_percentual($taxaConclusao) ?> dos iniciados</small></article>
        <article class="funil-card"><span>Clicaram no WhatsApp</span><strong><?= $cliquesWhatsapp ?></strong><small><?= formatar_percentual($taxaCliqueWhatsapp) ?> dos concluidos</small></article>
      </div>

      <div class="funil-taxas">
        <article class="funil-card"><span>Taxa de conclusao</span><strong><?= formatar_percentual($taxaConclusao) ?></strong></article>
        <article class="funil-card"><span>Taxa de clique no WhatsApp</span><strong><?= formatar_percentual($taxaCliqueWhatsapp) ?></strong></article>
        <article class="funil-card"><span>Taxa geral de conversao</span><strong><?= formatar_percentual($taxaConversaoGeral) ?></strong></article>
      </div>
    </section>

    <section class="panel">
      <div class="panel-head">
        <h2>Origem dos acessos</h2>
        <span>Principal: <?= e((string) $origemPrincipal) ?></span>
      </div>

      <div class="origens-grid">
        <?php foreach ($origens as $origemNome => $origemTotal): ?>
          <?php $percentualOrigem = porcentagem_metrica((int) $origemTotal, $totalOrigens); ?>
          <article class="origem-item">
            <div class="origem-topo">
              <strong><?= e((string) $origemNome) ?></strong>
              <span><?= (int) $origemTotal ?> - <?= formatar_percentual($percentualOrigem) ?></span>
            </div>
            <div class="origem-barra" aria-hidden="true"><span style="width: <?= $percentualOrigem ?>%"></span></div>
          </article>
        <?php endforeach; ?>
      </div>
    </section>

    <section class="panel">
      <div class="panel-head">
        <h2>Relatorio de performance</h2>
        <span>Insights automaticos</span>
      </div>

      <div class="insights-list">
        <?php foreach ($insights as $insight): ?>
          <p class="insight-item"><?= e($insight) ?></p>
        <?php endforeach; ?>
      </div>
    </section>

    <section class="panel">
      <div class="panel-head">
        <h2>Ultimos leads</h2>
        <span><?= $totalEventos ?> eventos registrados</span>
      </div>

      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Data</th>
              <th>Hora</th>
              <th>Tipo de evento</th>
              <th>Pagina</th>
              <th>Origem</th>
              <th>IP Hash</th>
              <th>User Agent Hash</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!$eventosRecentes): ?>
              <tr><td colspan="7">Nenhum evento registrado ainda.</td></tr>
            <?php endif; ?>
            <?php foreach ($eventosRecentes as $evento): ?>
              <?php $dataEvento = new DateTime((string) $evento['created_at']); ?>
              <tr>
                <td><?= e($dataEvento->format('d/m/Y')) ?></td>
                <td><?= e($dataEvento->format('H:i')) ?></td>
                <td><span class="evento-tipo"><?= e(rotulo_evento((string) $evento['event_type'])) ?></span></td>
                <td><?= e((string) $evento['page']) ?></td>
                <td><?= e(classificar_origem($evento['referer'] ?? '')) ?></td>
                <td class="hash-cell"><?= e(hash_curto($evento['ip_hash'] ?? '')) ?></td>
                <td class="hash-cell"><?= e(hash_curto($evento['user_agent_hash'] ?? '')) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <?php if ($totalPaginas > 1): ?>
        <nav class="paginacao-admin" aria-label="Paginacao dos eventos">
          <?php if ($paginaAtual > 1): ?>
            <a href="?pagina=<?= $paginaAtual - 1 ?>">Anterior</a>
          <?php endif; ?>

          <?php for ($pagina = 1; $pagina <= $totalPaginas; $pagina++): ?>
            <?php if ($pagina === $paginaAtual): ?>
              <span><?= $pagina ?></span>
            <?php else: ?>
              <a href="?pagina=<?= $pagina ?>"><?= $pagina ?></a>
            <?php endif; ?>
          <?php endfor; ?>

          <?php if ($paginaAtual < $totalPaginas): ?>
            <a href="?pagina=<?= $paginaAtual + 1 ?>">Proxima</a>
          <?php endif; ?>
        </nav>
      <?php endif; ?>
    </section>
  </main>
</body>
</html>
