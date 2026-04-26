<?php
declare(strict_types=1);

/**
 * helpers.php — funções globais reutilizáveis.
 *
 * Carregadas via Composer autoload (files entry) ou via include direto
 * no shim public_html/includes/functions.php.
 *
 * NÃO declarar namespace aqui — estas são funções globais para máxima
 * compatibilidade com o código legacy.
 */

if (!function_exists('e')) {
    /**
     * Escape HTML para output seguro.
     */
    function e(string $str): string
    {
        return htmlspecialchars($str, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}

if (!function_exists('generateSlug')) {
    /**
     * Gera slug URL-safe a partir de texto livre.
     */
    function generateSlug(string $text): string
    {
        $text = mb_strtolower(trim($text));
        $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
        $text = preg_replace('/[^a-z0-9]+/', '-', $text);
        return trim($text, '-');
    }
}

if (!function_exists('sanitizeFilename')) {
    function sanitizeFilename(string $name): string
    {
        $name = preg_replace('/[^a-zA-Z0-9._-]/', '_', $name);
        return strtolower(trim($name, '_'));
    }
}

if (!function_exists('dateFormat')) {
    function dateFormat(string $date, string $format = 'd/m/Y'): string
    {
        try {
            return (new DateTime($date))->format($format);
        } catch (Exception) {
            return $date;
        }
    }
}

if (!function_exists('bytesFormat')) {
    function bytesFormat(int $bytes): string
    {
        if ($bytes >= 1048576) return round($bytes / 1048576, 2) . ' MB';
        if ($bytes >= 1024)    return round($bytes / 1024, 2) . ' KB';
        return $bytes . ' B';
    }
}

if (!function_exists('truncate')) {
    function truncate(string $text, int $length = 150): string
    {
        $text = strip_tags($text);
        if (mb_strlen($text) <= $length) return $text;
        return mb_substr($text, 0, $length) . '…';
    }
}

if (!function_exists('jsonResponse')) {
    function jsonResponse(array $data, int $code = 200): never
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
}

if (!function_exists('svgIcon')) {
    /**
     * Ícones SVG inline — sem dependências externas.
     * Mantido com a mesma assinatura do legacy public_html/includes/functions.php.
     */
    function svgIcon(string $name, int $size = 24): string
    {
        static $icons = [
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
}
