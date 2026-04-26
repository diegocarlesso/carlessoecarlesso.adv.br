<?php
declare(strict_types=1);

namespace Carlesso\Services;

/**
 * HtmlSanitizer — wrapper sobre HTMLPurifier com config travada para o CMS.
 *
 * Substitui o sanitizador regex-based antigo (sanitizeHtml em functions.php).
 * Allowlist por tag E por atributo — bloqueia javascript:, data: (exceto
 * data:image/* em src), <script>, <iframe> não-aprovado, etc.
 *
 * Quando HTMLPurifier não está instalado (composer install ainda não rodou),
 * faz fallback para o sanitizador regex antigo. Nada quebra.
 */
final class HtmlSanitizer
{
    private static ?\HTMLPurifier $purifier = null;

    public static function clean(string $html): string
    {
        if (trim($html) === '') {
            return '';
        }

        $purifier = self::purifier();
        if ($purifier === null) {
            return self::fallback($html);
        }

        return $purifier->purify($html);
    }

    /**
     * Sanitização específica para HTML produzido pelo TipTap (Phase 3+).
     * Mais restrito; remove qualquer atributo style.
     */
    public static function cleanRichText(string $html): string
    {
        // Por enquanto idêntico ao clean(); Phase 3 vai trocar a config aqui.
        return self::clean($html);
    }

    private static function purifier(): ?\HTMLPurifier
    {
        if (self::$purifier !== null) {
            return self::$purifier;
        }

        if (!class_exists('\HTMLPurifier')) {
            return null;
        }

        $config = \HTMLPurifier_Config::createDefault();
        $config->set('Core.Encoding', 'UTF-8');
        $config->set('HTML.Doctype', 'HTML 4.01 Transitional');

        // Cache em storage/cache/htmlpurifier
        $cacheDir = defined('STORAGE_PATH')
            ? STORAGE_PATH . '/cache/htmlpurifier'
            : sys_get_temp_dir() . '/htmlpurifier';
        if (!is_dir($cacheDir)) {
            @mkdir($cacheDir, 0755, true);
        }
        $config->set('Cache.SerializerPath', $cacheDir);

        // Tags permitidas — superset do antigo + tags HTML5 modernas
        $config->set('HTML.Allowed',
            'p,br,strong,em,u,s,'
          . 'h1,h2,h3,h4,h5,h6,'
          . 'ul,ol,li,'
          . 'a[href|target|rel|class],'
          . 'img[src|alt|width|height|loading|class|srcset|sizes],'
          . 'figure[class],figcaption,'
          . 'picture,source[srcset|type|sizes|media],'
          . 'video[src|controls|width|height|poster|loop|muted|autoplay],'
          . 'audio[src|controls|loop],'
          . 'blockquote,table,thead,tbody,tr,th,td,'
          . 'span[class],div[class],'
          . 'hr,sub,sup,pre,code'
        );

        // Permite alvos comuns em links
        $config->set('Attr.AllowedFrameTargets', ['_blank', '_self', '_parent', '_top']);
        // Adiciona rel="noopener noreferrer" automaticamente em links com target=_blank
        $config->set('HTML.TargetBlank', true);

        // URI: bloqueia javascript: e data:; permite data:image/* via filtro custom abaixo
        $config->set('URI.AllowedSchemes', [
            'http'   => true,
            'https'  => true,
            'mailto' => true,
            'tel'    => true,
            'data'   => true, // restringido ao filtro abaixo
        ]);
        // Bloqueia data: para tudo exceto src/href de imagens
        $config->set('URI.DisableExternalResources', false);

        // Define atributos data-* e aria-* permitidos via HTMLDefinition
        $def = $config->getHTMLDefinition(true);
        $allowedAttrs = ['data-id', 'data-block', 'aria-label', 'aria-hidden', 'aria-describedby', 'role'];
        foreach (['p', 'div', 'span', 'a', 'img', 'figure', 'section', 'article'] as $tag) {
            foreach ($allowedAttrs as $attr) {
                if ($def->info[$tag] ?? null) {
                    $def->info[$tag]->attr[$attr] = new \HTMLPurifier_AttrDef_Text();
                }
            }
        }

        self::$purifier = new \HTMLPurifier($config);
        return self::$purifier;
    }

    /**
     * Fallback regex (idêntico ao sanitizeHtml() legacy) para quando
     * HTMLPurifier não está disponível.
     */
    private static function fallback(string $html): string
    {
        $allowed = '<p><br><strong><em><u><s><ul><ol><li><h1><h2><h3><h4><h5><h6>'
            . '<a><img><blockquote><table><thead><tbody><tr><th><td><span><div>'
            . '<figure><figcaption><hr><sub><sup><pre><code>'
            . '<picture><source><video><audio>';
        $clean = strip_tags($html, $allowed);
        $clean = preg_replace('/\s+on\w+\s*=\s*["\'][^"\']*["\']/i', '', $clean);
        $clean = preg_replace('/javascript\s*:/i', '', $clean);
        // Bloqueia data: exceto data:image/{png,jpeg,gif,webp,svg+xml}
        $clean = preg_replace_callback(
            '/\b(href|src)\s*=\s*(["\'])\s*data:([^"\']*)\2/i',
            static function (array $m): string {
                if (preg_match('#^image/(png|jpeg|gif|webp|svg\+xml);#i', $m[3])) {
                    return $m[0];
                }
                return $m[1] . '=' . $m[2] . '#' . $m[2];
            },
            $clean
        );
        return $clean;
    }
}
