<?php
/**
 * robots.php — Serve /robots.txt com o domínio real detectado em runtime
 * Carlesso & Carlesso CMS
 */
$proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host  = rtrim($_SERVER['HTTP_HOST'] ?? '', '/');

header('Content-Type: text/plain; charset=UTF-8');
header('Cache-Control: public, max-age=86400');

echo "User-agent: *\n";
echo "Allow: /\n";
echo "\n";
echo "Disallow: /admin/\n";
echo "Disallow: /api/\n";
echo "Disallow: /vendor/\n";
echo "Disallow: /src/\n";
echo "Disallow: /config/\n";
echo "Disallow: /storage/\n";
echo "Disallow: /bin/\n";
echo "\n";
echo "Sitemap: {$proto}://{$host}/sitemap.xml\n";
