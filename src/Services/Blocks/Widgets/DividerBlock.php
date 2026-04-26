<?php
declare(strict_types=1);

namespace Carlesso\Services\Blocks\Widgets;

use Carlesso\Services\Blocks\Block;
use Carlesso\Services\Blocks\PageRenderer;
use Carlesso\Services\Blocks\RenderContext;

final class DividerBlock extends AbstractBlock
{
    public const TYPE  = 'divider';
    public const LABEL = 'Divisor';
    public const ICON  = '—';

    public function render(Block $block, RenderContext $ctx, PageRenderer $renderer): string
    {
        $style = $this->style($block);
        $style->addClass('block-divider');

        $variant = (string) $block->setting('style', 'solid');
        if (in_array($variant, ['solid', 'dashed', 'dotted', 'double'], true)) {
            $style->addClass('divider-' . $variant);
        }

        $width = (string) $block->setting('width', 'full');
        if (in_array($width, ['short', 'medium', 'full'], true)) {
            $style->addClass('w-' . $width);
        }

        $color = (string) $block->setting('color', '');
        if ($color !== '') $style->applyColor('text', $color, 'border-color');

        return '<hr data-block-id="' . $this->e($block->id) . '"' . $style->getAttrs() . '>';
    }

    public function defaultSettings(): array
    {
        return ['style' => 'solid', 'width' => 'full', 'color' => ''];
    }
}
