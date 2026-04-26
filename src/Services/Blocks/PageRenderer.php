<?php
declare(strict_types=1);

namespace Carlesso\Services\Blocks;

/**
 * PageRenderer — entry point para renderizar JSON de blocos como HTML.
 *
 * Phase 2: roda atrás do feature flag USE_RENDERER_V2=1 (no .env).
 * Quando off, public_html/includes/functions.php usa o renderer legacy
 * (renderBlock() inline) para garantia de compat byte-similar.
 *
 * Quando on, este é o renderer único — V2 nativo + V1 via BlockTransformer.
 *
 * Uso:
 *   $renderer = PageRenderer::default();
 *   echo $renderer->render($paginaRow['blocos']);
 */
final class PageRenderer
{
    public function __construct(
        private readonly BlockRegistry      $registry,
        private readonly BlockTransformer   $transformer,
    ) {}

    public static function default(): self
    {
        return new self(BlockRegistry::default(), new BlockTransformer());
    }

    /**
     * Recebe JSON string ou array já decodificado; devolve HTML completo.
     */
    public function render(string|array|null $blocksJson): string
    {
        if ($blocksJson === null || $blocksJson === '' || $blocksJson === '[]') {
            return '';
        }

        $normalized = $this->transformer->normalize($blocksJson);
        if (empty($normalized['blocks'])) {
            return '';
        }

        $ctx = new RenderContext();
        $html = '<div class="blocks-content">';
        foreach ($normalized['blocks'] as $blockArray) {
            $block = Block::fromArray($blockArray);
            $html .= $this->renderBlock($block, $ctx);
        }
        $html .= '</div>';
        return $html;
    }

    /**
     * Renderiza um Block individual. Chamado recursivamente pelos widgets
     * de container (section/column) via $ctx->renderer.
     */
    public function renderBlock(Block $block, RenderContext $ctx): string
    {
        if ($ctx->tooDeep()) {
            return ''; // proteção contra recursão patológica
        }

        $widget = $this->registry->get($block->type);
        if ($widget === null) {
            return '<!-- unknown block type: ' . htmlspecialchars($block->type, ENT_QUOTES) . ' -->';
        }

        try {
            return $widget->render($block, $ctx, $this);
        } catch (\Throwable $e) {
            // Falha de um widget não deve derrubar a página inteira.
            error_log("[PageRenderer] $block->type ($block->id): " . $e->getMessage());
            return '<!-- block render error -->';
        }
    }

    /**
     * Helper para containers (SectionBlock, ColumnBlock) renderizarem filhos.
     */
    public function renderChildren(Block $block, RenderContext $ctx): string
    {
        $childCtx = $ctx->child();
        $out = '';
        foreach ($block->children as $child) {
            $out .= $this->renderBlock($child, $childCtx);
        }
        return $out;
    }

    public function registry(): BlockRegistry
    {
        return $this->registry;
    }
}
