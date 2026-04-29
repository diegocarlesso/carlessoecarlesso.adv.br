<?php
/**
 * api/builder/render.php — Preview server-side.
 *
 * POST /api/builder/render.php
 * Body: { tree: {...} }   (não precisa salvar; renderização efêmera)
 * GET  /api/builder/render.php?page_id=N   (renderiza o que está salvo)
 *
 * Usa o PageRenderer da Phase 2 — produz HTML idêntico ao que o frontend
 * mostra ao visitante. Útil pro builder mostrar um preview "real" lado a lado.
 */
define('CARLESSO_CMS', true);
define('BASE_PATH', dirname(dirname(dirname(__DIR__))));

header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store');

try {
    require_once __DIR__ . '/../../includes/config.php';
    require_once __DIR__ . '/../../includes/db.php';
    require_once __DIR__ . '/../../includes/auth.php';
    require_once __DIR__ . '/../../includes/csrf.php';
    require_once __DIR__ . '/../../includes/functions.php';

    Auth::requireCan('pages.edit');

    $tree = null;

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $raw = file_get_contents('php://input');
        $input = json_decode($raw, true);

        $token = $input['csrf'] ?? '';
        $csrfOk = (method_exists('CSRF', 'validateStateless') && CSRF::validateStateless($token))
               || CSRF::validate($token);
        if (!$csrfOk) {
            http_response_code(403);
            echo '<!-- csrf invalid -->';
            exit;
        }
        $tree = $input['tree'] ?? null;
    } else {
        $pageId = (int) ($_GET['page_id'] ?? 0);
        if ($pageId) {
            $page = Database::fetchOne('SELECT blocos FROM paginas WHERE id = ?', [$pageId]);
            $tree = json_decode($page['blocos'] ?? '', true);
        }
    }

    if (!is_array($tree)) {
        echo '<div style="padding:24px;color:#9ca3af;text-align:center">Página vazia. Adicione blocos.</div>';
        exit;
    }

    if (class_exists(\Carlesso\Services\Blocks\PageRenderer::class)) {
        echo \Carlesso\Services\Blocks\PageRenderer::default()->render($tree);
    } else {
        // Fallback: chama o renderBlocks legacy
        echo function_exists('renderBlocks')
            ? renderBlocks(json_encode($tree))
            : '<!-- PageRenderer indisponível -->';
    }
} catch (\Throwable $e) {
    error_log('[builder/render] ' . $e->getMessage());
    http_response_code(500);
    echo '<div style="padding:24px;color:#dc2626">Erro ao renderizar: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES) . '</div>';
}
