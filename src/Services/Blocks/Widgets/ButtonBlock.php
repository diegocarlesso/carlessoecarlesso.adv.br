<?php
declare(strict_types=1);

namespace Carlesso\Services\Blocks\Widgets;

use Carlesso\Services\Blocks\Block;
use Carlesso\Services\Blocks\PageRenderer;
use Carlesso\Services\Blocks\RenderContext;

/**
 * ButtonBlock — botão CTA.
 *
 * Settings:
 *   text    string visível
 *   url     destino (validado por safeUrl)
 *   style   'primary' | 'secondary' | 'outline' | 'accent' | 'ghost'
 *   size    'sm' | 'md' | 'lg'
 *   align   'left' | 'center' | 'right' | 'block' (full-width)
 *   icon    nome de svgIcon() opcional
 *   target  '_self' | '_blank'
 */
final class ButtonBlock extends AbstractBlock
{
    public const TYPE  = 'button';
    public const LABEL = 'Botão';
    public const ICON  = '⏵';

    private const STYLES = ['primary', 'secondary', 'outline', 'accent', 'ghost'];
    private const SIZES  = ['sm', 'md', 'lg'];
    private const ALIGNS = ['left', 'center', 'right', 'block'];

    public function render(Block $block, RenderContext $ctx, PageRenderer $renderer): string
    {
        $text = (string) $block->setting('text', 'Botão');
        if ($text === '') return '';

        $url    = $this->safeUrl((string) $block->setting('url', '#'));
        $style  = (string) $block->setting('style', 'primary');
        $size   = (string) $block->setting('size',  'md');
        $align  = (string) $block->setting('align', 'left');
        $target = (string) $block->setting('target', '_self');
        $icon   = (string) $block->setting('icon', '');

        if (!in_array($style,  self::STYLES, true)) $style  = 'primary';
        if (!in_array($size,   self::SIZES,  true)) $size   = 'md';
        if (!in_array($align,  self::ALIGNS, true)) $align  = 'left';
        if (!in_array($target, ['_self', '_blank'], true)) $target = '_self';

        $wrap = $this->style($block);
        $wrap->addClass('block-button-wrap')->addClass('align-' . $align);

        $btnClasses = "btn btn-$style btn-$size";
        $rel = $target === '_blank' ? ' rel="noopener noreferrer"' : '';

        $iconHtml = '';
        if ($icon !== '' && function_exists('svgIcon')) {
            $iconHtml = '<span class="btn-icon">' . svgIcon($icon, 18) . '</span>';
        }

        return sprintf(
            '<div data-block-id="%s"%s><a href="%s" target="%s"%s class="%s">%s%s</a></div>',
            $this->e($block->id),
            $wrap->getAttrs(),
            $this->e($url),
            $this->e($target),
            $rel,
            $this->e($btnClasses),
            $iconHtml,
            $this->e($text)
        );
    }

    public function defaultSettings(): array
    {
        return [
            'text' => 'Saiba mais', 'url' => '#',
            'style' => 'primary', 'size' => 'md',
            'align' => 'left', 'target' => '_self', 'icon' => '',
        ];
    }
}
