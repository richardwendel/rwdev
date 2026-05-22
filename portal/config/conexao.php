<?php
declare(strict_types=1);

date_default_timezone_set('America/Sao_Paulo');

$host = 'localhost';
$banco = 'u724577237_rwdev_portal';
$usuario = 'u724577237_rwdev_admin';
$senha = 'M@luf307';

define('BASE_URL', 'https://www.rwdev.com.br');
define('ADMIN_EMAIL_NOTIFICACAO', 'rwdevtech@gmail.com');

try {
    $pdo = new PDO(
        "mysql:host={$host};dbname={$banco};charset=utf8mb4",
        $usuario,
        $senha
    );

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
} catch (PDOException $erro) {
    error_log('Erro de conexao PDO: ' . $erro->getMessage());
    http_response_code(500);
    die('Erro ao conectar ao banco de dados.');
}
