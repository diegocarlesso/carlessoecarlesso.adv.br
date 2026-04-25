<?php
// admin/includes/header.php
if (!defined('CARLESSO_CMS')) exit;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($pageTitle ?? 'Painel') ?> — Carlesso CMS</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/assets/icons/icons.css?v=1">
  <link rel="stylesheet" href="/assets/css/admin.css?v=<?= filemtime(__DIR__ . '/../../assets/css/admin.css') ?>">
  <?php if (!empty($extraHead)) echo $extraHead; ?>
  <?= \CSRF::meta() ?>
</head>
<body>
<div class="admin-layout">
<?php include __DIR__ . '/sidebar.php'; ?>
<div class="main-content">
  <!-- Topbar -->
  <div class="topbar">
    <div class="topbar-left">
      <button class="sidebar-toggle" id="sidebar-toggle" aria-label="Toggle menu">
        <span class="i i-list"></span>
      </button>
      <div class="topbar-breadcrumb">
        <a href="/admin/">Início</a>
        <?php if (!empty($breadcrumb)): foreach ($breadcrumb as $label => $url): ?>
          <span class="sep">›</span>
          <?php if ($url): ?><a href="<?= e($url) ?>"><?= e($label) ?></a>
          <?php else: ?><span><?= e($label) ?></span><?php endif; ?>
        <?php endforeach; endif; ?>
      </div>
    </div>
    <div class="topbar-right">
      <?php if (!empty($topbarActions)) echo $topbarActions; ?>
      <a href="/" target="_blank" class="topbar-btn outline">
        <span class="i i-external"></span> Ver Site
      </a>
    </div>
  </div>

  <!-- Content -->
  <div class="page-content">
    <?php
    if ($f = flash('success')) printf('<div class="alert alert-success">✓ %s</div>', e($f['msg']));
    if ($f = flash('error'))   printf('<div class="alert alert-error">✕ %s</div>', e($f['msg']));
    ?>
