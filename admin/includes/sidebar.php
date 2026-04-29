<?php
// admin/includes/sidebar.php
// Sidebar com filtragem por permissão granular (rev v1.5).
// Cada link só aparece se o usuário tem a capability necessária.
if (!defined('CARLESSO_CMS')) exit;

$currentFile = basename($_SERVER['PHP_SELF']);
$user = Auth::user();

function isActive(string $file): string {
    global $currentFile;
    return $currentFile === $file ? 'active' : '';
}

/**
 * Renderiza um item de menu apenas se o usuário tem a permissão necessária.
 * Use null em $perm para "sempre visível" (ex: dashboard).
 */
function navItem(string $href, ?string $perm, string $icon, string $label, ?string $extra = ''): void {
    if ($perm !== null && !Auth::can($perm)) return;
    $active = isActive(basename($href));
    echo '<li><a href="' . htmlspecialchars($href, ENT_QUOTES) . '" class="' . $active . '">'
       . '<span class="icon">' . $icon . '</span> ' . htmlspecialchars($label, ENT_QUOTES);
    if ($extra) echo $extra;
    echo '</a></li>';
}
?>
<aside class="sidebar" id="sidebar">
  <div class="sidebar-brand">
    <div class="sidebar-logo">⚖</div>
    <div class="brand-text">
      <div class="name">Carlesso &amp; Carlesso</div>
      <div class="subtitle">Painel CMS</div>
    </div>
  </div>

  <!-- Principal -->
  <div class="sidebar-section">
    <div class="sidebar-section-label">Principal</div>
    <ul class="sidebar-nav">
      <?php navItem('/admin/index.php', null, '⊞', 'Dashboard'); ?>
    </ul>
  </div>

  <!-- Conteúdo -->
  <div class="sidebar-section">
    <div class="sidebar-section-label">Conteúdo</div>
    <ul class="sidebar-nav">
      <?php
      navItem('/admin/postagens.php', 'posts.edit', '📝', 'Produções');
      navItem('/admin/paginas.php',   'pages.edit', '📄', 'Páginas');
      navItem('/admin/conteudos.php', 'pages.edit', '✏️', 'Seções');
      navItem('/admin/media.php',     'media.upload', '🖼️', 'Biblioteca de Mídia');
      ?>
    </ul>
  </div>

  <!-- Mensagens — visível a todos os usuários logados (decisão de produto) -->
  <?php
  $unreadCount = 0;
  try {
      $unreadCount = (int) (Database::fetchOne('SELECT COUNT(*) AS c FROM contatos WHERE lido=0')['c'] ?? 0);
  } catch (\Throwable $e) {}
  $badgeHtml = $unreadCount > 0 ? '<span class="badge">' . $unreadCount . '</span>' : '';
  ?>
  <div class="sidebar-section">
    <div class="sidebar-section-label">Comunicação</div>
    <ul class="sidebar-nav">
      <?php navItem('/admin/contatos.php', null, '✉️', 'Mensagens', $badgeHtml); ?>
    </ul>
  </div>

  <!-- Configurações -->
  <?php
  $hasAnyConfig = Auth::can('settings.manage') || Auth::can('appearance.manage') || Auth::can('users.manage');
  if ($hasAnyConfig):
  ?>
  <div class="sidebar-section">
    <div class="sidebar-section-label">Configurações</div>
    <ul class="sidebar-nav">
      <?php
      navItem('/admin/seo.php',        'settings.manage',   '🔍', 'SEO');
      navItem('/admin/aparencia.php',  'appearance.manage', '🎨', 'Aparência');
      navItem('/admin/configs.php',    'settings.manage',   '⚙️', 'Configurações');
      navItem('/admin/usuarios.php',   'users.manage',      '👥', 'Usuários');
      navItem('/admin/permissoes.php', 'users.manage',      '🔐', 'Permissões');
      ?>
    </ul>
  </div>
  <?php endif; ?>

  <!-- Ver site -->
  <div class="sidebar-section">
    <ul class="sidebar-nav">
      <li><a href="/" target="_blank">
        <span class="icon">🌐</span> Ver Site
      </a></li>
    </ul>
  </div>

  <!-- User info -->
  <div class="sidebar-user">
    <div class="user-avatar"><?= strtoupper(substr($user['full_name'] ?: $user['username'], 0, 1)) ?></div>
    <div class="user-info">
      <div class="name"><?= e($user['full_name'] ?: $user['username']) ?></div>
      <div class="role"><?= e($user['role']) ?></div>
    </div>
    <a href="/admin/perfil.php" class="logout" title="Meu perfil" style="margin-right:6px">⚙</a>
    <a href="/admin/logout.php" class="logout" title="Sair">⏻</a>
  </div>
</aside>
