<?php
declare(strict_types=1);

/**
 * Diagnostico temporario do erro HTTP 500 do SONI PONTO.
 *
 * Nao coleta dados da requisicao e nao exibe detalhes no navegador.
 * Remover este arquivo e seus requires apos a correcao definitiva.
 */

ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

$pontoDiagnosticoLog = __DIR__ . '/runtime-error.log';

if (!file_exists($pontoDiagnosticoLog)) {
    $pontoDiagnosticoHandle = @fopen($pontoDiagnosticoLog, 'x');
    if (is_resource($pontoDiagnosticoHandle)) {
        @chmod($pontoDiagnosticoLog, 0600);
        fclose($pontoDiagnosticoHandle);
    }
} elseif (is_file($pontoDiagnosticoLog)) {
    @chmod($pontoDiagnosticoLog, 0600);
}

ini_set('error_log', $pontoDiagnosticoLog);

register_shutdown_function(static function () use ($pontoDiagnosticoLog): void {
    $erro = error_get_last();
    $tiposFatais = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR];

    if ($erro === null || !in_array($erro['type'], $tiposFatais, true)) {
        return;
    }

    $mensagem = (string) ($erro['message'] ?? 'Erro fatal sem mensagem');
    $mensagem = preg_replace(
        [
            '/\b(password|passwd|senha|token|secret|api[_-]?key)\b\s*[:=]\s*[^\s,;]+/iu',
            '#(https?://)[^/\s:@]+:[^/\s@]+@#iu',
            '/[\r\n\t]+/',
        ],
        ['$1=[REDACTED]', '$1[REDACTED]@', ' '],
        $mensagem
    ) ?? 'Erro fatal com mensagem indisponivel';

    $tipos = [
        E_ERROR => 'E_ERROR',
        E_PARSE => 'E_PARSE',
        E_CORE_ERROR => 'E_CORE_ERROR',
        E_COMPILE_ERROR => 'E_COMPILE_ERROR',
        E_USER_ERROR => 'E_USER_ERROR',
    ];

    $registro = sprintf(
        "[%s] tipo=%s mensagem=%s arquivo=%s linha=%d%s",
        date('c'),
        $tipos[$erro['type']] ?? (string) $erro['type'],
        $mensagem,
        (string) ($erro['file'] ?? 'desconhecido'),
        (int) ($erro['line'] ?? 0),
        PHP_EOL
    );

    @error_log($registro, 3, $pontoDiagnosticoLog);
});
