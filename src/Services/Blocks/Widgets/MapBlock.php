<?php
declare(strict_types=1);

namespace Carlesso\Services\Blocks\Widgets;

use Carlesso\Services\Blocks\Block;
use Carlesso\Services\Blocks\PageRenderer;
use Carlesso\Services\Blocks\RenderContext;
use Carlesso\Services\HtmlSanitizer;

/**
 * MapBlock — mapa embed.
 *
 * Três modos por ordem de prioridade (espelha a lógica do template legacy
 * /templates/contato.php — Phase 1 PATCH-README issue #3):
 *   1. embed_html — iframe colado pelo admin (Google Maps "Compartilhar > Incorporar"),
 *      sanitizado para aceitar só <iframe> de fontes conhecidas
 *   2. lat/lng — fallback OpenStreetMap (sem chave de API)
 *   3. nada — link "Ver no Google Maps" estático
 */
final class MapBlock extends AbstractBlock
{
    public const TYPE  = 'map';
    public const LABEL = 'Mapa';
    public const ICON  = '🗺';

    public function render(Block $block, RenderContext $ctx, PageRenderer $renderer): string
    {
        $style = $this->style($block);
        $style->addClass('block-map');

        $height = (string) $block->setting('height', '400px');
        if (!preg_match('/^\d+(px|vh|%)$/', $height)) $height = '400px';
        $style->addStyle('min-height', $height);

        $embed = trim((string) $block->setting('embed_html', ''));
        if ($embed !== '') {
            $clean = $this->cleanIframe($embed);
            if ($clean !== '') {
                return '<div data-block-id="' . $this->e($block->id) . '"' . $style->getAttrs() . '>' . $clean . '</div>';
            }
        }

        $lat = (float) $block->setting('lat', 0);
        $lng = (float) $block->setting('lng', 0);
        if ($lat !== 0.0 && $lng !== 0.0) {
            $delta = 0.005;
            $bbox = sprintf('%.6f,%.6f,%.6f,%.6f',
                $lng - $delta, $lat - $delta, $lng + $delta, $lat + $delta);
            $marker = sprintf('%.6f,%.6f', $lat, $lng);
            return '<div data-block-id="' . $this->e($block->id) . '"' . $style->getAttrs() . '>'
                 . '<iframe loading="lazy" src="https://www.openstreetmap.org/export/embed.html?bbox='
                 . $this->e($bbox) . '&amp;layer=mapnik&amp;marker=' . $this->e($marker)
                 . '" title="Mapa"></iframe>'
                 . '</div>';
        }

        $address = (string) $block->setting('address', '');
        if ($address !== '') {
            $url = 'https://www.google.com/maps/search/?api=1&query=' . urlencode($address);
            return '<div data-block-id="' . $this->e($block->id) . '"' . $style->getAttrs() . '>'
                 . '<a class="btn btn-outline btn-md" href="' . $this->e($url) . '" target="_blank" rel="noopener">Ver no Google Maps</a>'
                 . '</div>';
        }

        return '';
    }

    /**
     * Aceita iframes de google.com/maps e openstreetmap.org apenas.
     * Tira atributos perigosos.
     */
    private function cleanIframe(string $html): string
    {
        if (!preg_match('#<iframe\b[^>]*\ssrc=["\']([^"\']+)["\'][^>]*></iframe>#i', $html, $m)) {
            return '';
        }
        $src   = $m[1];
        $host  = parse_url($src, PHP_URL_HOST) ?: '';
        $allow = ['www.google.com', 'maps.google.com', 'www.openstreetmap.org', 'www.google.com.br'];
        if (!in_array(strtolower($host), $allow, true)) {
            return '';
        }
        return sprintf(
            '<iframe loading="lazy" src="%s" title="Mapa" referrerpolicy="no-referrer-when-downgrade"></iframe>',
            $this->e($src)
        );
    }

    public function defaultSettings(): array
    {
        return [
            'embed_html' => '', 'lat' => 0, 'lng' => 0, 'address' => '',
            'height' => '400px',
        ];
    }
}
