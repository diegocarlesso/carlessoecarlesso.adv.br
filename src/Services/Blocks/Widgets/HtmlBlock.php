<?php
declare(strict_types=1);

namespace Carlesso\Services\Blocks\Widgets;

use Carlesso\Services\Blocks\Block;
use Carlesso\Services\Blocks\PageRenderer;
use Carlesso\Services\Blocks\RenderContext;
use Carlesso\Services\HtmlSanitizer;

/**
 * HtmlBlock — escape-hatch para HTML cru.
 *
 * Mesmo aqui, passa pelo HtmlSanitizer (allowlist generosa mas não livre):
 * sem <script>, sem on*=, sem javascript:, sem data: URIs maliciosos.
 *
 * Phase 4: restringir uso a `Auth::can('settings.manage')` no editor para
 * que apenas admins possam adicionar/editar este tipo de bloco.
 */
final class HtmlBlock extends AbstractBlock
{
    public const TYPE  = 'html';
    public const LABEL = 'HTML personalizado';
    public const ICON  = '</>';

    public function render(Block $block, RenderContext $ctx, PageRenderer $renderer): string
    {
        $code = (string) $block->setting('code', '');
        if (trim($code) === '') return '';

        $clean = HtmlSanitizer::clean($code);

        $style = $this->style($block);
        $style->addClass('block-html');

        return '<div data-block-id="' . $this->e($block->id) . '"' . $style->getAttrs() . '>'
             . $clean
             . '</div>';
    }

    public function defaultSettings(): array
    {
        return ['code' => ''];
    }
}
