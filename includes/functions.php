<?php
/**
 * functions.php — Shim de compatibilidade + funções de domínio.
 *
 * Helpers genéricos (e, generateSlug, sanitizeFilename, dateFormat, bytesFormat,
 * truncate, jsonResponse, svgIcon) vivem agora em src/Support/helpers.php e
 * são carregados via include direto abaixo (Composer autoload também os
 * carrega via "files" entry, mas o include garante funcionamento mesmo
 * sem composer install).
 *
 * Funções de domínio (getPage, getContent, renderBlocks, handleUpload, etc.)
 * permanecem aqui por enquanto — Phase 2 vai migrar para Repositories e
 * PageRenderer service.
 *
 * sanitizeHtml() agora delega para Carlesso\Services\HtmlSanitizer (que usa
 * HTMLPurifier quando disponível, ou cai no regex legacy como fallback).
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

// Helpers globais (e, generateSlug, dateFormat, etc.)
// Usa SRC_PATH (auto-detectado pelo Kernel — funciona nos layouts PARENT e HOSTINGER).
// Fallback duplo: se Kernel não bootou, proba os dois locais antes de desistir.
$helpersFile = (defined('SRC_PATH') ? SRC_PATH : BASE_PATH . '/src') . '/Support/helpers.php';
if (!is_file($helpersFile)) {
    foreach ([BASE_PATH . '/src/Support/helpers.php', PUBLIC_PATH . '/src/Support/helpers.php'] as $candidate) {
        if (is_file($candidate)) { $helpersFile = $candidate; break; }
    }
}
require_once $helpersFile;

// ═══════════════════════════════════════════════════════════════════════════
// Sanitização — delega para HtmlSanitizer (HTMLPurifier ou fallback regex)
// ═══════════════════════════════════════════════════════════════════════════

if (!function_exists('sanitizeHtml')) {
    function sanitizeHtml(string $html): string
    {
        return \Carlesso\Services\HtmlSanitizer::clean($html);
    }
}

// ═══════════════════════════════════════════════════════════════════════════
// Configurações do site
// ═══════════════════════════════════════════════════════════════════════════

if (!function_exists('getConfig')) {
    function getConfig(string $key, string $default = ''): string
    {
        static $cache = [];
        if (empty($cache)) {
            $rows  = Database::fetchAll('SELECT chave, valor FROM configs');
            $cache = array_column($rows, 'valor', 'chave');
        }
        return $cache[$key] ?? $default;
    }
}

if (!function_exists('setConfig')) {
    function setConfig(string $key, string $value): void
    {
        Database::query(
            'INSERT INTO configs (chave, valor) VALUES (?, ?) ON DUPLICATE KEY UPDATE valor = ?',
            [$key, $value, $value]
        );
    }
}

if (!function_exists('getCustomization')) {
    function getCustomization(string $key, string $default = ''): string
    {
        static $cache = [];
        if (empty($cache)) {
            $rows  = Database::fetchAll('SELECT setting_key, setting_value FROM customizations');
            $cache = array_column($rows, 'setting_value', 'setting_key');
        }
        return $cache[$key] ?? $default;
    }
}

// ═══════════════════════════════════════════════════════════════════════════
// Páginas
// ═══════════════════════════════════════════════════════════════════════════

if (!function_exists('getPage')) {
    function getPage(string $slug): ?array
    {
        return Database::fetchOne(
            'SELECT * FROM paginas WHERE slug = ? AND status = "publicado" LIMIT 1',
            [$slug]
        );
    }
}

if (!function_exists('getAllPages')) {
    function getAllPages(): array
    {
        return Database::fetchAll(
            'SELECT * FROM paginas WHERE show_in_menu = 1 AND status = "publicado" ORDER BY menu_order ASC'
        );
    }
}

if (!function_exists('getContent')) {
    function getContent(string $pagina, string $secao): ?array
    {
        return Database::fetchOne(
            'SELECT * FROM conteudos WHERE pagina = ? AND secao = ? LIMIT 1',
            [$pagina, $secao]
        );
    }
}

if (!function_exists('getPageSeo')) {
    function getPageSeo(string $slug): array
    {
        $seo = Database::fetchOne('SELECT * FROM seo WHERE pagina = ? LIMIT 1', [$slug]);
        if (!$seo) {
            $page = getPage($slug);
            return [
                'meta_title'       => ($page['meta_title'] ?? '') ?: (getConfig('site_titulo') . ' | ' . ($page['titulo'] ?? '')),
                'meta_description' => $page['meta_description'] ?? '',
            ];
        }
        return $seo;
    }
}

// ═══════════════════════════════════════════════════════════════════════════
// Posts (Produções)
// ═══════════════════════════════════════════════════════════════════════════

if (!function_exists('getPublishedPosts')) {
    function getPublishedPosts(int $limit = 10, int $offset = 0): array
    {
        // Cast explícito (PDO emulation off requer ints reais em LIMIT/OFFSET).
        return Database::fetchAll(
            'SELECT * FROM postagens WHERE status = "publicado" ORDER BY data_publicacao DESC LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset
        );
    }
}

// ═══════════════════════════════════════════════════════════════════════════
// Upload de mídia
// ═══════════════════════════════════════════════════════════════════════════

if (!function_exists('handleUpload')) {
    function handleUpload(array $file, string $dest = ''): array
    {
        $maxSize  = (int) env('UPLOAD_MAX_SIZE', 5242880);
        $allowed  = explode(',', env('UPLOAD_ALLOWED', 'jpg,jpeg,png,gif,webp,pdf,svg'));
        $dest     = $dest ?: PUBLIC_PATH . '/assets/images';

        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => 'Erro no upload.'];
        }

        if ($file['size'] > $maxSize) {
            return ['success' => false, 'message' => 'Arquivo muito grande (máx. ' . ($maxSize / 1048576) . 'MB).'];
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed)) {
            return ['success' => false, 'message' => 'Tipo de arquivo não permitido.'];
        }

        $finfo    = new finfo(FILEINFO_MIME_TYPE);
        $mimeReal = $finfo->file($file['tmp_name']);
        $allowedMimes = [
            'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',  'gif'  => 'image/gif',
            'webp'=> 'image/webp', 'svg'  => 'image/svg+xml',
            'pdf' => 'application/pdf',
        ];
        if (!in_array($mimeReal, array_values($allowedMimes))) {
            return ['success' => false, 'message' => 'Tipo de arquivo inválido.'];
        }

        if (!is_dir($dest)) {
            mkdir($dest, 0755, true);
        }

        $unique   = time() . '_' . bin2hex(random_bytes(4));
        $filename = $unique . '.' . $ext;
        $fullPath = $dest . '/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $fullPath)) {
            return ['success' => false, 'message' => 'Falha ao salvar arquivo.'];
        }

        $urlPath = '/assets/images/' . $filename;
        Database::insert('media', [
            'filename'      => $filename,
            'original_name' => $file['name'],
            'file_path'     => $urlPath,
            'file_type'     => $mimeReal,
            'file_size'     => $file['size'],
        ]);

        return [
            'success'  => true,
            'filename' => $filename,
            'url'      => $urlPath,
            'id'       => Database::lastId(),
        ];
    }
}

// ═══════════════════════════════════════════════════════════════════════════
// Blocos (renderer V1 — Phase 2 substitui pelo PageRenderer service)
// ═══════════════════════════════════════════════════════════════════════════

if (!function_exists('renderBlocks')) {
    function renderBlocks(string $blocksJson): string
    {
        if (empty($blocksJson)) return '';

        // Phase 2 rev 1.2: feature flag para o PageRenderer V2.
        // Quando USE_RENDERER_V2=1 no .env, delega para o renderer novo
        // (que aceita V1 via BlockTransformer + V2 nativo).
        // Quando off (default), usa o renderer legacy abaixo — mesma saída
        // byte-similar do PATCH-README v1.2 para garantia de compat.
        $useV2 = function_exists('env') && env('USE_RENDERER_V2', '0') === '1';

        if ($useV2 && class_exists(\Carlesso\Services\Blocks\PageRenderer::class)) {
            try {
                return \Carlesso\Services\Blocks\PageRenderer::default()->render($blocksJson);
            } catch (\Throwable $e) {
                // Falha catastrófica — loga e cai no legacy para não derrubar a página.
                error_log('[renderBlocks V2 fallback] ' . $e->getMessage());
            }
        }

        // Legacy renderer (V1 flat array)
        $blocks = json_decode($blocksJson, true);
        if (!$blocks) return '';
        // V2 não é renderizável pelo legacy — retorna vazio sem error.
        if (isset($blocks['version']) && (int) $blocks['version'] >= 2) {
            error_log('[renderBlocks] página em V2 mas USE_RENDERER_V2 != 1 — output vazio');
            return '';
        }

        $html = '<div class="blocks-content">';
        foreach ($blocks as $block) {
            $html .= renderBlock($block);
        }
        $html .= '</div>';
        return $html;
    }
}

if (!function_exists('renderBlock')) {
    function renderBlock(array $block): string
    {
        $type = $block['type'] ?? '';
        $data = $block['data'] ?? [];

        return match ($type) {
            'heading' => sprintf(
                '<h%d class="block-heading" style="%s">%s</h%d>',
                (int)($data['level'] ?? 2),
                e($data['style'] ?? ''),
                e($data['text'] ?? ''),
                (int)($data['level'] ?? 2)
            ),
            'text' => sprintf(
                '<div class="block-text" style="%s">%s</div>',
                e($data['style'] ?? ''),
                sanitizeHtml($data['html'] ?? '')
            ),
            'image' => sprintf(
                '<figure class="block-image %s"><img src="%s" alt="%s" loading="lazy"><figcaption>%s</figcaption></figure>',
                e($data['align'] ?? 'center'),
                e($data['url'] ?? ''),
                e($data['alt'] ?? ''),
                e($data['caption'] ?? '')
            ),
            'button' => sprintf(
                '<div class="block-button-wrap text-%s"><a href="%s" class="block-btn btn-style-%s">%s</a></div>',
                e($data['align'] ?? 'left'),
                e($data['url'] ?? '#'),
                e($data['style'] ?? 'primary'),
                e($data['text'] ?? 'Botão')
            ),
            'divider' => '<hr class="block-divider">',
            'columns' => renderColumnsBlock($data),
            default   => '',
        };
    }
}

if (!function_exists('renderColumnsBlock')) {
    function renderColumnsBlock(array $data): string
    {
        $cols = $data['columns'] ?? [];
        $html = '<div class="block-columns cols-' . count($cols) . '">';
        foreach ($cols as $col) {
            $html .= '<div class="block-col">';
            foreach ($col['blocks'] ?? [] as $sub) {
                $html .= renderBlock($sub);
            }
            $html .= '</div>';
        }
        $html .= '</div>';
        return $html;
    }
}

// ═══════════════════════════════════════════════════════════════════════════
// Mensagens flash
// ═══════════════════════════════════════════════════════════════════════════

if (!function_exists('flash')) {
    function flash(string $key, string $msg = '', string $type = 'success'): ?array
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if ($msg) {
            $_SESSION['flash'][$key] = ['msg' => $msg, 'type' => $type];
            return null;
        }
        if (isset($_SESSION['flash'][$key])) {
            $f = $_SESSION['flash'][$key];
            unset($_SESSION['flash'][$key]);
            return $f;
        }
        return null;
    }
}

if (!function_exists('showFlash')) {
    function showFlash(string $key): void
    {
        $f = flash($key);
        if ($f) {
            printf('<div class="alert alert-%s">%s</div>', e($f['type']), e($f['msg']));
        }
    }
}
