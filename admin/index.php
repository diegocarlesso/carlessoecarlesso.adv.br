<?php
define('CARLESSO_CMS', true);
define('BASE_PATH', dirname(dirname(__DIR__)));
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/functions.php';

Auth::requireLogin();

$pageTitle = 'Dashboard';
$breadcrumb = ['Dashboard' => ''];

// Stats
$totalPages = (int) Database::fetchOne('SELECT COUNT(*) AS c FROM paginas')['c'];
$totalPosts = (int) Database::fetchOne('SELECT COUNT(*) AS c FROM postagens WHERE status="publicado"')['c'];
$totalUsers = (int) Database::fetchOne('SELECT COUNT(*) AS c FROM usuarios')['c'];
$totalMedia = (int) Database::fetchOne('SELECT COUNT(*) AS c FROM media')['c'];
$recentPosts= Database::fetchAll('SELECT * FROM postagens ORDER BY data_publicacao DESC LIMIT 5');
$recentPages= Database::fetchAll('SELECT * FROM paginas ORDER BY updated_at DESC LIMIT 5');

include __DIR__ . '/includes/header.php';
?>

<!-- Stats Grid -->
<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-icon blue"><span class="i i-document"></span></div>
    <div class="stat-body">
      <div class="label">Páginas</div>
      <div class="value"><?= $totalPages ?></div>
      <div class="delta">Publicadas no site</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon gold"><span class="i i-newspaper"></span></div>
    <div class="stat-body">
      <div class="label">Produções</div>
      <div class="value"><?= $totalPosts ?></div>
      <div class="delta">Artigos publicados</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon green"><span class="i i-images"></span></div>
    <div class="stat-body">
      <div class="label">Mídias</div>
      <div class="value"><?= $totalMedia ?></div>
      <div class="delta">Arquivos na biblioteca</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon purple"><span class="i i-people"></span></div>
    <div class="stat-body">
      <div class="label">Usuários</div>
      <div class="value"><?= $totalUsers ?></div>
      <div class="delta">Com acesso ao painel</div>
    </div>
  </div>
</div>

<!-- Dois cards lado a lado -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:24px">

  <!-- Produções recentes -->
  <div class="card">
    <div class="card-header">
      <h3>Produções Recentes</h3>
      <a href="/admin/postagens.php" class="topbar-btn outline" style="padding:5px 10px;font-size:.75rem">Ver todas</a>
    </div>
    <div class="table-wrap">
      <table class="data-table">
        <thead><tr><th>Título</th><th>Status</th><th>Data</th></tr></thead>
        <tbody>
        <?php if (empty($recentPosts)): ?>
          <tr><td colspan="3" style="text-align:center;color:#9ca3af;padding:24px">Nenhuma produção.</td></tr>
        <?php else: foreach ($recentPosts as $p): ?>
          <tr>
            <td>
              <a href="/admin/postagens.php?action=edit&id=<?= $p['id'] ?>" style="font-weight:500;color:var(--text)">
                <?= e(truncate($p['titulo'], 40)) ?>
              </a>
            </td>
            <td>
              <span class="badge <?= $p['status'] === 'publicado' ? 'badge-green' : 'badge-yellow' ?>">
                <?= e($p['status']) ?>
              </span>
            </td>
            <td style="color:var(--text-muted)"><?= dateFormat($p['data_publicacao']) ?></td>
          </tr>
        <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Páginas recentes -->
  <div class="card">
    <div class="card-header">
      <h3>Páginas</h3>
      <a href="/admin/paginas.php" class="topbar-btn outline" style="padding:5px 10px;font-size:.75rem">Gerenciar</a>
    </div>
    <div class="table-wrap">
      <table class="data-table">
        <thead><tr><th>Título</th><th>Slug</th><th>Status</th></tr></thead>
        <tbody>
        <?php foreach ($recentPages as $pg): ?>
          <tr>
            <td>
              <a href="/admin/paginas.php?action=edit&id=<?= $pg['id'] ?>" style="font-weight:500;color:var(--text)">
                <?= e($pg['titulo']) ?>
              </a>
            </td>
            <td><code style="font-size:.75rem;color:#6b7280">/<?= e($pg['slug']) ?></code></td>
            <td>
              <span class="badge <?= $pg['status'] === 'publicado' ? 'badge-green' : 'badge-yellow' ?>">
                <?= e($pg['status']) ?>
              </span>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>

<!-- Ações rápidas -->
<div class="card" style="margin-top:24px">
  <div class="card-header"><h3>Ações Rápidas</h3></div>
  <div class="card-body" style="display:flex;gap:12px;flex-wrap:wrap">
    <a href="/admin/postagens.php?action=new" class="topbar-btn primary">
      <span class="i i-plus-circle"></span> Nova Produção
    </a>
    <a href="/admin/paginas.php?action=new" class="topbar-btn outline">
      <span class="i i-file-plus"></span> Nova Página
    </a>
    <a href="/admin/media.php" class="topbar-btn outline">
      <span class="i i-cloud-upload"></span> Upload de Mídia
    </a>
    <a href="/admin/aparencia.php" class="topbar-btn outline">
      <span class="i i-palette"></span> Aparência
    </a>
    <a href="/admin/configs.php" class="topbar-btn outline">
      <span class="i i-gear"></span> Configurações
    </a>
    <a href="/" target="_blank" class="topbar-btn outline">
      <span class="i i-eye"></span> Visualizar Site
    </a>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
