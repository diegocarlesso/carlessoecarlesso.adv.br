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

        // Tags permitidas — formatting tags ganham [style] para preservar
        // cor, alinhamento, fonte, tamanho aplicados pelo editor (TipTap/TinyMCE/lite).
        // <font> legacy aceito para compatibilidade com execCommand do navegador.
        $config->set('HTML.Allowed',
            'p[style|align|class],br,'
          . 'strong[style],em[style],u[style],s[style],b[style],i[style],'
          . 'h1[style|align|class],h2[style|align|class],h3[style|align|class],'
          . 'h4[style|align|class],h5[style|align|class],h6[style|align|class],'
          . 'ul[style|class],ol[style|class|start|type],li[style|class|value],'
          . 'a[href|target|rel|class|style|title],'
          . 'img[src|alt|width|height|loading|class|srcset|sizes|style],'
          . 'figure[class|style],figcaption[class|style],'
          . 'picture,source[srcset|type|sizes|media],'
          . 'video[src|controls|width|height|poster|loop|muted|autoplay|class|style],'
          . 'audio[src|controls|loop|class],'
          . 'blockquote[style|cite|class],'
          . 'table[style|class|border|cellpadding|cellspacing|width],'
          . 'thead[style|class],tbody[style|class],tfoot[style|class],'
          . 'tr[style|class],'
          . 'th[style|class|colspan|rowspan|scope|width|align|valign],'
          . 'td[style|class|colspan|rowspan|width|align|valign],'
          . 'span[class|style],div[class|style|align],'
          . 'hr[style|class],sub[style],sup[style],'
          . 'pre[style|class],code[style|class],'
          . 'mark[style],small[style],kbd[style],samp[style],var[style],'
          . 'font[color|face|size]'
        );

        // Whitelist de propriedades CSS — só formatação visual segura.
        // BLOQUEADO automaticamente: position, javascript:, expression(), behavior, etc.
        $config->set('CSS.AllowedProperties', [
            // Cor & background
            'color', 'background-color', 'background',
            // Texto & fonte
            'font-family', 'font-size', 'font-weight', 'font-style', 'font-variant',
            'text-align', 'text-decoration', 'text-transform', 'text-indent',
            'line-height', 'letter-spacing', 'word-spacing', 'white-space',
            'vertical-align',
            // Box model (limitado)
            'margin', 'margin-top', 'margin-right', 'margin-bottom', 'margin-left',
            'padding', 'padding-top', 'padding-right', 'padding-bottom', 'padding-left',
            'width', 'max-width', 'min-width',
            'height', 'max-height', 'min-height',
            // Border (limitado)
            'border', 'border-color', 'border-style', 'border-width', 'border-radius',
            'border-top', 'border-right', 'border-bottom', 'border-left',
            'border-collapse', 'border-spacing',
            // Display básico (sem position absoluta etc.)
            'display', 'float', 'clear',
            'list-style', 'list-style-type', 'list-style-position',
        ]);
        $config->set('CSS.AllowTricky', false); // sem expression(), url(javascript:), etc.

        // Aceita 'align' attr em block elements (Word/legacy)
        $config->set('Attr.AllowedFrameTargets', ['_blank', '_self', '_parent', '_top']);
        $config->set('HTML.TargetBlank', true);

        // URIs permitidas
        $config->set('URI.AllowedSchemes', [
            'http' => true, 'https' => true,
            'mailto' => true, 'tel' => true,
            'data' => true, // restrita a images via filter custom
        ]);

        // Define atributos data-* e aria-* nas tags relevantes.
        // DefinitionID/Rev força regeneração do cache quando mudamos a config.
        // Bumpar Rev sempre que alterar este bloco.
        $config->set('HTML.DefinitionID', 'carlesso-cms-formatting');
        $config->set('HTML.DefinitionRev', 3);
        $def = $config->getHTMLDefinition(true);
        $extraAttrs = ['data-id', 'data-block', 'aria-label', 'aria-hidden', 'aria-describedby', 'role', 'title'];
        foreach (['p', 'div', 'span', 'a', 'img', 'figure', 'section', 'article', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6'] as $tag) {
            foreach ($extraAttrs as $attr) {
                if ($def->info[$tag] ?? null) {
                    $def->info[$tag]->attr[$attr] = new \HTMLPurifier_AttrDef_Text();
                }
            }
        }

        self::$purifier = new \HTMLPurifier($config);
        return self::$purifier;
    }

    /**
     * Fallback regex usado quando HTMLPurifier não está disponível (composer
     * install não rodou ainda). Mais permissivo que o legacy — preserva
     * style="...", <font>, align, colspan/rowspan — pra não perder formatação.
     * Bloqueia o que importa: <script>, on*=, javascript:, expression(),
     * data: não-imagem, e tags potencialmente perigosas (iframe/object/embed).
     */
    private static function fallback(string $html): string
    {
        $allowed = '<p><br><strong><em><u><s><b><i><ul><ol><li><h1><h2><h3><h4><h5><h6>'
            . '<a><img><blockquote><table><thead><tbody><tfoot><tr><th><td>'
            . '<span><div><figure><figcaption><hr><sub><sup><pre><code>'
            . '<picture><source><video><audio><font><mark><small><kbd><samp><var>';
        $clean = strip_tags($html, $allowed);

        // Remove handlers JS inline (on*=)
        $clean = preg_replace('/\s+on\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^>\s]+)/i', '', $clean);

        // Remove javascript: e expression() em qualquer atributo
        $clean = preg_replace('/javascript\s*:/i', '', $clean);
        $clean = preg_replace('/expression\s*\(/i', '', $clean);
        $clean = preg_replace('/behavior\s*:/i', '', $clean);
        $clean = preg_replace('/-moz-binding\s*:/i', '', $clean);

        // data: URIs apenas para imagens em src
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
