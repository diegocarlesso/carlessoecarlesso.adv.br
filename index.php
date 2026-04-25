<?php
/**
 * index.php — Roteador principal do frontend
 * Carlesso & Carlesso CMS
 */

define('CARLESSO_CMS', true);
define('BASE_PATH', dirname(__DIR__));

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

// Determinar slug atual
$slug = trim($_GET['slug'] ?? '', '/');
$slug = $slug ?: 'inicio';
$slug = preg_replace('/[^a-z0-9_-]/', '', strtolower($slug));

// Buscar página
$page    = getPage($slug);
$seoData = getPageSeo($slug);
$seoTitle= $seoData['meta_title'] ?? getConfig('site_titulo');
$seoDesc = $seoData['meta_description'] ?? '';

// 404
if (!$page && $slug !== 'inicio') {
    http_response_code(404);
    $seoTitle = '404 – Página não encontrada | ' . getConfig('site_titulo');
    include __DIR__ . '/templates/header.php';
    echo '
    <main style="padding:160px 0 80px;text-align:center">
      <h1 style="font-size:5rem;color:#527095;margin-bottom:8px">404</h1>
      <p style="color:#6b7280;font-size:1.2rem;margin-bottom:32px">Página não encontrada.</p>
      <a href="/" class="btn btn-primary">Voltar ao Início</a>
    </main>';
    include __DIR__ . '/templates/footer.php';
    exit;
}

// Header
include __DIR__ . '/templates/header.php';

// Renderizar conteúdo
echo '<main id="main-content">';

if ($slug === 'inicio') {
    include __DIR__ . '/templates/home.php';
} else {
    // Cabeçalho de página padrão
    echo '<section class="page-header">
      <div class="container">
        <div class="breadcrumb">
          <a href="/">Início</a>
          <span class="sep">›</span>
          <span>' . e($page['titulo']) . '</span>
        </div>
        <h1>' . e($page['titulo']) . '</h1>
      </div>
    </section>';

    echo '<div class="page-content"><div class="container">';

    // Blocos ou conteúdo rico?
    if (!empty($page['blocos'])) {
        echo renderBlocks($page['blocos']);
    } elseif (!empty($page['conteudo'])) {
        echo '<div class="rich-content">' . sanitizeHtml($page['conteudo']) . '</div>';
    }

    // Templates especiais por slug
    $templateFile = __DIR__ . '/templates/' . $slug . '.php';
    if (file_exists($templateFile)) {
        include $templateFile;
    }

    echo '</div></div>';
}

echo '</main>';

// Footer
include __DIR__ . '/templates/footer.php';
