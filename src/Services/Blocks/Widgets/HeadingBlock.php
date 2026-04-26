<?php
declare(strict_types=1);

namespace Carlesso\Services\Blocks\Widgets;

use Carlesso\Services\Blocks\Block;
use Carlesso\Services\Blocks\PageRenderer;
use Carlesso\Services\Blocks\RenderContext;

final class HeadingBlock extends AbstractBlock
{
    public const TYPE  = 'heading';
    public const LABEL = 'Título';
    public const ICON  = 'H';

    public function render(Block $block, RenderContext $ctx, PageRenderer $renderer): string
    {
        $level = (int) $block->setting('level', 2);
        if ($level < 1 || $level > 6) $level = 2;

        $text = (string) $block->setting('text', '');
        if ($text === '') return '';

        $style = $this->style($block);
        $style->addClass('block-heading')->addClass('block-heading-h' . $level);

        $color = (string) $block->setting('color', '');
        if ($color !== '') $style->applyColor('text', $color, 'color');

        $align = $block->setting('align', '');
        if (is_string($align)) $align = ['desktop' => $align];
        if (is_array($align))  $style->applyTextAlign($align);

        return sprintf(
            '<h%1$d data-block-id="%2$s"%3$s>%4$s</h%1$d>',
            $level,
            $this->e($block->id),
            $style->getAttrs(),
            $this->e($text)
        );
    }

    public function defaultSettings(): array
    {
        return ['level' => 2, 'text' => 'Novo título', 'align' => 'left', 'color' => ''];
    }
}
