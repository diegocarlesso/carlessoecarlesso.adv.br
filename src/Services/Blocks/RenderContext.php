<?php
declare(strict_types=1);

namespace Carlesso\Services\Blocks;

/**
 * RenderContext — estado compartilhado durante o render de uma página.
 *
 * Carrega:
 *  - depth atual (para limitar nesting recursivo e evitar bombs)
 *  - device-target hint (sempre 'desktop' em Phase 2; 3B usa para preview)
 *  - flag isV1Wrapper (para o renderer saber se um section foi sintetizado
 *    pelo BlockTransformer e poder elidir o wrapper de saída)
 *  - cache de funções de utilidade (getConfig, getCustomization)
 */
final class RenderContext
{
    public int  $depth   = 0;
    public bool $isAdmin = false;
    public string $device = 'desktop';

    /** Profundidade máxima — proteção contra árvores patológicas */
    public const MAX_DEPTH = 12;

    public function child(): self
    {
        $next = clone $this;
        $next->depth++;
        return $next;
    }

    public function tooDeep(): bool
    {
        return $this->depth > self::MAX_DEPTH;
    }
}
