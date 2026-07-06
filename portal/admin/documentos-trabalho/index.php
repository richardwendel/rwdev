<?php
declare(strict_types=1);

require_once __DIR__ . '/_funcoes.php';

$categoriaFiltro = trim($_GET['categoria'] ?? '');
$empresaFiltro = trim($_GET['empresa'] ?? '');
$anoFiltro = (int) ($_GET['ano'] ?? 0);
$categorias = docs_categorias($pdo, false);

$where = ['1 = 1'];
$params = [];

if ($categoriaFiltro !== '') {
    $where[] = 'd.categoria = :categoria';
    $params[':categoria'] = $categoriaFiltro;
}

if ($empresaFiltro !== '') {
    $where[] = 'd.empresa LIKE :empresa';
    $params[':empresa'] = '%' . $empresaFiltro . '%';
}

if ($anoFiltro > 0) {
    $where[] = 'YEAR(d.data_documento) = :ano';
    $params[':ano'] = $anoFiltro;
}

$sqlPontos = docs_tem_soni_ponto($pdo)
    ? 'SELECT d.*, p.data AS ponto_data, l.codigo_loja
       FROM documentos_trabalho d
       LEFT JOIN pontos_trabalho p ON p.id = d.ponto_id
       LEFT JOIN lojas_trabalho l ON l.id = p.loja_id'
    : 'SELECT d.*, NULL AS ponto_data, NULL AS codigo_loja
       FROM documentos_trabalho d';

$stmt = $pdo->prepare(
    $sqlPontos . '
    WHERE ' . implode(' AND ', $where) . '
    ORDER BY d.data_documento DESC, d.id DESC'
);
$stmt->execute($params);
$documentos = $stmt->fetchAll();

$cards = $pdo->query(
    'SELECT categoria, COUNT(*) AS total
     FROM documentos_trabalho
     WHERE ativo = 1
     GROUP BY categoria
     ORDER BY categoria'
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Documentos do Trabalho | RWDEV Admin</title>
  <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
  <?php docs_render_header(); ?>

  <main class="app-container">
    <section class="page-title">
      <span>Administração interna</span>
      <h1>Documentos do Trabalho</h1>
      <p>Arquivos pessoais e profissionais não devem ser commitados no Git. Use apenas o upload protegido do painel.</p>
    </section>

    <?php docs_render_nav('index.php'); ?>

    <div class="metrics-grid">
      <?php if (!$cards): ?>
        <article class="metric-card"><span>Documentos</span><strong>0</strong></article>
      <?php endif; ?>
      <?php foreach (array_slice($cards, 0, 4) as $card): ?>
        <article class="metric-card"><span><?= e((string) $card['categoria']) ?></span><strong><?= (int) $card['total'] ?></strong></article>
      <?php endforeach; ?>
    </div>

    <section class="panel">
      <div class="panel-head">
        <h2>Documentos cadastrados</h2>
        <div class="ponto-actions">
          <a class="btn" href="novo.php">Novo documento</a>
          <a class="btn outline" href="categorias.php">Categorias</a>
        </div>
      </div>

      <form class="ponto-filtros docs-filtros" method="get">
        <label>Categoria
          <select name="categoria">
            <option value="">Todas</option>
            <?php foreach ($categorias as $categoria): ?>
              <option value="<?= e((string) $categoria) ?>" <?= $categoriaFiltro === $categoria ? 'selected' : '' ?>><?= e((string) $categoria) ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <label>Empresa<input name="empresa" value="<?= e($empresaFiltro) ?>"></label>
        <label>Ano<input name="ano" inputmode="numeric" value="<?= $anoFiltro ?: '' ?>" placeholder="2026"></label>
        <button class="btn small" type="submit">Filtrar</button>
      </form>

      <div class="table-wrap">
        <table>
          <thead><tr><th>Título</th><th>Categoria</th><th>Empresa</th><th>Data</th><th>Validade</th><th>Vínculo</th><th>Status</th><th>Ações</th></tr></thead>
          <tbody>
            <?php if (!$documentos): ?><tr><td colspan="8">Nenhum documento encontrado.</td></tr><?php endif; ?>
            <?php foreach ($documentos as $documento): ?>
              <tr>
                <td><strong><?= e((string) $documento['titulo']) ?></strong><br><small><?= e((string) $documento['arquivo']) ?></small></td>
                <td><?= e((string) $documento['categoria']) ?></td>
                <td><?= e((string) $documento['empresa']) ?><br><small><?= e((string) $documento['cargo']) ?></small></td>
                <td><?= $documento['data_documento'] ? date('d/m/Y', strtotime($documento['data_documento'])) : '-' ?></td>
                <td><?= $documento['data_validade'] ? date('d/m/Y', strtotime($documento['data_validade'])) : '-' ?></td>
                <td><?= $documento['ponto_data'] ? date('d/m/Y', strtotime($documento['ponto_data'])) . ' - Loja ' . e((string) $documento['codigo_loja']) : '-' ?></td>
                <td><span class="status <?= (int) $documento['ativo'] === 1 ? 'status-concluido' : 'status-expirado' ?>"><?= (int) $documento['ativo'] === 1 ? 'ativo' : 'inativo' ?></span></td>
                <td class="ponto-table-actions">
                  <a href="visualizar.php?id=<?= (int) $documento['id'] ?>">Ver</a>
                  <a href="editar.php?id=<?= (int) $documento['id'] ?>">Editar</a>
                  <a class="danger-link" href="excluir.php?id=<?= (int) $documento['id'] ?>">Excluir</a>
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
