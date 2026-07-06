<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/../includes/auth.php';

exigir_admin();

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

function caminho_interno_evento(?string $valor): string
{
    $valor = trim((string) $valor);

    if ($valor === '') {
        return '';
    }

    if (str_starts_with($valor, 'http://') || str_starts_with($valor, 'https://')) {
        $host = strtolower((string) parse_url($valor, PHP_URL_HOST));
        $path = parse_url($valor, PHP_URL_PATH);

        if ($host !== '' && !str_contains($host, 'rwdev.com.br')) {
            return '';
        }

        $valor = is_string($path) && $path !== '' ? $path : '/';
    }

    $valor = '/' . ltrim($valor, '/');

    return rtrim($valor, '/') === '' ? '/' : rtrim($valor, '/');
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

function status_lead_diagnostico(): array
{
    return ['Novo Lead', 'Em Contato', 'Proposta Enviada', 'Cliente Fechado', 'Encerrado'];
}

function classe_status_lead(string $status): string
{
    return [
        'Novo Lead' => 'lead-status-novo',
        'Em Contato' => 'lead-status-contato',
        'Proposta Enviada' => 'lead-status-proposta',
        'Cliente Fechado' => 'lead-status-fechado',
        'Encerrado' => 'lead-status-encerrado',
    ][$status] ?? 'lead-status-novo';
}

function classe_classificacao_lead(string $classificacao): string
{
    return [
        'Muito Quente' => 'lead-classificacao-muito-quente',
        'Quente' => 'lead-classificacao-quente',
        'Morno' => 'lead-classificacao-morno',
    ][$classificacao] ?? 'lead-classificacao-morno';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validar_csrf();

    $leadId = (int) ($_POST['lead_id'] ?? 0);
    $statusLead = (string) ($_POST['status'] ?? '');

    if ($leadId > 0 && in_array($statusLead, status_lead_diagnostico(), true)) {
        $stmtStatus = $pdo->prepare(
            'UPDATE diagnostico_leads
             SET status = :status, updated_at = NOW()
             WHERE id = :id'
        );
        $stmtStatus->execute([
            ':status' => $statusLead,
            ':id' => $leadId,
        ]);
    }

    redirect('diagnostico-metricas.php');
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
$totalDiagnosticosRealizados = contador_metrica($pdo, 'SELECT COUNT(*) FROM diagnostico_leads');
$totalCliquesWhatsappLeads = contador_metrica($pdo, 'SELECT COUNT(*) FROM diagnostico_leads WHERE clicou_whatsapp = 1');
$totalOportunidadesGeradas = contador_metrica(
    $pdo,
    "SELECT COUNT(*) FROM diagnostico_leads
     WHERE classificacao IN ('Muito Quente', 'Quente') OR clicou_whatsapp = 1"
);
$taxaConversaoLeads = porcentagem_metrica($totalOportunidadesGeradas, $totalDiagnosticosRealizados);
$leadsMes = contador_metrica(
    $pdo,
    'SELECT COUNT(*) FROM diagnostico_leads
     WHERE created_at >= DATE_FORMAT(CURDATE(), "%Y-%m-01")'
);
$clientesFechados = contador_metrica(
    $pdo,
    "SELECT COUNT(*) FROM diagnostico_leads
     WHERE status = 'Cliente Fechado'"
);
$clientesFechadosMes = contador_metrica(
    $pdo,
    "SELECT COUNT(*) FROM diagnostico_leads
     WHERE status = 'Cliente Fechado'
       AND updated_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01')"
);
$taxaFechamento = porcentagem_metrica($clientesFechadosMes, $leadsMes);
$empresasSemPerfilGoogle = contador_metrica(
    $pdo,
    "SELECT COUNT(*) FROM diagnostico_leads
     WHERE JSON_UNQUOTE(JSON_EXTRACT(respostas_json, '$.perfil_google')) <> 'Sim'"
);
$empresasSemSite = contador_metrica(
    $pdo,
    "SELECT COUNT(*) FROM diagnostico_leads
     WHERE JSON_UNQUOTE(JSON_EXTRACT(respostas_json, '$.site_profissional')) <> 'Sim'"
);
$interesseGoogleAds = contador_metrica(
    $pdo,
    "SELECT COUNT(*) FROM diagnostico_leads
     WHERE JSON_UNQUOTE(JSON_EXTRACT(respostas_json, '$.google_ads')) = 'Tenho interesse'"
);
$cliquesWhatsapp24h = contador_metrica(
    $pdo,
    'SELECT COUNT(*) FROM diagnostico_leads
     WHERE clicou_whatsapp = 1
       AND whatsapp_clicked_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)'
);

$stmtOrigemTop = $pdo->prepare(
    'SELECT origem, COUNT(*) AS total
     FROM diagnostico_leads
     WHERE created_at >= DATE_FORMAT(CURDATE(), "%Y-%m-01")
     GROUP BY origem
     ORDER BY total DESC
     LIMIT 1'
);
$stmtOrigemTop->execute();
$origemTopLead = $stmtOrigemTop->fetch();

$stmtCidadeTop = $pdo->prepare(
    'SELECT cidade, COUNT(*) AS total
     FROM diagnostico_leads
     GROUP BY cidade
     ORDER BY total DESC
     LIMIT 1'
);
$stmtCidadeTop->execute();
$cidadeTopLead = $stmtCidadeTop->fetch();

$paginasInteresse = [
    '/diagnostico' => 0,
    '/' => 0,
    '/servicos.html' => 0,
    '/trabalhos.html' => 0,
    '/parceiros.html' => 0,
    '/contato.html' => 0,
];

$stmtPaginasInteresse = $pdo->prepare(
    "SELECT page, referer, COUNT(*) AS total
     FROM diagnostico_eventos
     WHERE created_at >= {$periodoInicio}
     GROUP BY page, referer"
);
$stmtPaginasInteresse->execute();

foreach ($stmtPaginasInteresse->fetchAll() as $linhaPagina) {
    $paginaEvento = caminho_interno_evento($linhaPagina['page'] ?? '');
    $refererEvento = caminho_interno_evento($linhaPagina['referer'] ?? '');
    $totalLinha = (int) $linhaPagina['total'];

    if (array_key_exists($paginaEvento, $paginasInteresse)) {
        $paginasInteresse[$paginaEvento] += $totalLinha;
    }

    if ($refererEvento !== $paginaEvento && array_key_exists($refererEvento, $paginasInteresse)) {
        $paginasInteresse[$refererEvento] += $totalLinha;
    }
}

$totalPaginasInteresse = array_sum($paginasInteresse);
$paginasInteresseComDados = array_filter($paginasInteresse, static fn (int $total): bool => $total > 0);
$ultimoCliqueWhatsapp = null;
$stmtUltimoCliqueWhatsapp = $pdo->prepare(
    "SELECT created_at
     FROM diagnostico_eventos
     WHERE event_type = 'whatsapp_click'
     ORDER BY created_at DESC
     LIMIT 1"
);
$stmtUltimoCliqueWhatsapp->execute();
$ultimoCliqueWhatsappValor = $stmtUltimoCliqueWhatsapp->fetchColumn();

if ($ultimoCliqueWhatsappValor) {
    $ultimoCliqueWhatsapp = new DateTime((string) $ultimoCliqueWhatsappValor);
}

$insights = [
    formatar_percentual($taxaInicio) . ' dos visitantes iniciaram o diagnostico.',
    formatar_percentual(porcentagem_metrica($diagnosticosConcluidos, $visitas30Dias)) . ' dos visitantes concluiram o diagnostico.',
    formatar_percentual($taxaConversaoGeral) . ' dos visitantes clicaram no WhatsApp.',
];

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

$itensLeadsPorPagina = 15;
$paginaLeadsAtual = max(1, (int) ($_GET['lead_pagina'] ?? 1));
$totalLeads = contador_metrica($pdo, 'SELECT COUNT(*) FROM diagnostico_leads');
$totalPaginasLeads = max(1, (int) ceil($totalLeads / $itensLeadsPorPagina));
$paginaLeadsAtual = min($paginaLeadsAtual, $totalPaginasLeads);
$offsetLeads = ($paginaLeadsAtual - 1) * $itensLeadsPorPagina;

$stmtLeads = $pdo->prepare(
    'SELECT id, empresa, cidade, responsavel, whatsapp, pontuacao, classificacao, origem, status, created_at
     FROM diagnostico_leads
     ORDER BY created_at DESC
     LIMIT :limite OFFSET :offset'
);
$stmtLeads->bindValue(':limite', $itensLeadsPorPagina, PDO::PARAM_INT);
$stmtLeads->bindValue(':offset', $offsetLeads, PDO::PARAM_INT);
$stmtLeads->execute();
$leadsRecentes = $stmtLeads->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex, nofollow">
  <title>Metricas do Diagnostico | RWDEV Admin</title>
  <link rel="stylesheet" href="../assets/css/style.css">
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

    .lead-resultados-grid,
    .lead-oportunidades-grid {
      display: grid;
      grid-template-columns: repeat(4, minmax(0, 1fr));
      gap: 12px;
    }

    .funil-card,
    .pagina-interesse-item,
    .insight-item,
    .lead-oportunidade-card {
      background: #f8fafc;
      border: 1px solid var(--line);
      border-radius: var(--radius);
      padding: 16px;
    }

    .funil-card strong,
    .lead-oportunidade-card strong {
      display: block;
      font-size: 28px;
      margin-top: 8px;
    }

    .lead-oportunidade-card p {
      margin: 8px 0 0;
    }

    .funil-taxas {
      display: grid;
      grid-template-columns: repeat(3, minmax(0, 1fr));
      gap: 12px;
      margin-top: 14px;
    }

    .paginas-interesse-grid {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 12px;
    }

    .pagina-interesse-topo {
      align-items: center;
      display: flex;
      justify-content: space-between;
      gap: 12px;
      margin-bottom: 10px;
    }

    .pagina-interesse-barra {
      background: #e5eaf3;
      border-radius: 999px;
      height: 8px;
      overflow: hidden;
    }

    .pagina-interesse-barra span {
      background: linear-gradient(135deg, var(--primary), var(--gold));
      display: block;
      height: 100%;
    }

    .whatsapp-resumo-grid {
      display: grid;
      grid-template-columns: repeat(3, minmax(0, 1fr));
      gap: 12px;
      margin-top: 14px;
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

    .lead-badge {
      border-radius: 999px;
      display: inline-flex;
      font-size: 12px;
      font-weight: 800;
      padding: 7px 10px;
      white-space: nowrap;
    }

    .lead-classificacao-muito-quente {
      background: #fde8e8;
      color: #b42318;
    }

    .lead-classificacao-quente {
      background: #fff3d6;
      color: #a76800;
    }

    .lead-classificacao-morno {
      background: #e0edff;
      color: #0753bd;
    }

    .lead-status-novo {
      background: #fff3d6;
      color: #a76800;
    }

    .lead-status-contato {
      background: #e0edff;
      color: #0753bd;
    }

    .lead-status-proposta {
      background: #e6f7ff;
      color: #087299;
    }

    .lead-status-fechado {
      background: #e5f8ef;
      color: var(--success);
    }

    .lead-status-encerrado {
      background: #fde8e8;
      color: var(--danger);
    }

    .lead-status-form {
      display: flex;
      gap: 8px;
      margin-top: 8px;
      min-width: 220px;
    }

    .lead-status-form select {
      min-width: 150px;
      padding: 8px;
    }

    .lead-status-form button {
      min-height: 38px;
      padding: 8px 10px;
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
      .paginas-interesse-grid,
      .whatsapp-resumo-grid,
      .lead-resultados-grid,
      .lead-oportunidades-grid {
        grid-template-columns: 1fr;
      }

      .lead-status-form {
        min-width: 0;
      }
    }
  </style>
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
      <span>Ferramenta /diagnostico</span>
      <h1>Painel comercial do diagnostico</h1>
    </section>

    <section class="panel">
      <div class="panel-head">
        <h2>&#127919; Leads Gerados</h2>
        <span>Oportunidades comerciais da RWDEV</span>
      </div>

      <div class="lead-resultados-grid">
        <article class="metric-card"><span>Total de diagnosticos realizados</span><strong><?= $totalDiagnosticosRealizados ?></strong></article>
        <article class="metric-card"><span>Total de cliques no WhatsApp</span><strong><?= $totalCliquesWhatsappLeads ?></strong></article>
        <article class="metric-card"><span>Total de oportunidades geradas</span><strong><?= $totalOportunidadesGeradas ?></strong></article>
        <article class="metric-card destaque"><span>Taxa de conversao</span><strong><?= formatar_percentual($taxaConversaoLeads) ?></strong></article>
      </div>
    </section>

    <section class="panel">
      <div class="panel-head">
        <h2>Painel de resultados</h2>
        <span>Indicadores comerciais</span>
      </div>

      <div class="lead-resultados-grid">
        <article class="funil-card"><span>Leads do mes</span><strong><?= $leadsMes ?></strong></article>
        <article class="funil-card"><span>Clientes fechados</span><strong><?= $clientesFechados ?></strong><small><?= $clientesFechadosMes ?> no mes atual</small></article>
        <article class="funil-card"><span>Taxa de fechamento</span><strong><?= formatar_percentual($taxaFechamento) ?></strong></article>
        <article class="funil-card"><span>Origem que mais gera oportunidades</span><strong><?= e((string) ($origemTopLead['origem'] ?? 'Sem dados')) ?></strong><small><?= (int) ($origemTopLead['total'] ?? 0) ?> lead(s) no mes</small></article>
        <article class="funil-card"><span>Cidade com mais diagnosticos</span><strong><?= e((string) ($cidadeTopLead['cidade'] ?? 'Sem dados')) ?></strong><small><?= (int) ($cidadeTopLead['total'] ?? 0) ?> diagnostico(s)</small></article>
      </div>
    </section>

    <section class="panel">
      <div class="panel-head">
        <h2>&#128640; Oportunidades Identificadas</h2>
        <span>Sinais comerciais automaticos</span>
      </div>

      <div class="lead-oportunidades-grid">
        <article class="lead-oportunidade-card">
          <strong><?= $empresasSemPerfilGoogle ?></strong>
          <p>empresas nao possuem Perfil da Empresa no Google.</p>
        </article>
        <article class="lead-oportunidade-card">
          <strong><?= $empresasSemSite ?></strong>
          <p>empresas nao possuem site profissional.</p>
        </article>
        <article class="lead-oportunidade-card">
          <strong><?= $interesseGoogleAds ?></strong>
          <p>empresas demonstraram interesse em Google Ads.</p>
        </article>
        <article class="lead-oportunidade-card">
          <strong><?= $cliquesWhatsapp24h ?></strong>
          <p>empresas clicaram no WhatsApp nas ultimas 24 horas.</p>
        </article>
      </div>
    </section>

    <section class="panel">
      <div class="panel-head">
        <h2>Historico de leads</h2>
        <span><?= $totalLeads ?> lead(s) registrados</span>
      </div>

      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Data</th>
              <th>Empresa</th>
              <th>Cidade</th>
              <th>Responsavel</th>
              <th>WhatsApp</th>
              <th>Pontuacao</th>
              <th>Classificacao</th>
              <th>Origem</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!$leadsRecentes): ?>
              <tr><td colspan="9">Nenhum lead registrado ainda.</td></tr>
            <?php endif; ?>
            <?php foreach ($leadsRecentes as $lead): ?>
              <?php $dataLead = new DateTime((string) $lead['created_at']); ?>
              <tr>
                <td><?= e($dataLead->format('d/m/Y H:i')) ?></td>
                <td><?= e((string) $lead['empresa']) ?></td>
                <td><?= e((string) $lead['cidade']) ?></td>
                <td><?= e((string) $lead['responsavel']) ?></td>
                <td><?= e((string) $lead['whatsapp']) ?></td>
                <td><?= (int) $lead['pontuacao'] ?>/100</td>
                <td><span class="lead-badge <?= e(classe_classificacao_lead((string) $lead['classificacao'])) ?>"><?= e((string) $lead['classificacao']) ?></span></td>
                <td><?= e((string) $lead['origem']) ?></td>
                <td>
                  <span class="lead-badge <?= e(classe_status_lead((string) $lead['status'])) ?>"><?= e((string) $lead['status']) ?></span>
                  <form class="lead-status-form" method="post">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="lead_id" value="<?= (int) $lead['id'] ?>">
                    <select name="status" aria-label="Status do lead">
                      <?php foreach (status_lead_diagnostico() as $statusDisponivel): ?>
                        <option value="<?= e($statusDisponivel) ?>" <?= $lead['status'] === $statusDisponivel ? 'selected' : '' ?>><?= e($statusDisponivel) ?></option>
                      <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn small">Salvar</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <?php if ($totalPaginasLeads > 1): ?>
        <nav class="paginacao-admin" aria-label="Paginacao dos leads">
          <?php if ($paginaLeadsAtual > 1): ?>
            <a href="?lead_pagina=<?= $paginaLeadsAtual - 1 ?>">Anterior</a>
          <?php endif; ?>

          <?php for ($paginaLead = 1; $paginaLead <= $totalPaginasLeads; $paginaLead++): ?>
            <?php if ($paginaLead === $paginaLeadsAtual): ?>
              <span><?= $paginaLead ?></span>
            <?php else: ?>
              <a href="?lead_pagina=<?= $paginaLead ?>"><?= $paginaLead ?></a>
            <?php endif; ?>
          <?php endfor; ?>

          <?php if ($paginaLeadsAtual < $totalPaginasLeads): ?>
            <a href="?lead_pagina=<?= $paginaLeadsAtual + 1 ?>">Proxima</a>
          <?php endif; ?>
        </nav>
      <?php endif; ?>
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
        <h2>&#128204; Paginas e Links de Interesse</h2>
        <span>Diagnostico e conversao</span>
      </div>

      <?php if (!$paginasInteresseComDados): ?>
        <p class="empty">Ainda não há dados suficientes sobre páginas internas.</p>
      <?php else: ?>
        <div class="paginas-interesse-grid">
          <?php foreach ($paginasInteresse as $paginaInteresse => $totalPaginaInteresse): ?>
            <?php $percentualPaginaInteresse = porcentagem_metrica((int) $totalPaginaInteresse, $totalPaginasInteresse); ?>
            <article class="pagina-interesse-item">
              <div class="pagina-interesse-topo">
                <strong><?= e((string) $paginaInteresse) ?></strong>
                <span><?= (int) $totalPaginaInteresse ?> - <?= formatar_percentual($percentualPaginaInteresse) ?></span>
              </div>
              <div class="pagina-interesse-barra" aria-hidden="true"><span style="width: <?= $percentualPaginaInteresse ?>%"></span></div>
            </article>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <div class="whatsapp-resumo-grid">
        <article class="funil-card">
          <span>Total de cliques no WhatsApp</span>
          <strong><?= $cliquesWhatsapp ?></strong>
        </article>
        <article class="funil-card">
          <span>Percentual sobre diagnosticos concluidos</span>
          <strong><?= formatar_percentual($taxaCliqueWhatsapp) ?></strong>
        </article>
        <article class="funil-card">
          <span>Ultimo clique registrado</span>
          <strong><?= $ultimoCliqueWhatsapp ? e($ultimoCliqueWhatsapp->format('d/m/Y H:i')) : 'Sem dados' ?></strong>
        </article>
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
