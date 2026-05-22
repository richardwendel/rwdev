<?php
declare(strict_types=1);

function avisar_admin_nova_solicitacao(array $cliente, array $projeto, int $solicitacaoId): void
{
    if (!defined('ADMIN_EMAIL_NOTIFICACAO') || ADMIN_EMAIL_NOTIFICACAO === '') {
        return;
    }

    $assunto = 'Nova solicitacao no Canal do Cliente RWDEV #' . $solicitacaoId;
    $link = rtrim(BASE_URL, '/') . '/portal/admin/solicitacoes.php?id=' . $solicitacaoId;
    $mensagem = "Nova solicitacao recebida.\n\n"
        . "Cliente: " . ($cliente['nome'] ?? '') . "\n"
        . "Empresa: " . ($cliente['empresa'] ?? '') . "\n"
        . "Projeto: " . ($projeto['nome'] ?? '') . "\n"
        . "Link: " . $link . "\n";

    $headers = [
        'From: RWDEV <no-reply@rwdev.com.br>',
        'Reply-To: ' . ($cliente['email'] ?? ADMIN_EMAIL_NOTIFICACAO),
        'Content-Type: text/plain; charset=UTF-8',
    ];

    @mail(ADMIN_EMAIL_NOTIFICACAO, $assunto, $mensagem, implode("\r\n", $headers));
}
