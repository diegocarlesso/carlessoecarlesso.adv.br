<?php
/**
 * admin/migrate-images.php — Migração de imagens existentes para srcset/WebP
 *
 * O que este script faz:
 *   1. Adiciona a coluna `variants` (JSON) à tabela `media` (se não existir)
 *   2. Percorre todas as imagens na tabela `media` sem variantes
 *   3. Gera variantes WebP (400w, 800w, 1200w) via ImageProcessor
 *   4. Salva o JSON de variantes no campo `variants`
 *
 * Execução:
 *   - Via browser: acesse /admin/migrate-images.php (requer login de admin)
 *   - Via CLI:     php public_html/admin/migrate-images.php
 *
 * Seguro para executar múltiplas vezes (idempotente):
 *   - Imagens já processadas são ignoradas
 *   - Variantes já geradas não são regravadas
 */

// ── Autenticação ─────────────────────────────────────────────────────────────
$isCLI = PHP_SAPI === 'cli';

if (!$isCLI) {
    // Proteção: exige sessão de admin
    define('CARLESSO_CMS', true);
    define('BASE_PATH', dirname(dirname(__DIR__)));

    require_once __DIR__ . '/../includes/config.php';
    require_once __DIR__ . '/../includes/db.php';
    require_once __DIR__ . '/../includes/functions.php';
    require_once __DIR__ . '/../includes/auth.php';

    session_start();
    if (empty($_SESSION['admin_id'])) {
        http_response_code(403);
        exit('Acesso negado. Faça login no painel admin primeiro.');
    }

    header('Content-Type: text/plain; charset=UTF-8');
    ob_implicit_flush(true);
    ob_end_flush();
} else {
    // CLI: carrega sem sessão
    define('CARLESSO_CMS', true);
    define('BASE_PATH', dirname(dirname(__DIR__)));

    require_once __DIR__ . '/../includes/config.php';
    require_once __DIR__ . '/../includes/db.php';
    require_once __DIR__ . '/../includes/functions.php';
}

// ── Helpers de output ─────────────────────────────────────────────────────────
function out(string $msg): void {
    echo $msg . "\n";
    if (!$isCLI ?? true) { flush(); }
}

out('═══════════════════════════════════════════════════════');
out(' Migração de Imagens — Carlesso & Carlesso CMS');
out('═══════════════════════════════════════════════════════');
out('');

// ── Verificar disponibilidade do ImageProcessor ───────────────────────────────
if (!class_exists(\Carlesso\Services\ImageProcessor::class)) {
    out('ERRO: ImageProcessor não carregado. Verifique o autoloader.');
    exit(1);
}

if (!\Carlesso\Services\ImageProcessor::isAvailable()) {
    out('ERRO: Nenhum engine disponível. Instale a extensão PHP "gd" ou "imagick".');
    exit(1);
}

$engine = extension_loaded('imagick') ? 'Imagick' : 'GD';
out("Engine disponível: {$engine}");
out('');

// ── 1. Adicionar coluna `variants` se não existir ─────────────────────────────
out('[ PASSO 1 ] Verificando coluna `variants` na tabela `media`...');

try {
    // Tenta uma query de teste para ver se a coluna existe
    Database::fetchOne('SELECT variants FROM media LIMIT 1');
    out('  ✓ Coluna `variants` já existe.');
} catch (\Throwable $e) {
    out('  → Coluna não encontrada. Executando ALTER TABLE...');
    try {
        Database::query(
            'ALTER TABLE media ADD COLUMN variants JSON NULL DEFAULT NULL AFTER file_size'
        );
        out('  ✓ Coluna `variants` criada com sucesso.');
    } catch (\Throwable $e2) {
        out('  ERRO ao criar coluna: ' . $e2->getMessage());
        exit(1);
    }
}

out('');

// ── 2. Buscar imagens sem variantes ───────────────────────────────────────────
out('[ PASSO 2 ] Buscando imagens processáveis sem variantes...');

$rows = [];
try {
    $rows = Database::fetchAll(
        "SELECT id, file_path, file_type
           FROM media
          WHERE file_type IN ('image/jpeg','image/png','image/webp')
            AND (variants IS NULL OR variants = '')
          ORDER BY id ASC"
    );
} catch (\Throwable $e) {
    out('  ERRO na consulta: ' . $e->getMessage());
    exit(1);
}

$total = count($rows);
out("  → {$total} imagem(ns) a processar.");
out('');

if ($total === 0) {
    out('Nenhuma imagem pendente. Migração já está completa!');
    exit(0);
}

// ── 3. Processar imagens ───────────────────────────────────────────────────────
out('[ PASSO 3 ] Gerando variantes WebP...');
out('');

$ok    = 0;
$skip  = 0;
$fail  = 0;

foreach ($rows as $row) {
    $urlPath = $row['file_path'];  // ex: /assets/images/xyz.jpg
    $absPath = PUBLIC_PATH . $urlPath;

    if (!is_file($absPath)) {
        out("  [SKIP] Arquivo não encontrado no disco: {$urlPath}");
        $skip++;
        continue;
    }

    // Determina urlBase a partir do urlPath
    $urlBase = '/' . ltrim(dirname($urlPath), '/');

    out("  [→] ID {$row['id']} | {$urlPath}");

    $variants = \Carlesso\Services\ImageProcessor::process($absPath, $urlBase);

    if (empty($variants)) {
        out("       Nenhuma variante gerada (imagem menor que 400px ou engine falhou).");
        // Registra JSON vazio para não reprocessar
        $variantsJson = '{}';
        $skip++;
    } else {
        $sizes = implode('w, ', array_keys($variants)) . 'w';
        out("       ✓ Variantes: {$sizes}");
        $variantsJson = json_encode(['webp' => $variants], JSON_UNESCAPED_SLASHES);
        $ok++;
    }

    try {
        Database::query(
            'UPDATE media SET variants = ? WHERE id = ?',
            [$variantsJson, $row['id']]
        );
    } catch (\Throwable $e) {
        out("       ERRO ao salvar variants no banco: " . $e->getMessage());
        $fail++;
    }
}

// ── Relatório final ───────────────────────────────────────────────────────────
out('');
out('═══════════════════════════════════════════════════════');
out(" Migração concluída.");
out("   Variantes geradas : {$ok}");
out("   Ignoradas/pequenas: {$skip}");
out("   Erros             : {$fail}");
out('═══════════════════════════════════════════════════════');
