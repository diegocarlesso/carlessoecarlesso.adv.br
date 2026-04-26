<?php
declare(strict_types=1);

namespace Carlesso\Services\Blocks;

/**
 * StyleBuilder — converte settings declarativas em (classes utilitárias + style attr controlado).
 *
 * Estratégia rev 1.2 (§3.6): preferir classes predefinidas em
 * public_html/assets/css/blocks.css. Quando o valor do usuário não casa
 * com a paleta/escala, cair em style="..." inline com whitelist rígida
 * (somente cor hex, width %, min-height px, background-color hex).
 *
 * Compatível com style-src-attr 'unsafe-inline' do CSP — style-src 'self'
 * permanece estrito (nada de <style> dinâmico).
 */
final class StyleBuilder
{
    /** Paleta institucional do escritório — keys batem com customizations table */
    public const PALETTE = [
        '#1a3554' => 'navy',
        '#527095' => 'primary',
        '#c8832a' => 'accent',
        '#e8d5c0' => 'cream',
        '#1c1c1c' => 'dark',
        '#ffffff' => 'white',
        '#fff'    => 'white',
    ];

    /** Escala de spacing aceita como classe utilitária */
    public const SPACING_SCALE = ['xs', 'sm', 'md', 'lg', 'xl', 'xxl', 'none'];

    /** Larguras de coluna aceitas */
    public const COLUMN_WIDTHS = [25, 33, 50, 66, 75, 100];

    private array $classes = [];
    private array $styles  = [];

    public function addClass(string|array $cls): self
    {
        foreach ((array) $cls as $c) {
            $c = trim($c);
            if ($c !== '' && preg_match('/^[a-zA-Z0-9_\- ]+$/', $c)) {
                $this->classes[$c] = true;
            }
        }
        return $this;
    }

    /**
     * Adiciona style controlado. Permite só um conjunto fechado de propriedades
     * com valores validados — qualquer coisa fora cai fora silenciosamente.
     */
    public function addStyle(string $property, string $value): self
    {
        $value = trim($value);
        if ($value === '') return $this;

        $allowed = match ($property) {
            'color', 'background-color', 'border-color' => $this->validHex($value) ? $value : null,
            'min-height', 'max-height', 'height' => $this->validLength($value) ? $value : null,
            'min-width', 'max-width', 'width'   => $this->validLength($value, allowPercent: true) ? $value : null,
            default => null,
        };

        if ($allowed !== null) {
            $this->styles[$property] = $allowed;
        }
        return $this;
    }

    /**
     * Cor: tenta classe utilitária (bg-primary, text-accent, ...) se
     * casar com a paleta; senão usa style controlado.
     *
     * @param string $kind 'text' ou 'bg'
     */
    public function applyColor(string $kind, ?string $color, string $cssProperty = 'color'): self
    {
        if (!$color) return $this;
        $color = strtolower(trim($color));
        $palette = self::PALETTE[$color] ?? null;
        if ($palette !== null) {
            $this->addClass($kind . '-' . $palette);
        } else {
            $this->addStyle($cssProperty, $color);
        }
        return $this;
    }

    /**
     * Spacing (padding/margin) por breakpoint.
     * Valor aceito: 'xs'|'sm'|'md'|'lg'|'xl'|'xxl'|'none' (gera classe).
     * Outros valores são silenciosamente ignorados em Phase 2.
     */
    public function applySpacing(string $kind, array $perBreakpoint): self
    {
        // kind: 'p' ou 'm'
        $bpMap = ['desktop' => 'd', 'tablet' => 't', 'mobile' => 'm'];
        foreach ($bpMap as $bp => $abbr) {
            $value = $perBreakpoint[$bp] ?? null;
            if (!$value) continue;
            if (in_array($value, self::SPACING_SCALE, true)) {
                $this->addClass("$kind-$abbr-$value");
            }
        }
        return $this;
    }

    /**
     * Largura de coluna por breakpoint → classes col-d-50 col-t-100 col-m-100.
     */
    public function applyColumnWidth(array $perBreakpoint): self
    {
        $bpMap = ['desktop' => 'd', 'tablet' => 't', 'mobile' => 'm'];
        foreach ($bpMap as $bp => $abbr) {
            $w = (int) ($perBreakpoint[$bp] ?? 0);
            if ($w === 0) continue;
            // Snap para a escala mais próxima
            $snapped = $this->snapWidth($w);
            $this->addClass("col-$abbr-$snapped");
        }
        return $this;
    }

    /**
     * Alinhamento de texto por breakpoint.
     */
    public function applyTextAlign(array|string $align): self
    {
        if (is_string($align)) {
            $align = ['desktop' => $align];
        }
        $bpMap = ['desktop' => 'd', 'tablet' => 't', 'mobile' => 'm'];
        $valid = ['left', 'center', 'right', 'justify'];
        foreach ($bpMap as $bp => $abbr) {
            $a = $align[$bp] ?? null;
            if (in_array($a, $valid, true)) {
                $this->addClass("text-$abbr-$a");
            }
        }
        return $this;
    }

    public function getClassAttr(): string
    {
        if (!$this->classes) return '';
        return ' class="' . htmlspecialchars(implode(' ', array_keys($this->classes)), ENT_QUOTES) . '"';
    }

    public function getStyleAttr(): string
    {
        if (!$this->styles) return '';
        $parts = [];
        foreach ($this->styles as $prop => $val) {
            $parts[] = $prop . ':' . $val;
        }
        return ' style="' . htmlspecialchars(implode(';', $parts), ENT_QUOTES) . '"';
    }

    /** Conveniência: class + style juntos. */
    public function getAttrs(): string
    {
        return $this->getClassAttr() . $this->getStyleAttr();
    }

    public function getClasses(): array
    {
        return array_keys($this->classes);
    }

    public function getStyles(): array
    {
        return $this->styles;
    }

    // ── Validação ────────────────────────────────────────────────────────

    private function validHex(string $v): bool
    {
        return (bool) preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6}|[0-9a-fA-F]{8})$/', $v);
    }

    private function validLength(string $v, bool $allowPercent = false): bool
    {
        $units = $allowPercent ? '(px|em|rem|vh|vw|%)' : '(px|em|rem|vh|vw)';
        return (bool) preg_match('/^-?\d+(?:\.\d+)?' . $units . '$/', $v);
    }

    private function snapWidth(int $w): int
    {
        $closest = self::COLUMN_WIDTHS[0];
        $best    = abs($w - $closest);
        foreach (self::COLUMN_WIDTHS as $candidate) {
            $d = abs($w - $candidate);
            if ($d < $best) {
                $best    = $d;
                $closest = $candidate;
            }
        }
        return $closest;
    }
}
