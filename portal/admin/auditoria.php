<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/../includes/admin_ui.php';
require_once __DIR__ . '/../includes/auditoria.php';

exigir_admin();
exigir_permissao('auditoria.visualizar');

$adminAtual = admin_atual() ?? [];
if (($adminAtual['perfil'] ?? '') !== 'superadministrador') {
    http_response_code(403);
    exit('Acesso negado. A Central de Auditoria e exclusiva do superadministrador.');
}

function auditoria_parametros_filtro(): array
{
    return [
        'inicio' => trim((string) ($_GET['inicio'] ?? date('Y-m-01'))),
        'fim' => trim((string) ($_GET['fim'] ?? date('Y-m-d'))),
        'admin_id' => (int) ($_GET['admin_id'] ?? 0),
        'perfil' => trim((string) ($_GET['perfil'] ?? '')),
        'modulo' => trim((string) ($_GET['modulo'] ?? '')),
        'acao' => trim((string) ($_GET['acao'] ?? '')),
        'resultado' => trim((string) ($_GET['resultado'] ?? '')),
        'entidade' => trim((string) ($_GET['entidade'] ?? '')),
        'registro_id' => (int) ($_GET['registro_id'] ?? 0),
        'busca' => trim((string) ($_GET['busca'] ?? '')),
    ];
}

function auditoria_where(array $filtros, array &$params): array
{
    $where = [];

    if ($filtros['inicio'] !== '') {
        $where[] = 'criado_em >= :inicio';
        $params[':inicio'] = $filtros['inicio'] . ' 00:00:00';
    }

    if ($filtros['fim'] !== '') {
        $where[] = 'criado_em <= :fim';
        $params[':fim'] = $filtros['fim'] . ' 23:59:59';
    }

    foreach (['modulo', 'acao', 'resultado', 'entidade'] as $campo) {
        if ($filtros[$campo] !== '') {
            $where[] = $campo . ' = :' . $campo;
            $params[':' . $campo] = $filtros[$campo];
        }
    }

    if ($filtros['admin_id'] > 0) {
        $where[] = 'admin_id = :admin_id';
        $params[':admin_id'] = $filtros['admin_id'];
    }

    if ($filtros['perfil'] !== '') {
        $where[] = 'admin_perfil_snapshot = :perfil';
        $params[':perfil'] = $filtros['perfil'];
    }

    if ($filtros['registro_id'] > 0) {
        $where[] = 'registro_id = :registro_id';
        $params[':registro_id'] = $filtros['registro_id'];
    }

    if ($filtros['busca'] !== '') {
        $where[] = '(admin_nome_snapshot LIKE :busca OR admin_email_snapshot LIKE :busca OR descricao LIKE :busca OR mensagem_resultado LIKE :busca OR rota LIKE :busca)';
        $params[':busca'] = '%' . $filtros['busca'] . '%';
    }

    return $where;
}

function auditoria_json_humano(?string $json): array
{
    if (!$json) {
        return [];
    }

    $dados = json_decode($json, true);
    return is_array($dados) ? $dados : [];
}

function auditoria_resumo_ua(?string $userAgent): string
{
    $ua = trim((string) $userAgent);
    if ($ua === '') {
        return '-';
    }

    return strlen($ua) > 120 ? substr($ua, 0, 117) . '...' : $ua;
}

function auditoria_render_dados(array $dados): void
{
    if (!$dados) {
        echo '<p class="empty">Sem dados registrados.</p>';
        return;
    }

    echo '<div class="table-wrap"><table><tbody>';
    foreach ($dados as $campo => $valor) {
        $texto = is_array($valor)
            ? json_encode($valor, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            : (string) $valor;
        echo '<tr><th>' . e((string) $campo) . '</th><td>' . e($texto) . '</td></tr>';
    }
    echo '</tbody></table></div>';
}

$filtros = auditoria_parametros_filtro();
$params = [];
$where = auditoria_where($filtros, $params);
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

if (($_GET['export'] ?? '') === 'csv') {
    $stmtCsv = $pdo->prepare(
        "SELECT criado_em, admin_nome_snapshot, admin_email_snapshot, admin_perfil_snapshot, modulo, acao, entidade, registro_id, resultado, ip, rota
         FROM auditoria_admin
         {$whereSql}
         ORDER BY criado_em DESC, id DESC
         LIMIT 5000"
    );
    $stmtCsv->execute($params);

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="rwdev-auditoria.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['RWDEV - Central de Auditoria']);
    fputcsv($out, ['Gerado em', date('d/m/Y H:i:s')]);
    fputcsv($out, []);
    fputcsv($out, ['Data', 'Usuario', 'E-mail', 'Perfil', 'Modulo', 'Acao', 'Entidade', 'Registro', 'Resultado', 'IP', 'Rota']);
    foreach ($stmtCsv->fetchAll() as $linha) {
        fputcsv($out, [
            $linha['criado_em'],
            $linha['admin_nome_snapshot'],
            $linha['admin_email_snapshot'],
            $linha['admin_perfil_snapshot'],
            $linha['modulo'],
            $linha['acao'],
            $linha['entidade'],
            $linha['registro_id'],
            $linha['resultado'],
            $linha['ip'],
            $linha['rota'],
        ]);
    }
    fclose($out);
    exit;
}

$stmt = $pdo->prepare(
    "SELECT *
     FROM auditoria_admin
     {$whereSql}
     ORDER BY criado_em DESC, id DESC
     LIMIT 200"
);
$stmt->execute($params);
$registros = $stmt->fetchAll();

$detalheId = (int) ($_GET['detalhe'] ?? 0);
$detalhe = null;
if ($detalheId > 0) {
    $stmtDetalhe = $pdo->prepare('SELECT * FROM auditoria_admin WHERE id = :id LIMIT 1');
    $stmtDetalhe->execute([':id' => $detalheId]);
    $detalhe = $stmtDetalhe->fetch() ?: null;
}

$cards = [
    'hoje' => (int) $pdo->query("SELECT COUNT(*) FROM auditoria_admin WHERE DATE(criado_em) = CURDATE()")->fetchColumn(),
    'mes' => (int) $pdo->query("SELECT COUNT(*) FROM auditoria_admin WHERE YEAR(criado_em) = YEAR(CURDATE()) AND MONTH(criado_em) = MONTH(CURDATE())")->fetchColumn(),
    'alteracoes' => (int) $pdo->query("SELECT COUNT(*) FROM auditoria_admin WHERE acao LIKE '%edit%' OR acao LIKE '%alter%' OR acao IN ('perfil_alterado','permissoes_alteradas')")->fetchColumn(),
    'exclusoes' => (int) $pdo->query("SELECT COUNT(*) FROM auditoria_admin WHERE acao LIKE '%exclu%'")->fetchColumn(),
    'negados' => (int) $pdo->query("SELECT COUNT(*) FROM auditoria_admin WHERE resultado = 'negado'")->fetchColumn(),
    'erros' => (int) $pdo->query("SELECT COUNT(*) FROM auditoria_admin WHERE resultado = 'erro'")->fetchColumn(),
    'usuarios' => (int) $pdo->query("SELECT COUNT(DISTINCT admin_id) FROM auditoria_admin WHERE admin_id IS NOT NULL")->fetchColumn(),
];

$admins = $pdo->query('SELECT DISTINCT admin_id, admin_nome_snapshot FROM auditoria_admin WHERE admin_id IS NOT NULL ORDER BY admin_nome_snapshot')->fetchAll();
$modulos = $pdo->query('SELECT DISTINCT modulo FROM auditoria_admin ORDER BY modulo')->fetchAll(PDO::FETCH_COLUMN);
$acoes = $pdo->query('SELECT DISTINCT acao FROM auditoria_admin ORDER BY acao')->fetchAll(PDO::FETCH_COLUMN);
$entidades = $pdo->query('SELECT DISTINCT entidade FROM auditoria_admin ORDER BY entidade')->fetchAll(PDO::FETCH_COLUMN);
$queryCsv = $_GET;
$queryCsv['export'] = 'csv';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Central de Auditoria | RWDEV</title>
  <link rel="stylesheet" href="<?= e(asset_url('assets/css/style.css')) ?>">
</head>
<body>
  <?php admin_render_header(); ?>

  <main class="app-container">
    <section class="page-title">
      <span>Registro de Seguranca</span>
      <h1>Central de Auditoria</h1>
      <p>Historico de atividades administrativas da plataforma RWDEV.</p>
    </section>

    <div class="metrics-grid">
      <article class="metric-card"><span>Acoes hoje</span><strong><?= $cards['hoje'] ?></strong></article>
      <article class="metric-card"><span>Acoes no mes</span><strong><?= $cards['mes'] ?></strong></article>
      <article class="metric-card"><span>Alteracoes</span><strong><?= $cards['alteracoes'] ?></strong></article>
      <article class="metric-card"><span>Exclusoes</span><strong><?= $cards['exclusoes'] ?></strong></article>
      <article class="metric-card"><span>Acessos negados</span><strong><?= $cards['negados'] ?></strong></article>
      <article class="metric-card"><span>Erros</span><strong><?= $cards['erros'] ?></strong></article>
      <article class="metric-card destaque"><span>Usuarios auditados</span><strong><?= $cards['usuarios'] ?></strong></article>
    </div>

    <form class="panel form-grid two-cols" method="get">
      <h2 class="form-section-title">Filtros</h2>
      <label>Periodo inicial<input type="date" name="inicio" value="<?= e($filtros['inicio']) ?>"></label>
      <label>Periodo final<input type="date" name="fim" value="<?= e($filtros['fim']) ?>"></label>
      <label>Usuario
        <select name="admin_id">
          <option value="0">Todos</option>
          <?php foreach ($admins as $admin): ?>
            <option value="<?= (int) $admin['admin_id'] ?>" <?= (int) $admin['admin_id'] === $filtros['admin_id'] ? 'selected' : '' ?>><?= e((string) $admin['admin_nome_snapshot']) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>Perfil<input name="perfil" value="<?= e($filtros['perfil']) ?>"></label>
      <label>Modulo
        <select name="modulo">
          <option value="">Todos</option>
          <?php foreach ($modulos as $modulo): ?>
            <option value="<?= e((string) $modulo) ?>" <?= $filtros['modulo'] === $modulo ? 'selected' : '' ?>><?= e((string) $modulo) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>Acao
        <select name="acao">
          <option value="">Todas</option>
          <?php foreach ($acoes as $acao): ?>
            <option value="<?= e((string) $acao) ?>" <?= $filtros['acao'] === $acao ? 'selected' : '' ?>><?= e((string) $acao) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>Resultado
        <select name="resultado">
          <option value="">Todos</option>
          <?php foreach (['sucesso', 'erro', 'negado'] as $resultado): ?>
            <option value="<?= e($resultado) ?>" <?= $filtros['resultado'] === $resultado ? 'selected' : '' ?>><?= e($resultado) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>Entidade
        <select name="entidade">
          <option value="">Todas</option>
          <?php foreach ($entidades as $entidade): ?>
            <option value="<?= e((string) $entidade) ?>" <?= $filtros['entidade'] === $entidade ? 'selected' : '' ?>><?= e((string) $entidade) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>Registro ID<input name="registro_id" inputmode="numeric" value="<?= $filtros['registro_id'] ?: '' ?>"></label>
      <label>Busca textual<input name="busca" value="<?= e($filtros['busca']) ?>"></label>
      <div class="ponto-actions full">
        <button class="btn" type="submit">Filtrar</button>
        <a class="btn outline" href="auditoria.php">Limpar</a>
        <a class="btn outline" href="auditoria.php?<?= e(http_build_query($queryCsv)) ?>">Exportar CSV</a>
        <button class="btn outline" type="button" onclick="window.print()">Imprimir</button>
      </div>
    </form>

    <?php if ($detalhe): ?>
      <section class="panel">
        <div class="panel-head">
          <h2>Detalhes #<?= (int) $detalhe['id'] ?></h2>
          <a href="auditoria.php?<?= e(http_build_query(array_diff_key($_GET, ['detalhe' => true]))) ?>">Fechar</a>
        </div>
        <div class="metrics-grid">
          <article class="metric-card"><span>Usuario</span><strong><?= e((string) $detalhe['admin_nome_snapshot']) ?></strong><small><?= e((string) $detalhe['admin_email_snapshot']) ?></small></article>
          <article class="metric-card"><span>Acao</span><strong><?= e((string) $detalhe['acao']) ?></strong><small><?= e((string) $detalhe['modulo']) ?></small></article>
          <article class="metric-card"><span>Resultado</span><strong><?= e((string) $detalhe['resultado']) ?></strong><small><?= e((string) $detalhe['mensagem_resultado']) ?></small></article>
        </div>
        <p><b>Data:</b> <?= date('d/m/Y H:i:s', strtotime((string) $detalhe['criado_em'])) ?></p>
        <p><b>Rota:</b> <?= e((string) $detalhe['rota']) ?> | <b>Metodo:</b> <?= e((string) $detalhe['metodo_http']) ?> | <b>IP:</b> <?= e((string) $detalhe['ip']) ?></p>
        <p><b>Navegador:</b> <?= e(auditoria_resumo_ua($detalhe['user_agent'] ?? '')) ?></p>
        <h3>Dados anteriores</h3>
        <?php auditoria_render_dados(auditoria_json_humano($detalhe['dados_anteriores_json'] ?? null)); ?>
        <h3>Dados posteriores</h3>
        <?php auditoria_render_dados(auditoria_json_humano($detalhe['dados_posteriores_json'] ?? null)); ?>
        <h3>Campos alterados</h3>
        <?php auditoria_render_dados(auditoria_json_humano($detalhe['campos_alterados_json'] ?? null)); ?>
      </section>
    <?php endif; ?>

    <section class="panel">
      <div class="panel-head">
        <h2>Historico de Atividades</h2>
        <span class="status status-concluido"><?= count($registros) ?> registro(s)</span>
      </div>
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Data e hora</th><th>Usuario</th><th>Perfil</th><th>Modulo</th><th>Acao</th><th>Registro</th><th>Resultado</th><th>IP</th><th>Detalhes</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!$registros): ?><tr><td colspan="9">Nenhum registro encontrado.</td></tr><?php endif; ?>
            <?php foreach ($registros as $registro): ?>
              <?php $queryDetalhe = array_merge($_GET, ['detalhe' => (int) $registro['id']]); ?>
              <tr>
                <td><?= date('d/m/Y H:i:s', strtotime((string) $registro['criado_em'])) ?></td>
                <td><?= e((string) $registro['admin_nome_snapshot']) ?><br><small><?= e((string) $registro['admin_email_snapshot']) ?></small></td>
                <td><?= e((string) $registro['admin_perfil_snapshot']) ?></td>
                <td><?= e((string) $registro['modulo']) ?></td>
                <td><?= e((string) $registro['acao']) ?></td>
                <td><?= e((string) $registro['entidade']) ?><?= $registro['registro_id'] ? ' #' . (int) $registro['registro_id'] : '' ?></td>
                <td><span class="status status-<?= e((string) $registro['resultado']) ?>"><?= e((string) $registro['resultado']) ?></span></td>
                <td><?= e((string) $registro['ip']) ?></td>
                <td><a href="auditoria.php?<?= e(http_build_query($queryDetalhe)) ?>">Detalhes</a></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>
  </main>
</body>
</html>
