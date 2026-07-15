<?php
declare(strict_types=1);

date_default_timezone_set('America/Sao_Paulo');

$envPath = dirname(__DIR__, 2) . '/.env';
$env = [];

if (is_file($envPath)) {
    $linhasEnv = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];

    foreach ($linhasEnv as $linhaEnv) {
        $linhaEnv = trim($linhaEnv);

        if ($linhaEnv === '' || str_starts_with($linhaEnv, '#') || !str_contains($linhaEnv, '=')) {
            continue;
        }

        [$chaveEnv, $valorEnv] = explode('=', $linhaEnv, 2);
        $env[trim($chaveEnv)] = trim($valorEnv, " \t\n\r\0\x0B\"'");
    }
}

$host = $env['DB_HOST'] ?? getenv('DB_HOST') ?: 'localhost';

$banco = $env['DB_NAME'] ?? getenv('DB_NAME') ?: '';

$usuario = $env['DB_USER'] ?? getenv('DB_USER') ?: '';

$senha = $env['DB_PASS'] ?? getenv('DB_PASS') ?: '';

define('BASE_URL', 'https://www.rwdev.com.br');
define('ADMIN_EMAIL_NOTIFICACAO', 'rwdevtech@gmail.com');
define('APP_ENV', $env['APP_ENV'] ?? getenv('APP_ENV') ?: 'production');

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
    if (defined('RWDEV_JSON_ENDPOINT') && RWDEV_JSON_ENDPOINT === true) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'sucesso' => false,
            'mensagem' => 'Erro ao conectar ao banco de dados.',
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    die('Erro ao conectar ao banco de dados.');
}
