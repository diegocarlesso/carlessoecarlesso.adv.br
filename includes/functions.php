<?php
require_once __DIR__ . '/db.php';

// ───────────────────────────────────────────
// Sanitização e segurança
// ───────────────────────────────────────────

function e(string $str): string
{
    return htmlspecialchars($str, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

function sanitizeHtml(string $html): string
{
    // Remove tags perigosas preservando HTML legítimo
    $allowed = '<p><br><strong><em><u><s><ul><ol><li><h1><h2><h3><h4><h5><h6>'
        . '<a><img><blockquote><table><thead><tbody><tr><th><td><span><div>'
        . '<figure><figcaption><hr><sub><sup><pre><code>';
    $clean = strip_tags($html, $allowed);
    // Remove atributos on* (XSS)
    $clean = preg_replace('/\s+on\w+\s*=\s*["\'][^"\']*["\']/', '', $clean);
    $clean = preg_replace('/javascript\s*:/i', '', $clean);
    return $clean;
}

function sanitizeFilename(string $name): string
{
    $name = preg_replace('/[^a-zA-Z0-9._-]/', '_', $name);
    return strtolower(trim($name, '_'));
}

function generateSlug(string $text): string
{
    $text = mb_strtolower(trim($text));
    $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    return trim($text, '-');
}

// ───────────────────────────────────────────
// Configurações do site
// ───────────────────────────────────────────

function getConfig(string $key, string $default = ''): string
{
    static $cache = [];
    if (empty($cache)) {
        $rows  = Database::fetchAll('SELECT chave, valor FROM configs');
        $cache = array_column($rows, 'valor', 'chave');
    }
    return $cache[$key] ?? $default;
}

function setConfig(string $key, string $value): void
{
    Database::query(
        'INSERT INTO configs (chave, valor) VALUES (?, ?) ON DUPLICATE KEY UPDATE valor = ?',
        [$key, $value, $value]
    );
}

function getCustomization(string $key, string $default = ''): string
{
    static $cache = [];
    if (empty($cache)) {
        $rows  = Database::fetchAll('SELECT setting_key, setting_value FROM customizations');
        $cache = array_column($rows, 'setting_value', 'setting_key');
    }
    return $cache[$key] ?? $default;
}

// ───────────────────────────────────────────
// Páginas
// ───────────────────────────────────────────

function getPage(string $slug): ?array
{
    return Database::fetchOne(
        'SELECT * FROM paginas WHERE slug = ? AND status = "publicado" LIMIT 1',
        [$slug]
    );
}

function getAllPages(): array
{
    return Database::fetchAll(
        'SELECT * FROM paginas WHERE show_in_menu = 1 AND status = "publicado" ORDER BY menu_order ASC'
    );
}

function getContent(string $pagina, string $secao): ?array
{
    return Database::fetchOne(
        'SELECT * FROM conteudos WHERE pagina = ? AND secao = ? LIMIT 1',
        [$pagina, $secao]
    );
}

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

// ───────────────────────────────────────────
// Posts (Produções)
// ───────────────────────────────────────────

function getPublishedPosts(int $limit = 10, int $offset = 0): array
{
    return Database::fetchAll(
        'SELECT * FROM postagens WHERE status = "publicado" ORDER BY data_publicacao DESC LIMIT ? OFFSET ?',
        [$limit, $offset]
    );
}

// ───────────────────────────────────────────
// Upload de mídia
// ───────────────────────────────────────────

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

    // Verificação de MIME real
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

    // Registra na biblioteca de mídia
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

// ───────────────────────────────────────────
// Blocos (editor Elementor-like)
// ───────────────────────────────────────────

function renderBlocks(string $blocksJson): string
{
    if (empty($blocksJson)) return '';
    $blocks = json_decode($blocksJson, true);
    if (!$blocks) return '';

    $html = '<div class="blocks-content">';
    foreach ($blocks as $block) {
        $html .= renderBlock($block);
    }
    $html .= '</div>';
    return $html;
}

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

// ───────────────────────────────────────────
// Mensagens flash
// ───────────────────────────────────────────

function flash(string $key, string $msg = '', string $type = 'success'): ?array
{
    if (session_status() === PHP_SESSION_NONE) session_start();
    if ($msg) {
        $_SESSION['flash'][$key] = ['msg' => $msg, 'type' => $type];
        return null;
    }
    if (isset($_SESSION['flash'][$key])) {
        $flash = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return $flash;
    }
    return null;
}

function showFlash(string $key): void
{
    $f = flash($key);
    if ($f) {
        printf('<div class="alert alert-%s">%s</div>', e($f['type']), e($f['msg']));
    }
}

// ───────────────────────────────────────────
// Formatação
// ───────────────────────────────────────────

function dateFormat(string $date, string $format = 'd/m/Y'): string
{
    try {
        return (new DateTime($date))->format($format);
    } catch (Exception) {
        return $date;
    }
}

function bytesFormat(int $bytes): string
{
    if ($bytes >= 1048576) return round($bytes / 1048576, 2) . ' MB';
    if ($bytes >= 1024)    return round($bytes / 1024, 2) . ' KB';
    return $bytes . ' B';
}

function truncate(string $text, int $length = 150): string
{
    $text = strip_tags($text);
    if (mb_strlen($text) <= $length) return $text;
    return mb_substr($text, 0, $length) . '…';
}

// ───────────────────────────────────────────
// Ícones inline (SVG) — sem dependência externa
// ───────────────────────────────────────────

function svgIcon(string $name, int $size = 24): string
{
    $icons = [
        'envelope'  => '<path d="M3 6.5l9 6 9-6M5 5h14a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2z"/>',
        'phone'     => '<path d="M5 4h4l2 5-3 2a11 11 0 0 0 5 5l2-3 5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2z"/>',
        'pin'       => '<path d="M12 22s7-7.5 7-13a7 7 0 1 0-14 0c0 5.5 7 13 7 13z"/><circle cx="12" cy="9" r="2.5"/>',
        'clock'     => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'send'      => '<path d="M22 2 11 13M22 2l-7 20-4-9-9-4 20-7z"/>',
        'people'    => '<circle cx="9" cy="8" r="3.5"/><path d="M3 20a6 6 0 0 1 12 0M16 11a3 3 0 1 0 0-6M21 20a5 5 0 0 0-4-4.9"/>',
        'briefcase' => '<rect x="3" y="7" width="18" height="13" rx="2"/><path d="M9 7V5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2M3 13h18"/>',
        'scale'     => '<path d="M12 3v18M5 21h14M7 7h10M5 7l-2 6a3 3 0 0 0 6 0L7 7zm12 0-2 6a3 3 0 0 0 6 0l-2-6"/>',
        'shield'    => '<path d="M12 3 4 6v6c0 5 3.5 9 8 10 4.5-1 8-5 8-10V6l-8-3z"/>',
        'document'  => '<path d="M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9l-6-6z"/><path d="M14 3v6h6M9 13h6M9 17h6"/>',
        'person'    => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
        'calendar'  => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M8 3v4M16 3v4M3 11h18"/>',
        'eye'       => '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/>',
        'target'    => '<circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5"/><circle cx="12" cy="12" r="1.5"/>',
        'instagram' => '<rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1.2" fill="currentColor"/>',
        'facebook'  => '<path d="M14 8.5h2.5V5h-2.5C12 5 10.5 6.5 10.5 8.5V11H8v3.5h2.5V21h3.5v-6.5h2.5l.5-3.5H14V9c0-.3.2-.5.5-.5z"/>',
        'whatsapp'  => '<path d="M21 12a9 9 0 0 1-13.5 7.8L3 21l1.3-4.5A9 9 0 1 1 21 12z"/><path d="M9 9.5c0 3 2.5 5.5 5.5 5.5l1.5-1.5-2.5-1L13 13.5c-1 0-2-1-2-2l-.5-.5-1 2.5z" fill="currentColor"/>',
        'linkedin'  => '<rect x="3" y="3" width="18" height="18" rx="2"/><path d="M8 10v7M8 7v.5M12 17v-4a2 2 0 0 1 4 0v4M12 17v-7" stroke-linecap="round"/>',
    ];

    if (!isset($icons[$name])) {
        return '';
    }

    return sprintf(
        '<svg xmlns="http://www.w3.org/2000/svg" width="%d" height="%d" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">%s</svg>',
        $size, $size, $icons[$name]
    );
}

// ───────────────────────────────────────────
// Resposta JSON para API
// ───────────────────────────────────────────

function jsonResponse(array $data, int $code = 200): never
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}
