<?php
declare(strict_types=1);

require_once __DIR__ . '/funcoes.php';

function cliente_logado(): bool
{
    return isset($_SESSION['cliente_id']);
}

function admin_logado(): bool
{
    return isset($_SESSION['admin_id']);
}

function exigir_cliente(): void
{
    if (isset($_SESSION['admin_id']) && !isset($_SESSION['cliente_id'])) {
        redirect('../admin/dashboard.php');
    }

    if (isset($_SESSION['admin_id'], $_SESSION['cliente_id'])) {
        unset($_SESSION['admin_id'], $_SESSION['admin_nome']);
    }

    if (!cliente_logado()) {
        redirect('../cliente/login.php');
    }
}

function exigir_admin(): void
{
    if (isset($_SESSION['cliente_id']) && !isset($_SESSION['admin_id'])) {
        redirect('../cliente/dashboard.php');
    }

    if (isset($_SESSION['admin_id'], $_SESSION['cliente_id'])) {
        unset($_SESSION['cliente_id'], $_SESSION['cliente_nome']);
    }

    if (!admin_logado()) {
        redirect('../admin/login.php');
    }
}
