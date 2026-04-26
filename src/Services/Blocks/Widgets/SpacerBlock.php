<?php
declare(strict_types=1);

namespace Carlesso\Services\Blocks\Widgets;

use Carlesso\Services\Blocks\Block;
use Carlesso\Services\Blocks\PageRenderer;
use Carlesso\Services\Blocks\RenderContext;

/**
 * SpacerBlock — espaço vertical configurável por breakpoint.
 *
 * Settings:
 *   height { desktop: 'lg', tablet: 'md', mobile: 'sm' }
 *      → utiliza utility classes spacer-d-lg, spacer-t-md, etc.
 *   custom_height '80px' (controlled style; sobrescreve as classes se setado)
 */
final class SpacerBlock extends AbstractBlock
{
    public const TYPE  = 'spacer';
    public const LABEL = 'Espaço';
    public const ICON  = '↕';

    public function render(Block $block, RenderContext $ctx, PageRenderer $renderer): string
    {
        $style = $this->style($block);
        $style->addClass('block-spacer');

        $custom = (string) $block->setting('custom_height', '');
        if ($custom !== '') {
            $style->addStyle('height', $custom);
        } else {
            $h = (array) $block->setting('height', ['desktop' => 'md']);
            $bpMap = ['desktop' => 'd', 'tablet' => 't', 'mobile' => 'm'];
            foreach ($bpMap as $bp => $abbr) {
                $size = $h[$bp] ?? null;
                if ($size && in_array($size, ['xs', 'sm', 'md', 'lg', 'xl', 'xxl'], true)) {
                    $style->addClass("spacer-$abbr-$size");
                }
            }
        }

        return '<div data-block-id="' . $this->e($block->id) . '" aria-hidden="true"' . $style->getAttrs() . '></div>';
    }

    public function defaultSettings(): array
    {
        return ['height' => ['desktop' => 'md'], 'custom_height' => ''];
    }
}
