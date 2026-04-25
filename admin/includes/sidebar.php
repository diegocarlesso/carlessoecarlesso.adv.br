<?php
// admin/includes/sidebar.php
if (!defined('CARLESSO_CMS')) exit;
$currentFile = basename($_SERVER['PHP_SELF']);
$user = Auth::user();
function isActive(string $file): string {
    global $currentFile;
    return $currentFile === $file ? 'active' : '';
}
?>
<aside class="sidebar" id="sidebar">
  <!-- Brand -->
  <div class="sidebar-brand">
    <div class="sidebar-logo">⚖</div>
    <div class="brand-text">
      <div class="name">Carlesso & Carlesso</div>
      <div class="subtitle">Painel CMS</div>
    </div>
  </div>

  <!-- Menu principal -->
  <div class="sidebar-section">
    <div class="sidebar-section-label">Principal</div>
    <ul class="sidebar-nav">
      <li><a href="/admin/index.php" class="<?= isActive('index.php') ?>">
        <span class="icon">⊞</span> Dashboard
      </a></li>
    </ul>
  </div>

  <!-- Conteúdo -->
  <div class="sidebar-section">
    <div class="sidebar-section-label">Conteúdo</div>
    <ul class="sidebar-nav">
      <li><a href="/admin/paginas.php" class="<?= isActive('paginas.php') ?>">
        <span class="icon">📄</span> Páginas
      </a></li>
      <li><a href="/admin/postagens.php" class="<?= isActive('postagens.php') ?>">
        <span class="icon">📝</span> Produções
      </a></li>
      <li><a href="/admin/conteudos.php" class="<?= isActive('conteudos.php') ?>">
        <span class="icon">✏️</span> Seções
      </a></li>
      <li><a href="/admin/media.php" class="<?= isActive('media.php') ?>">
        <span class="icon">🖼️</span> Biblioteca de Mídia
      </a></li>
    </ul>
  </div>

  <!-- Mensagens -->
  <?php
  $unreadCount = 0;
  try { $unreadCount = (int)(Database::fetchOne('SELECT COUNT(*) AS c FROM contatos WHERE lido=0')['c'] ?? 0); } catch(Exception $e){}
  ?>
  <div class="sidebar-section">
    <div class="sidebar-section-label">Comunicação</div>
    <ul class="sidebar-nav">
      <li><a href="/admin/contatos.php" class="<?= isActive('contatos.php') ?>">
        <span class="icon">✉️</span> Mensagens
        <?php if ($unreadCount > 0): ?>
          <span class="badge"><?= $unreadCount ?></span>
        <?php endif; ?>
      </a></li>
    </ul>
  </div>

  <!-- Configurações -->
  <div class="sidebar-section">
    <div class="sidebar-section-label">Configurações</div>
    <ul class="sidebar-nav">
      <li><a href="/admin/seo.php" class="<?= isActive('seo.php') ?>">
        <span class="icon">🔍</span> SEO
      </a></li>
      <li><a href="/admin/aparencia.php" class="<?= isActive('aparencia.php') ?>">
        <span class="icon">🎨</span> Aparência
      </a></li>
      <li><a href="/admin/configs.php" class="<?= isActive('configs.php') ?>">
        <span class="icon">⚙️</span> Configurações
      </a></li>
      <?php if (Auth::isAdmin()): ?>
      <li><a href="/admin/usuarios.php" class="<?= isActive('usuarios.php') ?>">
        <span class="icon">👥</span> Usuários
      </a></li>
      <?php endif; ?>
    </ul>
  </div>

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
    <a href="/admin/logout.php" class="logout" title="Sair">⏻</a>
  </div>
</aside>
