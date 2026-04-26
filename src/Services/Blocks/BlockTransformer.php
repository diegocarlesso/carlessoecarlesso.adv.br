<?php
declare(strict_types=1);

namespace Carlesso\Services\Blocks;

/**
 * BlockTransformer — converte JSON V1 (flat array) em árvore V2 (section→column→widgets).
 *
 * Uso:
 *  - on-the-fly no PageRenderer (compat retroativa: páginas antigas continuam renderizando)
 *  - explicit no bin/migrate-blocks-v2.php (one-shot, persiste a versão V2 no banco)
 *
 * Formato V1 (legacy public_html/assets/js/blocks.js):
 *   [{"type":"heading","data":{"level":2,"text":"Foo"}}, ...]
 *
 * Formato V2:
 *   {"version":2,"blocks":[{"id":"...","type":"section","settings":{},"children":[...]}]}
 */
final class BlockTransformer
{
    /**
     * Detecta o formato e devolve sempre uma estrutura V2 normalizada.
     *
     * @return array{version:int,blocks:array} Sempre retorna estrutura V2.
     */
    public function normalize(string|array $input): array
    {
        $decoded = is_string($input) ? json_decode($input, true) : $input;
        if (!is_array($decoded)) {
            return ['version' => 2, 'blocks' => []];
        }

        // Já é V2?
        if (isset($decoded['version']) && (int) $decoded['version'] >= 2 && isset($decoded['blocks'])) {
            return [
                'version' => 2,
                'blocks'  => $this->normalizeBlocks((array) $decoded['blocks']),
            ];
        }

        // É V1 (array plano de {type, data}) — converte
        return [
            'version' => 2,
            'blocks'  => [$this->wrapV1($decoded)],
        ];
    }

    /**
     * Atalho: detecta se já é V2.
     */
    public static function isV2(string|array $input): bool
    {
        $decoded = is_string($input) ? json_decode($input, true) : $input;
        return is_array($decoded)
            && isset($decoded['version'])
            && (int) $decoded['version'] >= 2;
    }

    /**
     * Envelopa um array V1 em uma section sintética com uma column de 100%.
     * O id começa com 'v1-synthetic-' para que o renderer possa elidir o
     * wrapper visualmente (mantém compat byte-similar).
     */
    private function wrapV1(array $v1Array): array
    {
        $children = [];
        foreach ($v1Array as $v1Block) {
            if (!is_array($v1Block)) continue;
            $converted = $this->convertV1Block($v1Block);
            if ($converted !== null) {
                $children[] = $converted;
            }
        }

        $columnId  = 'v1-synthetic-col-' . substr(Block::uuid(), 0, 8);
        $sectionId = 'v1-synthetic-sec-' . substr(Block::uuid(), 0, 8);

        return [
            'id'       => $sectionId,
            'type'     => 'section',
            'settings' => ['_synthetic' => true, 'container' => 'inherit'],
            'children' => [
                [
                    'id'       => $columnId,
                    'type'     => 'column',
                    'settings' => [
                        '_synthetic' => true,
                        'width'      => ['desktop' => 100, 'tablet' => 100, 'mobile' => 100],
                    ],
                    'children' => $children,
                ],
            ],
        ];
    }

    /**
     * Converte um bloco V1 (com 'data') para nó V2 (com 'settings').
     * Mapping de types:
     *   text → rich_text
     *   columns → section com children=column[]
     *   resto: type idêntico, data → settings
     */
    private function convertV1Block(array $v1): ?array
    {
        $type = (string) ($v1['type'] ?? '');
        $data = is_array($v1['data'] ?? null) ? $v1['data'] : [];

        return match ($type) {
            'heading' => [
                'id' => Block::uuid(), 'type' => 'heading', 'children' => [],
                'settings' => [
                    'level' => (int) ($data['level'] ?? 2),
                    'text'  => (string) ($data['text']  ?? ''),
                ],
            ],
            'text' => [
                'id' => Block::uuid(), 'type' => 'rich_text', 'children' => [],
                'settings' => ['html' => (string) ($data['html'] ?? '')],
            ],
            'image' => [
                'id' => Block::uuid(), 'type' => 'image', 'children' => [],
                'settings' => [
                    'url'     => (string) ($data['url']     ?? ''),
                    'alt'     => (string) ($data['alt']     ?? ''),
                    'caption' => (string) ($data['caption'] ?? ''),
                    'align'   => (string) ($data['align']   ?? 'center'),
                ],
            ],
            'button' => [
                'id' => Block::uuid(), 'type' => 'button', 'children' => [],
                'settings' => [
                    'text'  => (string) ($data['text']  ?? 'Botão'),
                    'url'   => (string) ($data['url']   ?? '#'),
                    'style' => (string) ($data['style'] ?? 'primary'),
                    'align' => (string) ($data['align'] ?? 'left'),
                ],
            ],
            'divider' => [
                'id' => Block::uuid(), 'type' => 'divider', 'settings' => [], 'children' => [],
            ],
            'columns' => $this->convertV1Columns($data),
            default   => null, // type desconhecido — descarta
        };
    }

    /**
     * V1 columns (com data.columns = [{blocks: [...]}]) → V2 section com column[] children.
     */
    private function convertV1Columns(array $data): array
    {
        $cols = is_array($data['columns'] ?? null) ? $data['columns'] : [];
        $columnNodes = [];
        foreach ($cols as $col) {
            $sub = [];
            foreach ((array) ($col['blocks'] ?? []) as $b) {
                if (is_array($b)) {
                    $converted = $this->convertV1Block($b);
                    if ($converted) $sub[] = $converted;
                }
            }
            $columnNodes[] = [
                'id'       => Block::uuid(),
                'type'     => 'column',
                'settings' => ['width' => ['desktop' => (int) (100 / max(count($cols), 1))]],
                'children' => $sub,
            ];
        }

        return [
            'id'       => Block::uuid(),
            'type'     => 'section',
            'settings' => [],
            'children' => $columnNodes,
        ];
    }

    /**
     * Garante que cada bloco V2 tenha id, type, settings, children válidos.
     */
    private function normalizeBlocks(array $blocks): array
    {
        $out = [];
        foreach ($blocks as $b) {
            if (!is_array($b)) continue;
            $b['id']       = (string) ($b['id']   ?? Block::uuid());
            $b['type']     = (string) ($b['type'] ?? 'unknown');
            $b['settings'] = is_array($b['settings'] ?? null) ? $b['settings'] : [];
            $b['children'] = is_array($b['children'] ?? null) ? $this->normalizeBlocks($b['children']) : [];
            $out[] = $b;
        }
        return $out;
    }
}
