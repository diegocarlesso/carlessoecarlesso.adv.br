<?php
/**
 * sitemap.php — Gerador dinâmico de sitemap XML (acessado via /sitemap.xml)
 * Carlesso & Carlesso CMS
 */
define('CARLESSO_CMS', true);
define('BASE_PATH', dirname(__DIR__));

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

// Protocolo e host detectados automaticamente
$proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host  = rtrim($_SERVER['HTTP_HOST'] ?? '', '/');
$base  = $proto . '://' . $host;

// Páginas estáticas do site
$staticPages = [
    ['loc' => '/',            'priority' => '1.0', 'changefreq' => 'weekly'],
    ['loc' => '/escritorio',  'priority' => '0.8', 'changefreq' => 'monthly'],
    ['loc' => '/equipe',      'priority' => '0.8', 'changefreq' => 'monthly'],
    ['loc' => '/fundamentos', 'priority' => '0.8', 'changefreq' => 'monthly'],
    ['loc' => '/servicos',    'priority' => '0.9', 'changefreq' => 'monthly'],
    ['loc' => '/producoes',   'priority' => '0.7', 'changefreq' => 'weekly'],
    ['loc' => '/contato',     'priority' => '0.7', 'changefreq' => 'monthly'],
];

// Posts publicados (Produções)
$posts = [];
try {
    $posts = Database::fetchAll(
        "SELECT slug, updated_at, data_publicacao
           FROM postagens
          WHERE status = 'publicado'
          ORDER BY data_publicacao DESC"
    );
} catch (\Throwable $e) {
    // Silencioso: continua sem posts se a query falhar
}

// Saída XML
header('Content-Type: application/xml; charset=UTF-8');
header('Cache-Control: public, max-age=3600');

// Obrigatório para não ser indexado como página
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

foreach ($staticPages as $p) {
    echo "  <url>\n";
    echo "    <loc>" . htmlspecialchars($base . $p['loc'], ENT_XML1) . "</loc>\n";
    echo "    <changefreq>" . $p['changefreq'] . "</changefreq>\n";
    echo "    <priority>" . $p['priority'] . "</priority>\n";
    echo "  </url>\n";
}

foreach ($posts as $post) {
    if (empty($post['slug'])) continue;
    $lastmod = !empty($post['updated_at'])
        ? date('Y-m-d', strtotime($post['updated_at']))
        : date('Y-m-d', strtotime($post['data_publicacao'] ?? 'now'));
    echo "  <url>\n";
    echo "    <loc>" . htmlspecialchars($base . '/producoes/' . $post['slug'], ENT_XML1) . "</loc>\n";
    echo "    <lastmod>" . $lastmod . "</lastmod>\n";
    echo "    <changefreq>monthly</changefreq>\n";
    echo "    <priority>0.6</priority>\n";
    echo "  </url>\n";
}

echo '</urlset>';
