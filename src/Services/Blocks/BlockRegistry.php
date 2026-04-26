<?php
declare(strict_types=1);

namespace Carlesso\Services\Blocks;

use Carlesso\Services\Blocks\Widgets\AbstractBlock;
use Carlesso\Services\Blocks\Widgets\ButtonBlock;
use Carlesso\Services\Blocks\Widgets\ColumnBlock;
use Carlesso\Services\Blocks\Widgets\DividerBlock;
use Carlesso\Services\Blocks\Widgets\HeadingBlock;
use Carlesso\Services\Blocks\Widgets\HtmlBlock;
use Carlesso\Services\Blocks\Widgets\IconBoxBlock;
use Carlesso\Services\Blocks\Widgets\ImageBlock;
use Carlesso\Services\Blocks\Widgets\MapBlock;
use Carlesso\Services\Blocks\Widgets\PostsLoopBlock;
use Carlesso\Services\Blocks\Widgets\RichTextBlock;
use Carlesso\Services\Blocks\Widgets\SectionBlock;
use Carlesso\Services\Blocks\Widgets\ServiceCardBlock;
use Carlesso\Services\Blocks\Widgets\SpacerBlock;
use Carlesso\Services\Blocks\Widgets\TeamCardBlock;
use Carlesso\Services\Blocks\Widgets\VideoBlock;

/**
 * BlockRegistry — mapeia type string → instância de widget.
 *
 * default() devolve um registry pré-populado com os 15 widgets built-in.
 * Plugins futuros podem fazer $registry->register('meu_widget', new MeuWidget()).
 */
final class BlockRegistry
{
    /** @var array<string, AbstractBlock> */
    private array $widgets = [];

    public function register(string $type, AbstractBlock $widget): self
    {
        $this->widgets[$type] = $widget;
        return $this;
    }

    public function get(string $type): ?AbstractBlock
    {
        return $this->widgets[$type] ?? null;
    }

    /** @return array<string, AbstractBlock> */
    public function all(): array
    {
        return $this->widgets;
    }

    public function has(string $type): bool
    {
        return isset($this->widgets[$type]);
    }

    /** Fábrica do registry default — 15 widgets built-in v2.0. */
    public static function default(): self
    {
        $r = new self();
        $r->register('section',      new SectionBlock());
        $r->register('column',       new ColumnBlock());
        $r->register('heading',      new HeadingBlock());
        $r->register('rich_text',    new RichTextBlock());
        $r->register('image',        new ImageBlock());
        $r->register('button',       new ButtonBlock());
        $r->register('divider',      new DividerBlock());
        $r->register('spacer',       new SpacerBlock());
        $r->register('video',        new VideoBlock());
        $r->register('icon_box',     new IconBoxBlock());
        $r->register('team_card',    new TeamCardBlock());
        $r->register('service_card', new ServiceCardBlock());
        $r->register('map',          new MapBlock());
        $r->register('posts_loop',   new PostsLoopBlock());
        $r->register('html',         new HtmlBlock());
        return $r;
    }
}
