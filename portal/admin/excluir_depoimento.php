<?php
declare(strict_types=1);

// Ação administrativa para excluir um depoimento e sua foto, quando existir.
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../includes/auth.php';

exigir_admin();
validar_csrf();

$id = (int) ($_POST['id'] ?? 0);

if ($id > 0) {
    $stmtBusca = $pdo->prepare('SELECT foto FROM depoimentos WHERE id = :id LIMIT 1');
    $stmtBusca->execute([':id' => $id]);
    $depoimento = $stmtBusca->fetch();

    if ($depoimento) {
        excluir_foto_depoimento($depoimento['foto'] ?? null);

        $stmtExclusao = $pdo->prepare('DELETE FROM depoimentos WHERE id = :id');
        $stmtExclusao->execute([':id' => $id]);

        $_SESSION['flash_depoimento'] = 'Depoimento excluído.';
    }
}

redirect('depoimentos.php');
