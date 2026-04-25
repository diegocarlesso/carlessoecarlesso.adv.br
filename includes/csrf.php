<?php
class CSRF
{
    private static string $key = '_csrf_token';

    public static function generate(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (empty($_SESSION[self::$key])) {
            $_SESSION[self::$key] = bin2hex(random_bytes(32));
        }
        return $_SESSION[self::$key];
    }

    public static function validate(?string $token): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $stored = $_SESSION[self::$key] ?? '';
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
}
