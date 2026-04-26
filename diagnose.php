<?php
/**
 * diagnose.php — Diagnóstico do bootstrap v2.0.
 *
 * USO: subir para public_html/, abrir https://seusite/diagnose.php
 * REMOVER após confirmar que está tudo verde — não deixar em produção.
 *
 * Checa:
 *  1. PHP version
 *  2. Extensões obrigatórias
 *  3. Resolução de paths (BASE_PATH, PUBLIC_PATH, SRC_PATH, VENDOR_PATH, STORAGE_PATH)
 *  4. Existência de arquivos críticos
 *  5. Se Kernel bootou
 *  6. Se autoload (Composer ou fallback) está funcionando
 *  7. Se as classes legacy (Database, Auth, CSRF) estão aliasadas
 *  8. Se as funções globais (e, generateSlug, sanitizeHtml) existem
 *  9. Se .env carregou
 * 10. Se conexão DB funciona
 */

header('Content-Type: text/plain; charset=utf-8');
echo "═══════════════════════════════════════════════\n";
echo "  CARLESSO CMS — DIAGNÓSTICO BOOTSTRAP v2.0\n";
echo "═══════════════════════════════════════════════\n\n";

// 1. PHP
echo "[1] PHP\n";
echo "    Versão: " . PHP_VERSION . " " . (PHP_VERSION_ID >= 80000 ? "✓" : "✗ (precisa 8.0+)") . "\n";
echo "    SAPI:   " . PHP_SAPI . "\n\n";

// 2. Extensões
echo "[2] Extensões\n";
foreach (['pdo_mysql', 'mbstring', 'fileinfo', 'gd', 'zip', 'openssl', 'json'] as $ext) {
    echo "    " . str_pad($ext, 12) . (extension_loaded($ext) ? "✓" : "✗") . "\n";
}
echo "\n";

// 3. Tenta carregar config.php
echo "[3] Carregando includes/config.php...\n";
try {
    define('CARLESSO_CMS', true);
    require_once __DIR__ . '/includes/config.php';
    echo "    OK\n\n";
} catch (\Throwable $e) {
    echo "    ✗ ERRO: " . $e->getMessage() . "\n";
    echo "    em " . $e->getFile() . ":" . $e->getLine() . "\n\n";
    exit;
}

// 4. Constantes de path
echo "[4] Paths resolvidos\n";
foreach (['BASE_PATH', 'PUBLIC_PATH', 'CONFIG_PATH', 'SRC_PATH', 'VENDOR_PATH', 'BIN_PATH', 'STORAGE_PATH'] as $c) {
    $val = defined($c) ? constant($c) : '(não definido)';
    $exists = defined($c) ? (is_dir($val) ? '✓ existe' : '✗ NÃO existe') : '';
    echo "    " . str_pad($c, 14) . " = $val  $exists\n";
}
echo "\n";

// 5. Arquivos críticos
echo "[5] Arquivos críticos\n";
$files = [
    SRC_PATH . '/Kernel.php',
    SRC_PATH . '/Support/Database.php',
    SRC_PATH . '/Support/helpers.php',
    SRC_PATH . '/Services/Auth/AuthService.php',
    SRC_PATH . '/Services/HtmlSanitizer.php',
    CONFIG_PATH . '/.env',
    VENDOR_PATH . '/autoload.php',
];
foreach ($files as $f) {
    echo "    " . (is_file($f) ? "✓" : "✗") . "  $f\n";
}
echo "\n";

// 6. Kernel bootou?
echo "[6] Kernel\n";
echo "    Classe \\Carlesso\\Kernel existe: " . (class_exists('\\Carlesso\\Kernel', false) ? "✓" : "✗") . "\n";
echo "    Composer autoload usado:        " . (is_file(VENDOR_PATH . '/autoload.php') ? "sim" : "não (fallback PSR-4)") . "\n\n";

// 7. Classes namespaced + alias legacy
echo "[7] Classes\n";
foreach ([
    '\\Carlesso\\Support\\Database' => 'Database',
    '\\Carlesso\\Services\\Auth\\AuthService' => 'Auth',
    '\\Carlesso\\Support\\Csrf' => 'CSRF',
    '\\Carlesso\\Services\\HtmlSanitizer' => null,
] as $fqcn => $alias) {
    echo "    " . (class_exists($fqcn) ? "✓" : "✗") . "  $fqcn";
    if ($alias) {
        echo "  →  alias '$alias': " . (class_exists($alias, false) ? "✓" : "✗");
    }
    echo "\n";
}
// Carrega db.php / auth.php / csrf.php para os aliases acima dispararem
@require_once __DIR__ . '/includes/db.php';
@require_once __DIR__ . '/includes/auth.php';
@require_once __DIR__ . '/includes/csrf.php';
echo "    Após require dos shims:\n";
foreach (['Database', 'Auth', 'CSRF'] as $alias) {
    echo "      " . (class_exists($alias, false) ? "✓" : "✗") . "  $alias\n";
}
echo "\n";

// 8. Funções globais
echo "[8] Funções globais (helpers.php)\n";
@require_once __DIR__ . '/includes/functions.php';
foreach (['e', 'generateSlug', 'sanitizeFilename', 'dateFormat', 'bytesFormat', 'truncate', 'jsonResponse', 'svgIcon', 'sanitizeHtml', 'getPage', 'getConfig', 'flash'] as $fn) {
    echo "    " . (function_exists($fn) ? "✓" : "✗") . "  $fn()\n";
}
echo "\n";

// 9. .env carregou
echo "[9] .env\n";
foreach (['DB_HOST', 'DB_NAME', 'DB_USER', 'APP_ENV', 'APP_URL'] as $k) {
    $v = function_exists('env') ? env($k) : ($_ENV[$k] ?? null);
    if ($k === 'DB_USER' || str_contains($k, 'PASS') || str_contains($k, 'SECRET')) {
        $v = $v ? '(definido, ' . strlen((string)$v) . ' chars)' : '(vazio)';
    }
    echo "    " . str_pad($k, 12) . " = " . ($v ?: '(vazio)') . "\n";
}
echo "\n";

// 10. DB
echo "[10] Conexão DB\n";
try {
    $pdo = Database::getInstance();
    echo "    ✓ Conectado\n";
    $count = Database::fetchOne('SELECT COUNT(*) AS c FROM paginas');
    echo "    ✓ Query teste: $count[c] páginas\n";

    // Verifica se update-v1.3.sql rodou
    $hasPerm = Database::fetchOne("SHOW TABLES LIKE 'permissions'");
    echo "    " . ($hasPerm ? "✓" : "✗") . "  update-v1.3.sql aplicado (tabela permissions " . ($hasPerm ? "existe" : "NÃO existe — rodar update-v1.3.sql") . ")\n";

    $hasCol = Database::fetchOne("SHOW COLUMNS FROM conteudos LIKE 'pagina_slug'");
    echo "    " . ($hasCol ? "✓" : "✗") . "  Coluna conteudos.pagina_slug " . ($hasCol ? "existe" : "NÃO existe — rodar update-v1.3.sql") . "\n";
} catch (\Throwable $e) {
    echo "    ✗ ERRO: " . $e->getMessage() . "\n";
}

echo "\n═══════════════════════════════════════════════\n";
echo "Se algum item acima está com ✗, esse é o problema.\n";
echo "REMOVA este arquivo após o diagnóstico.\n";
