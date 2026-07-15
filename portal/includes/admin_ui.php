<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';

function admin_url(string $caminho, string $prefixo = ''): string
{
    return $prefixo . $caminho;
}

function admin_menu_itens(): array
{
    return [
        ['permissao' => 'dashboard.visualizar', 'href' => 'dashboard.php', 'rotulo' => '🏠 Dashboard'],
        ['permissao' => 'clientes.visualizar', 'href' => 'clientes.php', 'rotulo' => '👥 Clientes'],
        ['permissao' => 'convites_clientes.visualizar', 'href' => 'convites.php', 'rotulo' => '✉️ Convites'],
        ['permissao' => 'projetos.visualizar', 'href' => 'projetos.php', 'rotulo' => '📁 Projetos'],
        ['permissao' => 'solicitacoes.visualizar', 'href' => 'solicitacoes.php', 'rotulo' => '📋 Solicitacoes'],
        ['permissao' => 'depoimentos.visualizar', 'href' => 'depoimentos.php', 'rotulo' => '💬 Depoimentos'],
        ['permissao' => 'diagnostico.visualizar', 'href' => 'diagnostico-metricas.php', 'rotulo' => '📊 Diagnostico'],
        ['permissao' => 'ponto.visualizar', 'href' => 'ponto/index.php', 'rotulo' => '⏱️ Soni Ponto'],
        ['permissao' => 'documentos.visualizar', 'href' => 'documentos-trabalho/index.php', 'rotulo' => '📄 Documentos'],
        ['permissao' => 'admins.visualizar', 'href' => 'administradores.php', 'rotulo' => '🛡️ Administradores'],
        ['permissao' => 'auditoria.visualizar', 'href' => 'auditoria.php', 'rotulo' => '🕵️ Auditoria', 'perfil' => 'superadministrador'],
    ];
}

function admin_perfil_rotulo(string $perfil): string
{
    $rotulos = [
        'superadministrador' => 'Superadministrador',
        'administrador_modulo' => 'Administrador de módulo',
        'visualizador' => 'Visualizador',
    ];

    return $rotulos[$perfil] ?? $perfil;
}

function admin_ambiente_rotulo(): string
{
    $ambiente = defined('APP_ENV') ? (string) APP_ENV : 'production';

    return strtolower($ambiente) === 'production' ? 'Produção' : ucfirst($ambiente);
}

function admin_versao_sistema(): string
{
    static $versao = null;

    if ($versao !== null) {
        return $versao;
    }

    $versionPath = dirname(__DIR__, 2) . '/VERSION.md';
    $conteudo = is_file($versionPath) ? (string) file_get_contents($versionPath) : '';

    if (preg_match('/Versao:\s*([0-9]+(?:\.[0-9]+){1,2})/i', $conteudo, $matches)) {
        $versao = $matches[1];
        return $versao;
    }

    $versao = 'indisponivel';
    return $versao;
}

function admin_ultimo_acesso_rotulo(?string $ultimoAcesso): string
{
    if (!$ultimoAcesso) {
        return 'Primeiro acesso';
    }

    $timestamp = strtotime($ultimoAcesso);
    if (!$timestamp) {
        return 'Primeiro acesso';
    }

    return 'Último acesso: ' . date('d/m/Y H:i', $timestamp);
}

function admin_render_header(string $prefixo = ''): void
{
    $admin = admin_atual() ?? [];
    $perfil = (string) ($admin['perfil'] ?? 'superadministrador');
    $perfilRotulo = admin_perfil_rotulo($perfil);
    $ultimoAcesso = admin_ultimo_acesso_rotulo($_SESSION['admin_ultimo_acesso_anterior'] ?? null);
    $ambiente = admin_ambiente_rotulo();
    $versao = admin_versao_sistema();
    ?>
    <header class="app-header admin">
      <a href="<?= e(admin_url('dashboard.php', $prefixo)) ?>" class="marca">RWDEV<br>Command Center</a>
      <nav>
        <?php foreach (admin_menu_itens() as $item): ?>
          <?php if (($item['perfil'] ?? null) && ($admin['perfil'] ?? '') !== $item['perfil']) { continue; } ?>
          <?php if (usuario_pode($item['permissao'])): ?>
            <a href="<?= e(admin_url($item['href'], $prefixo)) ?>"><span class="admin-menu-item"><?= e($item['rotulo']) ?></span></a>
          <?php endif; ?>
        <?php endforeach; ?>
        <span class="admin-user">
          <span>&#128994; Online</span>
          <strong><?= e((string) ($_SESSION['admin_nome'] ?? $admin['nome'] ?? 'Admin')) ?></strong>
          <span><?= e($perfilRotulo) ?></span>
          <small><?= e($ultimoAcesso) ?></small>
          <small>&#128994; <?= e($ambiente) ?> | Versão <?= e($versao) ?></small>
        </span>
        <a href="<?= e(admin_url('../logout.php', $prefixo)) ?>"><span class="admin-menu-item">🚪 Sair</span></a>
      </nav>
    </header>
    <?php
}

function admin_permissoes_selecionadas(array $origem): array
{
    $validas = array_values(array_unique(array_merge(...array_values(permissoes_admin_disponiveis()))));
    $selecionadas = [];

    foreach ($origem as $permissao) {
        $permissao = trim((string) $permissao);
        if (in_array($permissao, $validas, true)) {
            $selecionadas[] = $permissao;
        }
    }

    return array_values(array_unique($selecionadas));
}
