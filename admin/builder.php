<?php
/**
 * admin/builder.php — Entry point do Editor Visual (Builder v1).
 *
 * Renderiza o shell HTML de 3 colunas (sidebar | canvas | inspector),
 * embute o token CSRF e o estado inicial (page + tree) e carrega o app
 * JS modular via <script type="module" src="app.js">.
 *
 * URL: /admin/builder.php?page_id=N
 */
define('CARLESSO_CMS', true);
define('BASE_PATH', dirname(dirname(__DIR__)));

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/functions.php';

Auth::requireCan('pages.edit');

$pageId = (int) ($_GET['page_id'] ?? 0);
if (!$pageId) {
    header('Location: /admin/paginas.php');
    exit;
}

$page = Database::fetchOne('SELECT * FROM paginas WHERE id = ?', [$pageId]);
if (!$page) {
    header('Location: /admin/paginas.php');
    exit;
}

// Token CSRF stateless (preferido) — usado em save/load
$csrfToken = method_exists('\CSRF', 'generateStateless')
    ? CSRF::generateStateless()
    : CSRF::generate();

$cssVer = @filemtime(PUBLIC_PATH . '/assets/builder/builder.css') ?: 1;
?><!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Editor Visual — <?= e($page['titulo']) ?></title>
  <link rel="icon" type="image/png" href="/assets/images/logo_sem_texto.png">
  <link rel="stylesheet" href="/assets/builder/builder.css?v=<?= $cssVer ?>">
  <link rel="stylesheet" href="/assets/css/blocks.css?v=<?= @filemtime(PUBLIC_PATH . '/assets/css/blocks.css') ?: 1 ?>">

  <!-- Estado inicial passado pro JS -->
  <script>
    window.__BUILDER_BOOT__ = {
      pageId:    <?= (int) $page['id'] ?>,
      pageTitle: <?= json_encode($page['titulo']) ?>,
      pageSlug:  <?= json_encode($page['slug']) ?>,
      pageStatus: <?= json_encode($page['status']) ?>,
      csrf:      <?= json_encode($csrfToken) ?>,
      apiBase:   '/api/builder',
      backUrl:   '/admin/paginas.php?action=edit&id=<?= (int) $page['id'] ?>',
    };
  </script>
</head>
<body class="builder-body">

<div class="builder-shell">

  <!-- ── TOPBAR ────────────────────────────────────────────── -->
  <header class="builder-topbar">
    <div class="builder-topbar-left">
      <a class="builder-back" href="/admin/paginas.php?action=edit&id=<?= (int) $page['id'] ?>" title="Voltar">←</a>
      <div class="builder-page-info">
        <div class="builder-page-title"><?= e($page['titulo']) ?></div>
        <div class="builder-page-slug">/<?= e($page['slug']) ?></div>
      </div>
    </div>
    <div class="builder-topbar-center">
      <span class="builder-save-state" id="builder-save-state">Pronto</span>
    </div>
    <div class="builder-topbar-right">
      <button class="builder-btn ghost" id="builder-preview" type="button">Preview</button>
      <button class="builder-btn primary" id="builder-save" type="button">Salvar</button>
    </div>
  </header>

  <!-- ── BODY: 3 COLUNAS ─────────────────────────────────────── -->
  <div class="builder-main">
    <aside class="builder-sidebar" id="builder-sidebar" aria-label="Blocos disponíveis">
      <div class="builder-sidebar-header">Blocos</div>
      <div class="builder-sidebar-list" id="builder-sidebar-list">
        <!-- Sidebar.js popula aqui -->
      </div>
    </aside>

    <section class="builder-canvas-wrap">
      <div class="builder-canvas-frame">
        <div class="builder-canvas" id="builder-canvas">
          <!-- Canvas.js renderiza aqui -->
          <div class="builder-canvas-empty">Carregando…</div>
        </div>
      </div>
    </section>

    <aside class="builder-inspector" id="builder-inspector" aria-label="Propriedades do bloco">
      <div class="builder-inspector-header">Propriedades</div>
      <div class="builder-inspector-body" id="builder-inspector-body">
        <!-- Inspector.js popula aqui -->
      </div>
    </aside>
  </div>

</div>

<!-- App modular ES — sem build, importmap nativo do navegador -->
<script type="module" src="/assets/builder/app.js?v=<?= @filemtime(PUBLIC_PATH . '/assets/builder/app.js') ?: 1 ?>"></script>

</body>
</html>
