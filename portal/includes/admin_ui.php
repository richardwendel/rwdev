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
    ];
}

function admin_render_header(string $prefixo = ''): void
{
    $admin = admin_atual() ?? [];
    $perfis = perfis_admin();
    $perfil = (string) ($admin['perfil'] ?? 'superadministrador');
    $perfilRotulo = $perfis[$perfil] ?? $perfil;
    ?>
    <header class="app-header admin">
      <a href="<?= e(admin_url('dashboard.php', $prefixo)) ?>" class="marca">RWDEV Admin</a>
      <nav>
        <?php foreach (admin_menu_itens() as $item): ?>
          <?php if (usuario_pode($item['permissao'])): ?>
            <a href="<?= e(admin_url($item['href'], $prefixo)) ?>"><span class="admin-menu-item"><?= e($item['rotulo']) ?></span></a>
          <?php endif; ?>
        <?php endforeach; ?>
        <span class="admin-user">Usuario: <?= e((string) ($admin['nome'] ?? $_SESSION['admin_nome'] ?? 'Admin')) ?> | <?= e($perfilRotulo) ?></span>
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
