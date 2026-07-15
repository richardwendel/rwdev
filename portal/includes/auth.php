<?php
declare(strict_types=1);

require_once __DIR__ . '/funcoes.php';

function admin_coluna_existe(PDO $pdo, string $coluna): bool
{
    static $cache = [];

    if (!array_key_exists($coluna, $cache)) {
        $stmt = $pdo->prepare(
            'SELECT COUNT(*)
             FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = "admins" AND COLUMN_NAME = :coluna'
        );
        $stmt->execute([':coluna' => $coluna]);
        $cache[$coluna] = (int) $stmt->fetchColumn() > 0;
    }

    return $cache[$coluna];
}

function admin_tabela_existe(PDO $pdo, string $tabela): bool
{
    static $cache = [];

    if (!array_key_exists($tabela, $cache)) {
        $stmt = $pdo->prepare(
            'SELECT COUNT(*)
             FROM INFORMATION_SCHEMA.TABLES
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :tabela'
        );
        $stmt->execute([':tabela' => $tabela]);
        $cache[$tabela] = (int) $stmt->fetchColumn() > 0;
    }

    return $cache[$tabela];
}

function cliente_logado(): bool
{
    return isset($_SESSION['cliente_id']);
}

function admin_logado(): bool
{
    return isset($_SESSION['admin_id']);
}

function perfis_admin(): array
{
    return [
        'superadministrador' => 'Superadministrador',
        'administrador_modulo' => 'Administrador de modulo',
        'visualizador' => 'Visualizador',
    ];
}

function permissoes_admin_disponiveis(): array
{
    return [
        'Dashboard' => ['dashboard.visualizar'],
        'Soni Ponto' => ['ponto.visualizar', 'ponto.criar', 'ponto.editar', 'ponto.excluir', 'ponto.duplicar'],
        'Lojas' => ['lojas.visualizar', 'lojas.criar', 'lojas.editar', 'lojas.excluir'],
        'Trajetos' => ['trajetos.visualizar', 'trajetos.criar', 'trajetos.editar', 'trajetos.excluir'],
        'Resumo mensal' => ['resumo.visualizar'],
        'Documentos do Trabalho' => ['documentos.visualizar', 'documentos.criar', 'documentos.editar', 'documentos.excluir'],
        'Clientes' => ['clientes.visualizar', 'clientes.editar'],
        'Convites de clientes' => ['convites_clientes.visualizar', 'convites_clientes.criar'],
        'Projetos' => ['projetos.visualizar', 'projetos.criar', 'projetos.editar'],
        'Solicitacoes' => ['solicitacoes.visualizar', 'solicitacoes.editar'],
        'Depoimentos' => ['depoimentos.visualizar', 'depoimentos.editar', 'depoimentos.excluir'],
        'Diagnostico' => ['diagnostico.visualizar'],
        'Administradores' => ['admins.visualizar', 'admins.criar', 'admins.editar', 'admins.excluir', 'admins.convidar'],
        'Auditoria' => ['auditoria.visualizar'],
    ];
}

function permissoes_perfil_admin(string $perfil): array
{
    if ($perfil === 'superadministrador') {
        return array_values(array_unique(array_merge(...array_values(permissoes_admin_disponiveis()))));
    }

    if ($perfil === 'visualizador') {
        return array_values(array_filter(
            array_values(array_unique(array_merge(...array_values(permissoes_admin_disponiveis())))),
            static fn (string $permissao): bool => substr($permissao, -11) === '.visualizar'
        ));
    }

    return [];
}

function permissoes_mestre_dos_magos(): array
{
    return [
        'dashboard.visualizar',
        'ponto.visualizar',
        'ponto.criar',
        'ponto.editar',
        'ponto.excluir',
        'ponto.duplicar',
        'resumo.visualizar',
        'lojas.visualizar',
        'lojas.criar',
        'lojas.editar',
        'lojas.excluir',
        'trajetos.visualizar',
        'trajetos.criar',
        'trajetos.editar',
        'trajetos.excluir',
    ];
}

function admin_registrar_evento(PDO $pdo, string $evento, ?string $email = null, string $mensagem = ''): void
{
    try {
        if (!admin_tabela_existe($pdo, 'logs_seguranca')) {
            return;
        }

        $stmt = $pdo->prepare(
            'INSERT INTO logs_seguranca (tipo_evento, email, ip, tipo_usuario, mensagem)
             VALUES (:tipo_evento, :email, :ip, "admin", :mensagem)'
        );
        $stmt->execute([
            ':tipo_evento' => $evento,
            ':email' => $email,
            ':ip' => $_SERVER['REMOTE_ADDR'] ?? '',
            ':mensagem' => $mensagem,
        ]);
    } catch (Throwable $e) {
        error_log('Falha ao registrar log de seguranca: ' . $e->getMessage());
    }
}

function admin_atual(bool $recarregar = false): ?array
{
    global $pdo;

    if (!isset($_SESSION['admin_id'])) {
        return null;
    }

    if (!$recarregar && isset($_SESSION['admin_atual']) && is_array($_SESSION['admin_atual'])) {
        return $_SESSION['admin_atual'];
    }

    $stmt = $pdo->prepare('SELECT * FROM admins WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => (int) $_SESSION['admin_id']]);
    $admin = $stmt->fetch();

    if (!$admin) {
        return null;
    }

    $admin['perfil'] = admin_coluna_existe($pdo, 'perfil') ? ($admin['perfil'] ?: 'superadministrador') : 'superadministrador';
    $admin['ativo'] = admin_coluna_existe($pdo, 'ativo') ? (int) $admin['ativo'] : 1;
    $admin['permissoes'] = permissoes_perfil_admin((string) $admin['perfil']);

    if ($admin['perfil'] !== 'superadministrador' && admin_tabela_existe($pdo, 'admin_permissoes')) {
        $stmtPermissoes = $pdo->prepare('SELECT permissao FROM admin_permissoes WHERE admin_id = :admin_id');
        $stmtPermissoes->execute([':admin_id' => (int) $admin['id']]);
        $admin['permissoes'] = $stmtPermissoes->fetchAll(PDO::FETCH_COLUMN) ?: $admin['permissoes'];
    }

    $_SESSION['admin_nome'] = $admin['nome'];
    $_SESSION['admin_perfil'] = $admin['perfil'];
    $_SESSION['admin_atual'] = $admin;

    return $admin;
}

function usuario_pode(string $permissao): bool
{
    $admin = admin_atual();

    if (!$admin || (int) ($admin['ativo'] ?? 0) !== 1) {
        return false;
    }

    if (($admin['perfil'] ?? '') === 'superadministrador') {
        return true;
    }

    return in_array($permissao, $admin['permissoes'] ?? [], true);
}

function exigir_permissao(string $permissao): void
{
    global $pdo;

    if (usuario_pode($permissao)) {
        return;
    }

    $admin = admin_atual();
    admin_registrar_evento(
        $pdo,
        'acesso_negado',
        $admin['email'] ?? null,
        'Permissao negada: ' . $permissao . ' | URI: ' . ($_SERVER['REQUEST_URI'] ?? '')
    );

    if (!function_exists('registrar_auditoria')) {
        $auditoriaPath = __DIR__ . '/auditoria.php';
        if (is_file($auditoriaPath)) {
            require_once $auditoriaPath;
        }
    }

    if (function_exists('registrar_auditoria')) {
        registrar_auditoria(
            'seguranca',
            'acesso_negado',
            'permissao',
            null,
            [],
            ['permissao' => $permissao],
            'negado',
            'Permissao negada',
            'Tentativa de acesso sem permissao'
        );
    }

    http_response_code(403);
    exit('Acesso negado. Seu usuario nao possui permissao para esta acao.');
}

function exigir_cliente(): void
{
    if (isset($_SESSION['admin_id']) && !isset($_SESSION['cliente_id'])) {
        redirect(portal_url('admin/dashboard.php'));
    }

    if (isset($_SESSION['admin_id'], $_SESSION['cliente_id'])) {
        unset($_SESSION['admin_id'], $_SESSION['admin_nome']);
    }

    if (!cliente_logado()) {
        redirect(portal_url('cliente/login.php'));
    }
}

function exigir_admin(): void
{
    if (isset($_SESSION['cliente_id']) && !isset($_SESSION['admin_id'])) {
        redirect(portal_url('cliente/dashboard.php'));
    }

    if (isset($_SESSION['admin_id'], $_SESSION['cliente_id'])) {
        unset($_SESSION['cliente_id'], $_SESSION['cliente_nome']);
    }

    if (!admin_logado()) {
        redirect(portal_url('admin/login.php'));
    }

    $admin = admin_atual(true);

    if (!$admin || (int) ($admin['ativo'] ?? 0) !== 1) {
        session_unset();
        session_destroy();
        redirect(portal_url('admin/login.php'));
    }
}
