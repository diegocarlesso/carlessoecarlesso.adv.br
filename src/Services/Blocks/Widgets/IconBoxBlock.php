<?php
declare(strict_types=1);

namespace Carlesso\Services\Blocks\Widgets;

use Carlesso\Services\Blocks\Block;
use Carlesso\Services\Blocks\PageRenderer;
use Carlesso\Services\Blocks\RenderContext;

/**
 * IconBoxBlock — composto: ícone + título + descrição + link opcional.
 * Usado para grids de "features" / vantagens.
 */
final class IconBoxBlock extends AbstractBlock
{
    public const TYPE  = 'icon_box';
    public const LABEL = 'Caixa com ícone';
    public const ICON  = '◇';

    public function render(Block $block, RenderContext $ctx, PageRenderer $renderer): string
    {
        $icon  = (string) $block->setting('icon', '');
        $title = (string) $block->setting('title', '');
        $text  = (string) $block->setting('text', '');
        $link  = (array) $block->setting('link', []);
        $align = (string) $block->setting('align', 'center');

        if ($title === '' && $text === '' && $icon === '') return '';

        $style = $this->style($block);
        $style->addClass('block-icon-box')->addClass('align-' . $align);

        $iconHtml = '';
        if ($icon !== '' && function_exists('svgIcon')) {
            $iconHtml = '<div class="icon-box-icon">' . svgIcon($icon, 40) . '</div>';
        }

        $titleHtml = $title !== '' ? '<h3 class="icon-box-title">' . $this->e($title) . '</h3>' : '';
        $textHtml  = $text  !== '' ? '<p class="icon-box-text">'  . $this->e($text)  . '</p>' : '';

        $linkHtml = '';
        if (!empty($link['url'])) {
            $href   = $this->safeUrl((string) $link['url']);
            $label  = (string) ($link['label'] ?? 'Saiba mais');
            $target = (string) ($link['target'] ?? '_self');
            $rel    = $target === '_blank' ? ' rel="noopener noreferrer"' : '';
            $linkHtml = sprintf(
                '<a class="icon-box-link" href="%s" target="%s"%s>%s →</a>',
                $this->e($href), $this->e($target), $rel, $this->e($label)
            );
        }

        return '<div data-block-id="' . $this->e($block->id) . '"' . $style->getAttrs() . '>'
             . $iconHtml . $titleHtml . $textHtml . $linkHtml
             . '</div>';
    }

    public function defaultSettings(): array
    {
        return [
            'icon' => 'shield', 'title' => 'Título',
            'text' => 'Descrição breve.', 'link' => [], 'align' => 'center',
        ];
    }
}
