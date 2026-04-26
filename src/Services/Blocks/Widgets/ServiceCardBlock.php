<?php
declare(strict_types=1);

namespace Carlesso\Services\Blocks\Widgets;

use Carlesso\Services\Blocks\Block;
use Carlesso\Services\Blocks\PageRenderer;
use Carlesso\Services\Blocks\RenderContext;

/**
 * ServiceCardBlock — card de área de atuação (ícone + título + descrição + CTA).
 * Usado na página /servicos.
 */
final class ServiceCardBlock extends AbstractBlock
{
    public const TYPE  = 'service_card';
    public const LABEL = 'Card de serviço';
    public const ICON  = '⚖';

    public function render(Block $block, RenderContext $ctx, PageRenderer $renderer): string
    {
        $icon  = (string) $block->setting('icon', 'briefcase');
        $title = (string) $block->setting('title', '');
        $text  = (string) $block->setting('text', '');
        $link  = (array)  $block->setting('link', []);

        if ($title === '' && $text === '') return '';

        $style = $this->style($block);
        $style->addClass('block-service-card');

        $iconHtml = function_exists('svgIcon') && $icon !== ''
            ? '<div class="service-icon">' . svgIcon($icon, 36) . '</div>'
            : '';

        $linkHtml = '';
        if (!empty($link['url'])) {
            $href = $this->safeUrl((string) $link['url']);
            $label = (string) ($link['label'] ?? 'Conheça');
            $linkHtml = sprintf('<a class="service-cta" href="%s">%s →</a>',
                $this->e($href), $this->e($label));
        }

        return '<article data-block-id="' . $this->e($block->id) . '"' . $style->getAttrs() . '>'
             . $iconHtml
             . '<h3 class="service-title">' . $this->e($title) . '</h3>'
             . '<p class="service-text">' . $this->e($text) . '</p>'
             . $linkHtml
             . '</article>';
    }

    public function defaultSettings(): array
    {
        return [
            'icon' => 'briefcase', 'title' => 'Direito X',
            'text' => 'Descrição da área de atuação.', 'link' => [],
        ];
    }
}
