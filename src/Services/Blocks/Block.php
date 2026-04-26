<?php
declare(strict_types=1);

namespace Carlesso\Services\Blocks;

/**
 * Block — DTO imutável representando um nó da árvore de blocos.
 *
 * Cada nó tem:
 *  - id        UUID v4 estável (gerado pelo editor; persiste entre saves)
 *  - type      string do BlockRegistry ('section', 'heading', 'image', etc.)
 *  - settings  array associativo com configurações do widget (color, padding, text, ...)
 *  - children  array<Block> — apenas section/column têm filhos
 *
 * O JSON de página tem o envelope { version: 2, blocks: [Block...] }.
 */
final class Block
{
    /** @param Block[] $children */
    public function __construct(
        public readonly string $id,
        public readonly string $type,
        public readonly array  $settings = [],
        public readonly array  $children = [],
    ) {}

    /**
     * Constrói recursivamente a partir de array decodificado.
     * Aceita formato V2 nativo ou V1 (com 'data' em vez de 'settings').
     */
    public static function fromArray(array $raw): self
    {
        $type     = (string) ($raw['type'] ?? 'unknown');
        $id       = (string) ($raw['id'] ?? self::uuid());
        // V2 usa 'settings'; V1 usa 'data'. Normaliza para settings.
        $settings = is_array($raw['settings'] ?? null) ? $raw['settings']
                  : (is_array($raw['data'] ?? null) ? $raw['data'] : []);

        $children = [];
        foreach ((array) ($raw['children'] ?? []) as $child) {
            if (is_array($child)) {
                $children[] = self::fromArray($child);
            }
        }

        return new self($id, $type, $settings, $children);
    }

    /**
     * Lê uma setting com suporte a notação dotted (e.g. 'background.color').
     */
    public function setting(string $key, mixed $default = null): mixed
    {
        if (str_contains($key, '.')) {
            $parts = explode('.', $key);
            $cur   = $this->settings;
            foreach ($parts as $p) {
                if (!is_array($cur) || !array_key_exists($p, $cur)) {
                    return $default;
                }
                $cur = $cur[$p];
            }
            return $cur;
        }
        return $this->settings[$key] ?? $default;
    }

    /**
     * UUID v4 simples (sem ext-uuid).
     */
    public static function uuid(): string
    {
        $b = random_bytes(16);
        $b[6] = chr((ord($b[6]) & 0x0f) | 0x40);
        $b[8] = chr((ord($b[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($b), 4));
    }

    public function toArray(): array
    {
        return [
            'id'       => $this->id,
            'type'     => $this->type,
            'settings' => $this->settings,
            'children' => array_map(fn(Block $c) => $c->toArray(), $this->children),
        ];
    }
}
