<?php
/**
 * api/builder/load.php — Carrega o JSON tree de uma página pro builder.
 *
 * GET /api/builder/load.php?page_id=N
 * Retorna: { ok, page: { id, titulo, slug, status, ... }, blocks: { version, blocks: [...] } }
 *
 * Compat: páginas com blocos V1 (flat array) são automaticamente convertidas
 * para V2 (tree) via BlockTransformer antes de devolver.
 */
define('CARLESSO_CMS', true);
define('BASE_PATH', dirname(dirname(dirname(__DIR__))));

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache');

try {
    require_once __DIR__ . '/../../includes/config.php';
    require_once __DIR__ . '/../../includes/db.php';
    require_once __DIR__ . '/../../includes/auth.php';
    require_once __DIR__ . '/../../includes/csrf.php';
    require_once __DIR__ . '/../../includes/functions.php';

    Auth::requireCan('pages.edit');

    $pageId = (int) ($_GET['page_id'] ?? 0);
    if (!$pageId) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'page_id obrigatório.']);
        exit;
    }

    $page = Database::fetchOne('SELECT * FROM paginas WHERE id = ?', [$pageId]);
    if (!$page) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'error' => 'Página não encontrada.']);
        exit;
    }

    // Normaliza para V2 via BlockTransformer (Phase 2)
    $rawBlocks = $page['blocos'] ?? '';
    if (class_exists(\Carlesso\Services\Blocks\BlockTransformer::class)) {
        $transformer = new \Carlesso\Services\Blocks\BlockTransformer();
        $tree = $transformer->normalize($rawBlocks ?: '');
    } else {
        // Fallback se backend Phase 2 não está deployado
        $decoded = json_decode($rawBlocks, true);
        $tree = is_array($decoded) && isset($decoded['version'])
            ? $decoded
            : ['version' => 2, 'blocks' => []];
    }

    echo json_encode([
        'ok'    => true,
        'page'  => [
            'id'     => (int) $page['id'],
            'titulo' => $page['titulo'],
            'slug'   => $page['slug'],
            'status' => $page['status'],
        ],
        'tree'  => $tree,
        'csrf'  => CSRF::generate(),
    ], JSON_UNESCAPED_UNICODE);

} catch (\Throwable $e) {
    error_log('[builder/load] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Erro interno: ' . $e->getMessage()]);
}
