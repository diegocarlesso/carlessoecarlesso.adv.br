<?php
// templates/header.php
if (!defined('CARLESSO_CMS')) exit;

$siteTitle    = getConfig('site_titulo', 'Carlesso & Carlesso Advogados Associados');
$telefone     = getConfig('telefone', '(49) 3621-2254');
$whatsapp     = getConfig('whatsapp', preg_replace('/\D/', '', $telefone));
$instagram    = getConfig('instagram', '#');
$facebook     = getConfig('facebook', '#');
$currentSlug  = $slug ?? 'inicio';

$pageTitleOut = $seoTitle ?? $siteTitle;
$pageDescOut  = $seoDesc  ?? '';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($pageTitleOut) ?></title>
  <?php if ($pageDescOut): ?>
  <meta name="description" content="<?= e($pageDescOut) ?>">
  <?php endif; ?>
  <meta name="robots" content="index, follow">
  <meta name="theme-color" content="#1a3554">

  <meta property="og:title"       content="<?= e($pageTitleOut) ?>">
  <meta property="og:description" content="<?= e($pageDescOut) ?>">
  <meta property="og:type"        content="website">
  <meta property="og:image"       content="/assets/images/logo_com_texto.png">
  <meta property="og:locale"      content="pt_BR">

  <link rel="icon"          type="image/png" href="/assets/images/logo_sem_texto.png">
  <link rel="apple-touch-icon"               href="/assets/images/logo_sem_texto.png">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Hepta+Slab:wght@400;700;800&family=Open+Sans:ital,wght@0,400;0,600;1,400&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="/assets/css/style.css?v=<?= @filemtime(PUBLIC_PATH . '/assets/css/style.css') ?: '1' ?>">
  <link rel="stylesheet" href="/assets/css/style-extras.css?v=<?= @filemtime(PUBLIC_PATH . '/assets/css/style-extras.css') ?: '1' ?>">
  <link rel="stylesheet" href="/assets/css/blocks.css?v=<?= @filemtime(PUBLIC_PATH . '/assets/css/blocks.css') ?: '1' ?>">
  <link rel="stylesheet" href="/assets/icons/icons.css?v=1">
</head>
<body data-page="<?= e($currentSlug) ?>">

<a href="#main-content" class="skip-link">Pular para o conteúdo</a>

<header id="site-header" role="banner">
  <div class="container header-inner">

    <a href="/" class="site-logo" aria-label="<?= e($siteTitle) ?> – Início">
      <img src="/assets/images/logo_com_texto.png"
           alt="<?= e($siteTitle) ?>"
           class="logo-full">
      <img src="/assets/images/logo_sem_texto.png"
           alt=""
           class="logo-mark"
           aria-hidden="true">
    </a>

    <nav class="site-nav" id="site-nav" aria-label="Menu principal">
      <a href="/"                  class="<?= $currentSlug === 'inicio'      ? 'active' : '' ?>">Início</a>
      <a href="/escritorio"        class="<?= $currentSlug === 'escritorio'  ? 'active' : '' ?>">Escritório</a>
      <a href="/equipe"            class="<?= $currentSlug === 'equipe'      ? 'active' : '' ?>">Equipe</a>
      <a href="/fundamentos"       class="<?= $currentSlug === 'fundamentos' ? 'active' : '' ?>">Nossos Fundamentos</a>
      <a href="/servicos"          class="<?= $currentSlug === 'servicos'    ? 'active' : '' ?>">Serviços</a>
      <a href="/producoes"         class="<?= $currentSlug === 'producoes'   ? 'active' : '' ?>">Produções</a>
      <a href="/contato"           class="<?= $currentSlug === 'contato'     ? 'active' : '' ?>">Contato</a>
    </nav>

    <div class="header-social" aria-label="Redes sociais">
      <?php if ($facebook && $facebook !== '#'): ?>
      <a href="<?= e($facebook) ?>" target="_blank" rel="noopener" aria-label="Facebook">
        <span class="i i-facebook"></span>
      </a>
      <?php endif; ?>
      <?php if ($instagram && $instagram !== '#'): ?>
      <a href="<?= e($instagram) ?>" target="_blank" rel="noopener" aria-label="Instagram">
        <span class="i i-instagram"></span>
      </a>
      <?php endif; ?>
      <?php if ($whatsapp): ?>
      <a href="https://wa.me/<?= e(preg_replace('/\D/', '', $whatsapp)) ?>"
         target="_blank" rel="noopener" aria-label="WhatsApp">
        <span class="i i-whatsapp"></span>
      </a>
      <?php endif; ?>
    </div>

    <button class="menu-toggle" aria-label="Abrir menu" aria-expanded="false" aria-controls="site-nav">
      <span></span><span></span><span></span>
    </button>

  </div>
</header>
