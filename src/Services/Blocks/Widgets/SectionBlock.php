<?php
declare(strict_types=1);

namespace Carlesso\Services\Blocks\Widgets;

use Carlesso\Services\Blocks\Block;
use Carlesso\Services\Blocks\PageRenderer;
use Carlesso\Services\Blocks\RenderContext;

/**
 * SectionBlock — container raiz, agrupa columns horizontalmente.
 *
 * Settings esperadas:
 *   container        'boxed' | 'wide' | 'full' | 'inherit'
 *   background       { type: 'color'|'image', value: '#xxx' | url }
 *   padding          { desktop: 'lg', tablet: 'md', mobile: 'sm' }
 *   margin           { desktop: 'none', ... }
 *   min_height       '600px' (controlled style)
 *   text_color       '#xxx' or paleta key
 *   _synthetic       true se foi sintetizado pelo BlockTransformer
 *                    (V1 → V2): nesse caso emite só os children sem wrapper
 */
final class SectionBlock extends AbstractBlock
{
    public const TYPE  = 'section';
    public const LABEL = 'Seção';
    public const ICON  = '▭';

    public function render(Block $block, RenderContext $ctx, PageRenderer $renderer): string
    {
        // Compat V1: section sintética emite children direto sem wrapper
        if ($block->setting('_synthetic') === true) {
            return $renderer->renderChildren($block, $ctx);
        }

        $style = $this->style($block);
        $style->addClass('block-section');

        // Container
        $container = (string) $block->setting('container', 'boxed');
        if (in_array($container, ['boxed', 'wide', 'full'], true)) {
            $style->addClass('container-' . $container);
        }

        // Background color
        $bg = $block->setting('background.color') ?? $block->setting('background_color');
        if (is_string($bg) && $bg !== '') {
            $style->applyColor('bg', $bg, 'background-color');
        }

        // Text color
        $tc = (string) $block->setting('text_color', '');
        if ($tc !== '') {
            $style->applyColor('text', $tc, 'color');
        }

        // Spacing
        $padding = (array) $block->setting('padding', []);
        $margin  = (array) $block->setting('margin',  []);
        $style->applySpacing('p', $padding);
        $style->applySpacing('m', $margin);

        // Min-height (controlled style)
        $minH = (string) $block->setting('min_height', '');
        if ($minH !== '') $style->addStyle('min-height', $minH);

        // Inner row layout (columns side-by-side)
        $children = $renderer->renderChildren($block, $ctx);

        return '<section data-block-id="' . $this->e($block->id) . '"' . $style->getAttrs() . '>'
             . '<div class="section-row">' . $children . '</div>'
             . '</section>';
    }

    public function defaultSettings(): array
    {
        return [
            'container'  => 'boxed',
            'padding'    => ['desktop' => 'lg', 'tablet' => 'md', 'mobile' => 'sm'],
            'margin'     => [],
            'background' => ['color' => ''],
            'text_color' => '',
            'min_height' => '',
            'css_class'  => '',
        ];
    }
}
