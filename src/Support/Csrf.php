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

    // ═══════════════════════════════════════════════════════════════════════
    //  STATELESS CSRF (HMAC-signed) — para forms públicos
    //  Não depende de sessão. Usa APP_SECRET do .env como chave HMAC.
    //  Formato: <expiry>.<nonce>.<hmac>  (todos em hex/base16)
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Gera um token HMAC válido por $ttlSeconds (default 2h).
     * Sem sessão. Tolerante a expiração de aba/cookie/garbage collection.
     */
    public static function generateStateless(int $ttlSeconds = 7200): string
    {
        $expiry = time() + $ttlSeconds;
        $nonce  = bin2hex(random_bytes(8));
        $sig    = self::sign("$expiry.$nonce");
        return "$expiry.$nonce.$sig";
    }

    /**
     * Valida um token HMAC. Aceita se assinatura confere E timestamp não expirou.
     */
    public static function validateStateless(?string $token): bool
    {
        if (!$token || !is_string($token)) return false;
        $parts = explode('.', $token);
        if (count($parts) !== 3) return false;
        [$expiry, $nonce, $sig] = $parts;
        if (!ctype_digit($expiry) || (int) $expiry < time()) return false;
        if (!ctype_xdigit($nonce) || strlen($nonce) !== 16) return false;
        $expected = self::sign("$expiry.$nonce");
        return hash_equals($expected, $sig);
    }

    /**
     * Assina uma payload com APP_SECRET. Fallback para um secret derivado
     * do hostname se APP_SECRET não estiver setado (instalação descuidada).
     */
    private static function sign(string $payload): string
    {
        $secret = $_ENV['APP_SECRET'] ?? getenv('APP_SECRET') ?: null;
        if (!$secret || strlen($secret) < 16) {
            // Fallback fraco mas não-vazio (instalação sem APP_SECRET)
            $secret = 'carlesso-cms-fallback-' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
        }
        return hash_hmac('sha256', $payload, $secret);
    }
}
