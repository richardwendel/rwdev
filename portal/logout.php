<?php
require_once __DIR__ . '/includes/funcoes.php';
require_once __DIR__ . '/includes/auditoria.php';

$destino = isset($_SESSION['admin_id']) ? 'admin/login.php' : 'cliente/login.php';

if (isset($_SESSION['admin_id'])) {
    registrar_auditoria('autenticacao', 'logout', 'admins', (int) $_SESSION['admin_id'], [], [], 'sucesso', null, 'Logout administrativo realizado');
}

session_unset();
session_destroy();

redirect($destino);
