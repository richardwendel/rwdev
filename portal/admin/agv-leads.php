<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/../includes/admin_ui.php';
require_once __DIR__ . '/../includes/agv_leads.php';

exigir_admin();

$admin = admin_atual();

if (($admin['perfil'] ?? '') !== 'superadministrador') {
    http_response_code(403);
    exit('Acesso negado. Esta área é exclusiva do responsável pela RWDEV.');
}

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('X-Robots-Tag: noindex, nofollow', true);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validar_csrf();

    $leadId = (int) ($_POST['lead_id'] ?? 0);
    $status = trim((string) ($_POST['status'] ?? ''));

    if ($leadId > 0 && in_array($status, agv_status_disponiveis(), true)) {
        $stmtAtualizar = $pdo->prepare(
            'UPDATE agv_leads SET status = :status, updated_at = CURRENT_TIMESTAMP WHERE id = :id'
        );
        $stmtAtualizar->execute([':status' => $status, ':id' => $leadId]);
    }

    redirect('agv-leads.php');
}

$leads = $pdo->query(
    'SELECT id, codigo, nome, whatsapp, cidade, veiculo, ano, placa, created_at, origem, status
     FROM agv_leads
     ORDER BY created_at DESC, id DESC
     LIMIT 250'
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Leads AGV | Admin RWDEV</title>
  <link rel="stylesheet" href="<?= e(asset_url('assets/css/style.css')) ?>">
  <style>
    .agv-leads-table td, .agv-leads-table th { vertical-align: top; }
    .agv-leads-table .agv-nowrap { white-space: nowrap; }
    .agv-status-form { display: grid; gap: 8px; min-width: 180px; }
    .agv-status-form select, .agv-status-form button { min-height: 38px; }
    .agv-lead-contato { display: grid; gap: 3px; }
    @media (max-width: 760px) { .agv-leads-table { min-width: 1080px; } }
  </style>
</head>
<body>
  <?php admin_render_header(); ?>

  <main class="app-container">
    <section class="page-title">
      <span>Captação comercial</span>
      <h1>Leads da página AGV</h1>
      <p>Solicitações registradas antes do encaminhamento ao WhatsApp do Carlos.</p>
    </section>

    <section class="panel">
      <?php if (!$leads): ?>
        <p class="empty">Nenhuma solicitação AGV registrada até o momento.</p>
      <?php else: ?>
        <div class="table-wrap">
          <table class="agv-leads-table">
            <thead>
              <tr>
                <th>Código</th>
                <th>Cliente</th>
                <th>Local</th>
                <th>Veículo</th>
                <th>Data/hora</th>
                <th>Origem</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($leads as $lead): ?>
                <tr>
                  <td class="agv-nowrap"><strong><?= e((string) $lead['codigo']) ?></strong></td>
                  <td>
                    <div class="agv-lead-contato">
                      <strong><?= e((string) $lead['nome']) ?></strong>
                      <span><?= e(agv_formatar_whatsapp((string) $lead['whatsapp'])) ?></span>
                    </div>
                  </td>
                  <td><?= e((string) $lead['cidade']) ?></td>
                  <td>
                    <strong><?= e((string) $lead['veiculo']) ?></strong><br>
                    <span><?= e((string) $lead['ano']) ?> · <?= e((string) $lead['placa']) ?></span>
                  </td>
                  <td class="agv-nowrap"><?= e(date('d/m/Y H:i', strtotime((string) $lead['created_at']))) ?></td>
                  <td><?= e((string) $lead['origem']) ?></td>
                  <td>
                    <form class="agv-status-form" method="post">
                      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                      <input type="hidden" name="lead_id" value="<?= (int) $lead['id'] ?>">
                      <select name="status" aria-label="Status do lead <?= e((string) $lead['codigo']) ?>">
                        <?php foreach (agv_status_disponiveis() as $statusDisponivel): ?>
                          <option value="<?= e($statusDisponivel) ?>" <?= $lead['status'] === $statusDisponivel ? 'selected' : '' ?>><?= e($statusDisponivel) ?></option>
                        <?php endforeach; ?>
                      </select>
                      <button type="submit">Atualizar</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </section>
  </main>
</body>
</html>
