<?php
declare(strict_types=1);

namespace Carlesso\Support;

/**
 * Csrf — proteção CSRF.
 *
 * Migrado de public_html/includes/csrf.php preservando 100% da API estática.
 * Admin pages legacy continuam usando CSRF::field(), CSRF::check(), etc. via
 * class_alias declarado em includes/csrf.php (shim).
 *
 * Phase 4 adicionará rotação por formulário (double-submit cookie pattern).
 * Por enquanto: token único por sessão, rotacionado no login.
 */
final class Csrf
{
    private const SESSION_KEY = '_csrf_token';

    public static function generate(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (empty($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
        }
        return $_SESSION[self::SESSION_KEY];
    }

    public static function validate(?string $token): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $stored = $_SESSION[self::SESSION_KEY] ?? '';
        return $stored && $token && hash_equals($stored, $token);
    }

    public static function field(): string
    {
        $token = self::generate();
        return '<input type="hidden" name="_csrf" value="' . htmlspecialchars($token, ENT_QUOTES) . '">';
    }

    public static function meta(): string
    {
        return '<meta name="csrf-token" content="' . htmlspecialchars(self::generate(), ENT_QUOTES) . '">';
    }

    public static function check(): void
    {
        $token = $_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        if (!self::validate($token)) {
            http_response_code(403);
            die(json_encode(['error' => 'Token CSRF inválido.']));
        }
    }

    /**
     * Força a rotação do token. Chamar após login bem-sucedido e mudanças
     * de privilégio para mitigar fixation.
     */
    public static function rotate(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
    }
}
