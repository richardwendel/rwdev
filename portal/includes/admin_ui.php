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
        ['permissao' => 'solicitacoes.visualizar', 'href' => 'solicitacoes.php', 'rotulo' => '📋 Solicitacoes', 'mobile_rotulo' => '📋 Solicitações'],
        ['permissao' => 'depoimentos.visualizar', 'href' => 'depoimentos.php', 'rotulo' => '💬 Depoimentos'],
        ['permissao' => 'diagnostico.visualizar', 'href' => 'diagnostico-metricas.php', 'rotulo' => '📊 Diagnostico', 'mobile_rotulo' => '📊 Diagnóstico'],
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
    $linhas = is_file($versionPath) ? file($versionPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [];

    foreach ($linhas ?: [] as $linha) {
        $linha = trim(str_replace("\xEF\xBB\xBF", '', (string) $linha));

        if (preg_match('/^Vers(?:ao|ão):\s*([0-9]+(?:\.[0-9]+){1,2})\s*$/iu', $linha, $matches)) {
            $versao = $matches[1];
            return $versao;
        }
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
      <nav class="admin-desktop-nav">
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
      <button
        type="button"
        class="admin-mobile-menu-button"
        aria-label="Abrir menu administrativo"
        aria-expanded="false"
        aria-controls="admin-mobile-menu"
      >☰ Menu</button>
    </header>
    <div class="admin-mobile-menu-overlay" data-admin-mobile-close hidden></div>
    <aside class="admin-mobile-menu" id="admin-mobile-menu" aria-hidden="true">
      <nav class="admin-mobile-menu-nav" aria-label="Menu administrativo">
        <?php foreach (admin_menu_itens() as $item): ?>
          <?php if (($item['perfil'] ?? null) && ($admin['perfil'] ?? '') !== $item['perfil']) { continue; } ?>
          <?php if (usuario_pode($item['permissao'])): ?>
            <a href="<?= e(admin_url($item['href'], $prefixo)) ?>" data-admin-mobile-link><?= e($item['mobile_rotulo'] ?? $item['rotulo']) ?></a>
          <?php endif; ?>
        <?php endforeach; ?>
      </nav>
      <div class="admin-mobile-menu-separator"></div>
      <div class="admin-mobile-user">
        <span>🟢 Usuário Online</span>
        <strong><?= e((string) ($_SESSION['admin_nome'] ?? $admin['nome'] ?? 'Admin')) ?></strong>
        <span><?= e($perfilRotulo) ?></span>
        <small><?= e($ambiente) ?></small>
        <small>Versão <?= e($versao) ?></small>
      </div>
      <div class="admin-mobile-menu-separator"></div>
      <a class="admin-mobile-logout" href="<?= e(admin_url('../logout.php', $prefixo)) ?>" data-admin-mobile-link>🚪 Sair</a>
    </aside>
    <script>
      (() => {
        function initAdminMobileMenu() {
          const button = document.querySelector(".admin-mobile-menu-button");
          const panel = document.querySelector("#admin-mobile-menu");
          const overlay = document.querySelector(".admin-mobile-menu-overlay");

          if (!button || !panel || !overlay || document.documentElement.dataset.adminMobileMenuReady === "true") {
            return;
          }

          document.documentElement.dataset.adminMobileMenuReady = "true";

          let closeTimer = null;

          function setOpen(isOpen) {
            window.clearTimeout(closeTimer);
            button.setAttribute("aria-expanded", isOpen ? "true" : "false");
            panel.setAttribute("aria-hidden", isOpen ? "false" : "true");

            if (isOpen) {
              overlay.hidden = false;
            }

            panel.classList.toggle("is-open", isOpen);
            overlay.classList.toggle("is-open", isOpen);

            if (!isOpen) {
              closeTimer = window.setTimeout(() => {
                overlay.hidden = true;
              }, 240);
            }
          }

          document.addEventListener("click", (event) => {
            const target = event.target instanceof Element ? event.target : event.target?.parentElement;
            if (!target) {
              return;
            }

            if (target.closest(".admin-mobile-menu-button")) {
              event.preventDefault();
              setOpen(button.getAttribute("aria-expanded") !== "true");
              return;
            }

            if (target.closest(".admin-mobile-menu-overlay") || target.closest("[data-admin-mobile-link]")) {
              setOpen(false);
            }
          });

          document.addEventListener("keydown", (event) => {
            if (event.key === "Escape") {
              setOpen(false);
            }
          });
        }

        if (document.readyState === "loading") {
          document.addEventListener("DOMContentLoaded", initAdminMobileMenu, { once: true });
        } else {
          initAdminMobileMenu();
        }
      })();
    </script>
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
