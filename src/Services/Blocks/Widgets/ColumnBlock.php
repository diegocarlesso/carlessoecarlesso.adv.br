<?php
declare(strict_types=1);

namespace Carlesso\Services\Blocks\Widgets;

use Carlesso\Services\Blocks\Block;
use Carlesso\Services\Blocks\PageRenderer;
use Carlesso\Services\Blocks\RenderContext;

/**
 * ColumnBlock — coluna dentro de uma section (com largura por breakpoint).
 *
 * Settings:
 *   width            { desktop: 50, tablet: 100, mobile: 100 } — porcentagem
 *   vertical_align   'top' | 'middle' | 'bottom'
 *   gap              'none' | 'sm' | 'md' | 'lg'  — espaço entre filhos
 *   background_color '#xxx'
 *   padding          { desktop: 'md', ... }
 */
final class ColumnBlock extends AbstractBlock
{
    public const TYPE  = 'column';
    public const LABEL = 'Coluna';
    public const ICON  = '▯';

    public function render(Block $block, RenderContext $ctx, PageRenderer $renderer): string
    {
        $style = $this->style($block);
        $style->addClass('block-col');

        // Largura por breakpoint
        $width = (array) $block->setting('width', ['desktop' => 100]);
        $style->applyColumnWidth($width);

        // Vertical align
        $va = (string) $block->setting('vertical_align', '');
        if (in_array($va, ['top', 'middle', 'bottom'], true)) {
            $style->addClass('valign-' . $va);
        }

        // Gap interno
        $gap = (string) $block->setting('gap', 'md');
        if (in_array($gap, ['none', 'sm', 'md', 'lg', 'xl'], true)) {
            $style->addClass('gap-' . $gap);
        }

        // Background
        $bg = (string) $block->setting('background_color', '');
        if ($bg !== '') $style->applyColor('bg', $bg, 'background-color');

        // Padding
        $padding = (array) $block->setting('padding', []);
        $style->applySpacing('p', $padding);

        $children = $renderer->renderChildren($block, $ctx);

        return '<div data-block-id="' . $this->e($block->id) . '"' . $style->getAttrs() . '>'
             . $children
             . '</div>';
    }

    public function defaultSettings(): array
    {
        return [
            'width'            => ['desktop' => 100, 'tablet' => 100, 'mobile' => 100],
            'vertical_align'   => 'top',
            'gap'              => 'md',
            'background_color' => '',
            'padding'          => [],
        ];
    }
}
