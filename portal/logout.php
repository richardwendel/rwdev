<?php
require_once __DIR__ . '/includes/funcoes.php';

$destino = isset($_SESSION['admin_id']) ? 'admin/login.php' : 'cliente/login.php';

session_unset();
session_destroy();

redirect($destino);
