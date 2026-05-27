<?php
declare(strict_types=1);

// Ação administrativa para aprovar um depoimento pendente.
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../includes/auth.php';

exigir_admin();
validar_csrf();

$id = (int) ($_POST['id'] ?? 0);

if ($id > 0) {
    $colunasDepoimentos = $pdo->query('SHOW COLUMNS FROM depoimentos')->fetchAll(PDO::FETCH_COLUMN);
    $temRespostaAdmin = in_array('resposta_admin', $colunasDepoimentos, true);
    $temRespondidoEm = in_array('respondido_em', $colunasDepoimentos, true);
    $respostaAdmin = trim((string) ($_POST['resposta_admin'] ?? ''));

    if ($temRespostaAdmin) {
        $campos = 'status = "aprovado", resposta_admin = :resposta_admin';
        $parametros = [
            ':id' => $id,
            ':resposta_admin' => $respostaAdmin !== '' ? $respostaAdmin : null,
        ];

        if ($temRespondidoEm) {
            $campos .= ', respondido_em = :respondido_em';
            $parametros[':respondido_em'] = $respostaAdmin !== '' ? date('Y-m-d H:i:s') : null;
        }

        $stmt = $pdo->prepare("UPDATE depoimentos SET {$campos} WHERE id = :id");
        $stmt->execute($parametros);
    } else {
        $stmt = $pdo->prepare('UPDATE depoimentos SET status = "aprovado" WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }

    $_SESSION['flash_depoimento'] = 'Depoimento aprovado com sucesso.';
}

redirect('depoimentos.php');
