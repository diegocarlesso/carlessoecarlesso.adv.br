<?php
declare(strict_types=1);

namespace Carlesso\Services\Blocks\Widgets;

use Carlesso\Services\Blocks\Block;
use Carlesso\Services\Blocks\PageRenderer;
use Carlesso\Services\Blocks\RenderContext;
use Carlesso\Services\HtmlSanitizer;

/**
 * RichTextBlock — bloco de texto rico (HTML produzido pelo TipTap em Phase 3,
 * ou pelo TinyMCE/editor lite até a migração).
 *
 * Sempre passa pelo HtmlSanitizer no render — defesa em camadas, mesmo se
 * o admin tiver salvado HTML sujo.
 */
final class RichTextBlock extends AbstractBlock
{
    public const TYPE  = 'rich_text';
    public const LABEL = 'Texto';
    public const ICON  = '¶';

    public function render(Block $block, RenderContext $ctx, PageRenderer $renderer): string
    {
        $html = (string) $block->setting('html', '');
        if (trim($html) === '') return '';

        $clean = HtmlSanitizer::clean($html);

        $style = $this->style($block);
        $style->addClass('block-rich-text');

        $color = (string) $block->setting('color', '');
        if ($color !== '') $style->applyColor('text', $color, 'color');

        $align = $block->setting('align', '');
        if (is_string($align)) $align = ['desktop' => $align];
        if (is_array($align))  $style->applyTextAlign($align);

        return '<div data-block-id="' . $this->e($block->id) . '"' . $style->getAttrs() . '>'
             . $clean
             . '</div>';
    }

    public function defaultSettings(): array
    {
        return ['html' => '<p>Texto novo…</p>', 'align' => 'left', 'color' => ''];
    }
}
