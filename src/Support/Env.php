<?php
declare(strict_types=1);

namespace Carlesso\Support;

/**
 * Env — leitor de variáveis de ambiente.
 *
 * Mantém compatibilidade com a função global env() definida em
 * public_html/includes/config.php. Esta classe é o ponto de entrada
 * preferido para código novo dentro de Carlesso\*.
 */
final class Env
{
    /**
     * Lê uma variável do ambiente, retornando o default se ausente.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, $_ENV)) {
            return $_ENV[$key];
        }
        $val = getenv($key);
        return $val !== false ? $val : $default;
    }

    /**
     * Lê e converte para int.
     */
    public static function getInt(string $key, int $default = 0): int
    {
        $value = self::get($key, $default);
        return is_numeric($value) ? (int) $value : $default;
    }

    /**
     * Lê e converte para bool ("1", "true", "yes", "on" → true).
     */
    public static function getBool(string $key, bool $default = false): bool
    {
        $value = self::get($key, $default ? '1' : '0');
        if (is_bool($value)) {
            return $value;
        }
        return in_array(strtolower((string) $value), ['1', 'true', 'yes', 'on'], true);
    }
}
