<?php
declare(strict_types=1);

namespace Carlesso\Services\Blocks\Widgets;

use Carlesso\Services\Blocks\Block;
use Carlesso\Services\Blocks\PageRenderer;
use Carlesso\Services\Blocks\RenderContext;
use Carlesso\Services\Blocks\StyleBuilder;

/**
 * AbstractBlock — base para todos os widgets.
 *
 * Cada widget concreto sobrescreve render() para emitir HTML.
 * Phase 3 vai chamar settingsSchema() e defaultSettings() do editor.
 */
abstract class AbstractBlock
{
    /** Identificador único do widget — bate com BlockRegistry */
    public const TYPE = '';

    /** Label PT-BR exibido na palette do editor (Phase 3) */
    public const LABEL = '';

    /** Ícone do widget na palette (Phase 3) — nome de svgIcon() ou emoji */
    public const ICON = '';

    abstract public function render(Block $block, RenderContext $ctx, PageRenderer $renderer): string;

    /**
     * Settings padrão usados quando o editor cria um bloco novo.
     * Sobrescrever em cada widget concreto.
     */
    public function defaultSettings(): array
    {
        return [];
    }

    /**
     * Schema declarativo para a UI do inspector do editor (Phase 3).
     * Cada item: ['key', 'type'(text|number|color|select|...), 'label', 'options']
     */
    public function settingsSchema(): array
    {
        return [];
    }

    /**
     * Helper: HTML escape.
     */
    protected function e(string $s): string
    {
        return htmlspecialchars($s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Helper: novo StyleBuilder pré-aplicado com css_class do bloco
     * (campo livre do usuário; sempre disponível em qualquer widget).
     */
    protected function style(Block $b): StyleBuilder
    {
        $s = new StyleBuilder();
        $custom = trim((string) $b->setting('css_class', ''));
        if ($custom !== '') {
            $s->addClass($custom);
        }
        return $s;
    }

    /**
     * Sanitiza URL para uso em href/src. Retorna '#' para schemes não permitidos.
     */
    protected function safeUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '') return '';
        if (str_starts_with($url, '/') || str_starts_with($url, '#')) return $url;
        $scheme = strtolower(parse_url($url, PHP_URL_SCHEME) ?: '');
        if (in_array($scheme, ['http', 'https', 'mailto', 'tel'], true)) {
            return $url;
        }
        return '#';
    }
}
