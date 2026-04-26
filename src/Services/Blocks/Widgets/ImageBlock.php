<?php
declare(strict_types=1);

namespace Carlesso\Services\Blocks\Widgets;

use Carlesso\Services\Blocks\Block;
use Carlesso\Services\Blocks\PageRenderer;
use Carlesso\Services\Blocks\RenderContext;

/**
 * ImageBlock — imagem com srcset/WebP (Phase 3 ImageProcessor) e link opcional.
 *
 * Settings:
 *   url            URL do arquivo principal (string ou media_id resolvido)
 *   media_id       (opcional, Phase 3) — id da tabela media para puxar variants
 *   alt            string
 *   caption        string
 *   align          'left' | 'center' | 'right'
 *   ratio          '16:9' | '4:3' | '1:1' | 'auto'
 *   object_fit     'cover' | 'contain' | 'fill'
 *   link           { url, target, rel }
 *   loading        'lazy' | 'eager'
 */
final class ImageBlock extends AbstractBlock
{
    public const TYPE  = 'image';
    public const LABEL = 'Imagem';
    public const ICON  = '🖼';

    public function render(Block $block, RenderContext $ctx, PageRenderer $renderer): string
    {
        $url = $this->safeUrl((string) $block->setting('url', ''));
        if ($url === '' || $url === '#') return '';

        $alt     = (string) $block->setting('alt', '');
        $caption = (string) $block->setting('caption', '');
        $align   = (string) $block->setting('align', 'center');
        $ratio   = (string) $block->setting('ratio', 'auto');
        $fit     = (string) $block->setting('object_fit', 'cover');
        $loading = (string) $block->setting('loading', 'lazy');
        if (!in_array($loading, ['lazy', 'eager'], true)) $loading = 'lazy';

        $style = $this->style($block);
        $style->addClass('block-image');
        if (in_array($align, ['left', 'center', 'right'], true)) {
            $style->addClass('align-' . $align);
        }
        if ($ratio !== 'auto') {
            $style->addClass('ratio-' . str_replace(':', '-', $ratio));
        }

        $imgClass = 'fit-' . (in_array($fit, ['cover', 'contain', 'fill'], true) ? $fit : 'cover');

        // Variants WebP / responsive — Phase 3 vai puxar de media.variants JSON
        $variants = (array) $block->setting('variants', []);
        $srcset   = '';
        $picture  = '';
        if (!empty($variants['webp'])) {
            $srcsetItems = [];
            foreach ((array) $variants['webp'] as $w => $u) {
                $srcsetItems[] = $this->e((string) $u) . ' ' . (int) $w . 'w';
            }
            $picture = '<source type="image/webp" srcset="' . implode(', ', $srcsetItems) . '">';
        }

        $imgTag = sprintf(
            '<img src="%s" alt="%s" loading="%s" class="%s">',
            $this->e($url),
            $this->e($alt),
            $this->e($loading),
            $this->e($imgClass)
        );

        $body = $picture
            ? '<picture>' . $picture . $imgTag . '</picture>'
            : $imgTag;

        // Link wrapper opcional
        $link = $block->setting('link', []);
        if (is_array($link) && !empty($link['url'])) {
            $href   = $this->safeUrl((string) $link['url']);
            $target = (string) ($link['target'] ?? '_self');
            $rel    = $target === '_blank' ? ' rel="noopener noreferrer"' : '';
            $body = sprintf('<a href="%s" target="%s"%s>%s</a>',
                $this->e($href), $this->e($target), $rel, $body);
        }

        $captionHtml = $caption !== ''
            ? '<figcaption>' . $this->e($caption) . '</figcaption>'
            : '';

        return '<figure data-block-id="' . $this->e($block->id) . '"' . $style->getAttrs() . '>'
             . $body . $captionHtml
             . '</figure>';
    }

    public function defaultSettings(): array
    {
        return [
            'url' => '', 'alt' => '', 'caption' => '',
            'align' => 'center', 'ratio' => 'auto', 'object_fit' => 'cover',
            'loading' => 'lazy', 'link' => [],
        ];
    }
}
