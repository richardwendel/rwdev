<?php
declare(strict_types=1);

// Ação administrativa para marcar um depoimento como recusado.
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../includes/auth.php';

exigir_admin();
validar_csrf();

$id = (int) ($_POST['id'] ?? 0);

if ($id > 0) {
    $stmt = $pdo->prepare('UPDATE depoimentos SET status = "recusado" WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $_SESSION['flash_depoimento'] = 'Depoimento recusado.';
}

redirect('depoimentos.php');
