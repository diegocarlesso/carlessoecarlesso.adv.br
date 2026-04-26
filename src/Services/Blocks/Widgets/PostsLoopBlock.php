<?php
declare(strict_types=1);

namespace Carlesso\Services\Blocks\Widgets;

use Carlesso\Services\Blocks\Block;
use Carlesso\Services\Blocks\PageRenderer;
use Carlesso\Services\Blocks\RenderContext;
use Carlesso\Support\Database;

/**
 * PostsLoopBlock — listagem dinâmica de postagens (produções/artigos).
 *
 * Settings:
 *   limit       int (default 6)
 *   layout      'grid' | 'list'
 *   show_image  bool
 *   show_date   bool
 *   show_excerpt bool
 *   excerpt_length int (default 150)
 *   columns     int (1|2|3) — só vale para layout=grid
 */
final class PostsLoopBlock extends AbstractBlock
{
    public const TYPE  = 'posts_loop';
    public const LABEL = 'Lista de postagens';
    public const ICON  = '☰';

    public function render(Block $block, RenderContext $ctx, PageRenderer $renderer): string
    {
        $limit         = max(1, min((int) $block->setting('limit', 6), 50));
        $layout        = (string) $block->setting('layout', 'grid');
        $showImage     = (bool) $block->setting('show_image', true);
        $showDate      = (bool) $block->setting('show_date', true);
        $showExcerpt   = (bool) $block->setting('show_excerpt', true);
        $excerptLen    = (int) $block->setting('excerpt_length', 150);
        $columns       = max(1, min((int) $block->setting('columns', 3), 4));

        try {
            $posts = Database::fetchAll(
                'SELECT id, titulo, slug, resumo, conteudo, imagem, data_publicacao
                 FROM postagens
                 WHERE status = ?
                 ORDER BY data_publicacao DESC
                 LIMIT ' . $limit,
                ['publicado']
            );
        } catch (\Throwable) {
            return '<!-- posts_loop: query failed -->';
        }

        if (!$posts) return '<p class="block-posts-empty">Nenhuma postagem publicada ainda.</p>';

        $style = $this->style($block);
        $style->addClass('block-posts-loop');
        if ($layout === 'grid') {
            $style->addClass('layout-grid')->addClass('cols-' . $columns);
        } else {
            $style->addClass('layout-list');
        }

        $items = '';
        foreach ($posts as $p) {
            $href = '/producoes/' . urlencode((string) ($p['slug'] ?? $p['id']));
            $img  = trim((string) ($p['imagem'] ?? ''));
            $title = (string) ($p['titulo'] ?? 'Sem título');
            $excerpt = (string) ($p['resumo'] ?? '');
            if ($excerpt === '' && $showExcerpt) {
                $excerpt = mb_substr(strip_tags((string) ($p['conteudo'] ?? '')), 0, $excerptLen);
                if (mb_strlen($excerpt) >= $excerptLen) $excerpt .= '…';
            }
            $date = $showDate && !empty($p['data_publicacao'])
                ? date('d/m/Y', strtotime((string) $p['data_publicacao']))
                : '';

            $items .= '<article class="post-card">';
            if ($showImage && $img !== '') {
                $items .= sprintf(
                    '<a class="post-thumb" href="%s"><img src="%s" alt="%s" loading="lazy"></a>',
                    $this->e($href), $this->e($this->safeUrl($img)), $this->e($title)
                );
            }
            $items .= '<div class="post-body">';
            if ($date !== '') $items .= '<time class="post-date">' . $this->e($date) . '</time>';
            $items .= '<h3 class="post-title"><a href="' . $this->e($href) . '">' . $this->e($title) . '</a></h3>';
            if ($showExcerpt && $excerpt !== '') {
                $items .= '<p class="post-excerpt">' . $this->e($excerpt) . '</p>';
            }
            $items .= '<a class="post-more" href="' . $this->e($href) . '">Ler mais →</a>';
            $items .= '</div></article>';
        }

        return '<div data-block-id="' . $this->e($block->id) . '"' . $style->getAttrs() . '>'
             . $items
             . '</div>';
    }

    public function defaultSettings(): array
    {
        return [
            'limit' => 6, 'layout' => 'grid', 'columns' => 3,
            'show_image' => true, 'show_date' => true,
            'show_excerpt' => true, 'excerpt_length' => 150,
        ];
    }
}
