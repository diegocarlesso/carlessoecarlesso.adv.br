<?php
declare(strict_types=1);

namespace Carlesso\Services\Blocks\Widgets;

use Carlesso\Services\Blocks\Block;
use Carlesso\Services\Blocks\PageRenderer;
use Carlesso\Services\Blocks\RenderContext;

/**
 * VideoBlock — embed YouTube/Vimeo ou MP4 self-hosted.
 *
 * Settings:
 *   url        URL do vídeo (YouTube, Vimeo, ou MP4)
 *   ratio      '16:9' | '4:3' | '21:9' | '1:1'
 *   autoplay   bool (apenas YouTube/Vimeo respeitam — browsers bloqueiam autoplay com som)
 *   muted      bool
 *   controls   bool (default true; só MP4 honra)
 *   loop       bool
 *   poster     URL para frame inicial (só MP4)
 */
final class VideoBlock extends AbstractBlock
{
    public const TYPE  = 'video';
    public const LABEL = 'Vídeo';
    public const ICON  = '▶';

    public function render(Block $block, RenderContext $ctx, PageRenderer $renderer): string
    {
        $url = trim((string) $block->setting('url', ''));
        if ($url === '') return '';

        $ratio = (string) $block->setting('ratio', '16:9');
        if (!in_array($ratio, ['16:9', '4:3', '21:9', '1:1'], true)) $ratio = '16:9';

        $style = $this->style($block);
        $style->addClass('block-video')->addClass('ratio-' . str_replace(':', '-', $ratio));

        $embed = $this->resolveEmbed($url, $block);

        return '<div data-block-id="' . $this->e($block->id) . '"' . $style->getAttrs() . '>'
             . '<div class="video-frame">' . $embed . '</div>'
             . '</div>';
    }

    private function resolveEmbed(string $url, Block $block): string
    {
        // YouTube
        if (preg_match('#youtube\.com/watch\?v=([\w-]+)|youtu\.be/([\w-]+)#i', $url, $m)) {
            $id = $m[1] ?: ($m[2] ?? '');
            if ($id !== '') {
                $params = [];
                if ($block->setting('autoplay', false)) $params[] = 'autoplay=1';
                if ($block->setting('muted', false))    $params[] = 'mute=1';
                if ($block->setting('loop', false))     { $params[] = 'loop=1'; $params[] = 'playlist=' . $id; }
                $qs = $params ? '?' . implode('&', $params) : '';
                return sprintf(
                    '<iframe src="https://www.youtube-nocookie.com/embed/%s%s" title="YouTube" loading="lazy" allow="accelerometer; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>',
                    $this->e($id), $this->e($qs)
                );
            }
        }

        // Vimeo
        if (preg_match('#vimeo\.com/(\d+)#i', $url, $m)) {
            $id = $m[1];
            return sprintf(
                '<iframe src="https://player.vimeo.com/video/%s" title="Vimeo" loading="lazy" allow="autoplay; fullscreen; picture-in-picture" allowfullscreen></iframe>',
                $this->e($id)
            );
        }

        // MP4 / WebM nativo
        if (preg_match('#\.(mp4|webm|ogg)(\?|$)#i', $url)) {
            $controls = $block->setting('controls', true) ? ' controls' : '';
            $autoplay = $block->setting('autoplay', false) ? ' autoplay' : '';
            $muted    = $block->setting('muted', false)    ? ' muted'    : '';
            $loop     = $block->setting('loop', false)     ? ' loop'     : '';
            $poster   = trim((string) $block->setting('poster', ''));
            $posterAttr = $poster !== '' ? ' poster="' . $this->e($this->safeUrl($poster)) . '"' : '';
            return sprintf(
                '<video src="%s"%s%s%s%s%s playsinline></video>',
                $this->e($this->safeUrl($url)),
                $controls, $autoplay, $muted, $loop, $posterAttr
            );
        }

        // URL desconhecida — link simples
        return '<p>Vídeo: <a href="' . $this->e($this->safeUrl($url)) . '" rel="noopener" target="_blank">abrir</a></p>';
    }

    public function defaultSettings(): array
    {
        return [
            'url' => '', 'ratio' => '16:9',
            'autoplay' => false, 'muted' => false,
            'controls' => true, 'loop' => false, 'poster' => '',
        ];
    }
}
