<?php
/**
 * api/builder/save.php — Persiste o JSON tree do builder em paginas.blocos.
 *
 * POST /api/builder/save.php
 * Body: { page_id, csrf, tree: { version, blocks: [...] }, status?: 'rascunho'|'publicado' }
 * Retorna: { ok, saved_at }
 *
 * Validações:
 *  - CSRF
 *  - Auth: posts.edit OU pages.edit (qualquer um cobre páginas e produções)
 *  - Estrutura mínima: tree.version >= 2 e tree.blocks é array
 *  - IDs e profundidade limitada (evita árvores patológicas)
 *  - Sanitização de campos HTML em blocos rich_text
 */
define('CARLESSO_CMS', true);
define('BASE_PATH', dirname(dirname(dirname(__DIR__))));

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

const MAX_DEPTH       = 8;     // proteção contra árvores patológicas
const MAX_NODES       = 500;   // limite de nós por página
const MAX_PAYLOAD_KB  = 512;   // 512KB ~ ainda generoso

try {
    require_once __DIR__ . '/../../includes/config.php';
    require_once __DIR__ . '/../../includes/db.php';
    require_once __DIR__ . '/../../includes/auth.php';
    require_once __DIR__ . '/../../includes/csrf.php';
    require_once __DIR__ . '/../../includes/functions.php';

    Auth::requireCan('pages.edit');

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['ok' => false, 'error' => 'POST esperado.']);
        exit;
    }

    $raw = file_get_contents('php://input');
    if (strlen($raw) > MAX_PAYLOAD_KB * 1024) {
        http_response_code(413);
        echo json_encode(['ok' => false, 'error' => 'Payload muito grande.']);
        exit;
    }
    $input = json_decode($raw, true);
    if (!is_array($input)) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'JSON inválido.']);
        exit;
    }

    // CSRF — aceita stateless (preferido) e legacy
    $token = $input['csrf'] ?? '';
    $csrfOk = false;
    if (method_exists('CSRF', 'validateStateless') && CSRF::validateStateless($token)) {
        $csrfOk = true;
    } elseif (CSRF::validate($token)) {
        $csrfOk = true;
    }
    if (!$csrfOk) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'error' => 'CSRF inválido.']);
        exit;
    }

    $pageId = (int) ($input['page_id'] ?? 0);
    $tree   = $input['tree'] ?? null;
    $status = $input['status'] ?? null;

    if (!$pageId || !is_array($tree)) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'page_id e tree obrigatórios.']);
        exit;
    }

    // Validação estrutural mínima
    if (!isset($tree['version']) || (int) $tree['version'] < 2) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'tree.version deve ser >= 2.']);
        exit;
    }
    if (!isset($tree['blocks']) || !is_array($tree['blocks'])) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'tree.blocks deve ser array.']);
        exit;
    }

    // Sanitização recursiva
    $nodeCount = 0;
    $tree['blocks'] = sanitizeNodes($tree['blocks'], 0, $nodeCount);
    if ($nodeCount > MAX_NODES) {
        http_response_code(413);
        echo json_encode(['ok' => false, 'error' => "Mais de " . MAX_NODES . " nós."]);
        exit;
    }

    // Confere se a página existe
    $page = Database::fetchOne('SELECT id FROM paginas WHERE id = ?', [$pageId]);
    if (!$page) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'error' => 'Página não encontrada.']);
        exit;
    }

    // Persiste
    $update = ['blocos' => json_encode($tree, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)];
    if (in_array($status, ['rascunho', 'publicado'], true)) {
        $update['status'] = $status;
    }
    // marca versão V2 se a coluna existe
    try {
        Database::query("SHOW COLUMNS FROM paginas LIKE 'blocks_version'");
        $update['blocks_version'] = 2;
    } catch (\Throwable) {}

    Database::update('paginas', $update, 'id = ?', [$pageId]);

    echo json_encode([
        'ok'       => true,
        'saved_at' => date('c'),
        'nodes'    => $nodeCount,
    ]);

} catch (\Throwable $e) {
    error_log('[builder/save] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Erro interno: ' . $e->getMessage()]);
}

// ─── helpers ───────────────────────────────────────────────────────────
function sanitizeNodes(array $nodes, int $depth, int &$count): array
{
    if ($depth > MAX_DEPTH) return [];
    $out = [];
    foreach ($nodes as $n) {
        if (!is_array($n)) continue;
        $count++;
        if ($count > MAX_NODES) return $out;

        $type     = preg_replace('/[^a-z0-9_]/', '', strtolower((string) ($n['type'] ?? 'unknown')));
        $id       = preg_replace('/[^a-zA-Z0-9_-]/', '', (string) ($n['id'] ?? ''));
        if ($id === '') $id = 'n-' . bin2hex(random_bytes(6));

        $settings = is_array($n['settings'] ?? null) ? $n['settings'] : [];
        $settings = sanitizeSettings($type, $settings);

        $children = is_array($n['children'] ?? null) ? $n['children'] : [];
        $children = sanitizeNodes($children, $depth + 1, $count);

        $out[] = [
            'id'       => $id,
            'type'     => $type,
            'settings' => $settings,
            'children' => $children,
        ];
    }
    return $out;
}

function sanitizeSettings(string $type, array $settings): array
{
    // Para blocos com HTML rico, passa pelo HtmlSanitizer (Phase 1).
    foreach (['html', 'code', 'text', 'caption', 'embed_html'] as $htmlField) {
        if (isset($settings[$htmlField]) && is_string($settings[$htmlField])) {
            if ($htmlField === 'text' && in_array($type, ['heading', 'button'], true)) {
                // 'text' em heading/button é texto puro — sem HTML
                $settings[$htmlField] = strip_tags($settings[$htmlField]);
            } else {
                $settings[$htmlField] = function_exists('sanitizeHtml')
                    ? sanitizeHtml($settings[$htmlField])
                    : strip_tags($settings[$htmlField], '<p><br><strong><em><u><s><a><ul><ol><li><h1><h2><h3><h4><h5><h6><img><blockquote><span><div><hr>');
            }
        }
    }
    // URLs: remove esquemas perigosos
    foreach (['url', 'src', 'photo_url', 'href', 'link'] as $urlField) {
        if (isset($settings[$urlField]) && is_string($settings[$urlField])) {
            $u = trim($settings[$urlField]);
            if ($u !== '' && !preg_match('#^(https?://|/|mailto:|tel:|#)#i', $u)) {
                $settings[$urlField] = '';
            }
        }
    }
    return $settings;
}
