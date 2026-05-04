<?php
// templates/header.php
if (!defined('CARLESSO_CMS')) exit;

$siteTitle    = getConfig('site_titulo', 'Carlesso & Carlesso Advogados Associados');
$telefone_g   = getConfig('telefone_g',      '+5549984371381');
$whatsapp_g   = getConfig('whatsapp_g',      preg_replace('/\D/', '', $telefone_g));
$telefone_j   = getConfig('telefone_j',      '+5549984380755');
$whatsapp_j   = getConfig('whatsapp_j',      preg_replace('/\D/', '', $telefone_j));
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
<!-- Google Consent Mode v2 — padrão: negado até consentimento LGPD -->
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('consent', 'default', {
    analytics_storage:    'denied',
    ad_storage:           'denied',
    ad_user_data:         'denied',
    ad_personalization:   'denied',
    wait_for_update:      500
  });
</script>
<script async src="https://www.googletagmanager.com/gtag/js?id=G-5D5L5MHMF5"></script>
<script>
  gtag('js', new Date());
  gtag('config', 'G-5D5L5MHMF5', { anonymize_ip: true });
</script>
<a href="#main-content" class="skip-link">Pular para o conteúdo</a>

<header id="site-header" role="banner">
  <div class="container header-inner">

    <a href="/" class="site-logo" aria-label="<?= e($siteTitle) ?> – Início">
      <img src="/assets/images/logo_com_texto.png"
           alt="<?= e($siteTitle) ?>"
           class="logo-full">
      <img src="/assets/images/logo_com_texto.png"
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
      <?php if ($whatsapp_g): ?>
      <a href="https://wa.me/<?= e(preg_replace('/\D/', '', $whatsapp_g)) ?>"
         target="_blank" rel="noopener"
         aria-label="WhatsApp Dr. Guilherme"
         class="whatsapp-btn">
        <span class="i i-whatsapp"></span>
        <span class="tooltip">WhatsApp Dr. Guilherme</span>
      </a>
      <?php endif; ?>
      
      <?php if ($whatsapp_j): ?>
      <a href="https://wa.me/<?= e(preg_replace('/\D/', '', $whatsapp_j)) ?>"
         target="_blank" rel="noopener"
         aria-label="WhatsApp Dr. Jean"
         class="whatsapp-btn">
        <span class="i i-whatsapp"></span>
        <span class="tooltip">WhatsApp Dr. Jean</span>
      </a>
      <?php endif; ?>
    </div>

    <button class="menu-toggle" aria-label="Abrir menu" aria-expanded="false" aria-controls="site-nav">
      <span></span><span></span><span></span>
    </button>

  </div>
</header>
