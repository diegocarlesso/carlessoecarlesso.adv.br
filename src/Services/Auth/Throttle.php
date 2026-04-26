<?php
declare(strict_types=1);

namespace Carlesso\Services\Auth;

use Carlesso\Support\Database;

/**
 * Throttle — rate limiter real por IP, persistido em auth_throttle.
 *
 * Substitui o counter de sessão quebrado do AuthService antigo.
 * O contador antigo vivia em $_SESSION e nunca acumulava entre tentativas
 * de atacantes (cada tentativa nova = nova sessão = contador zerado).
 *
 * Esta classe persiste por hash de IP e bloqueia globalmente.
 */
final class Throttle
{
    private const MAX_ATTEMPTS  = 5;
    private const LOCKOUT_TIME  = 300; // 5 minutos
    private const ATTEMPT_WINDOW = 300; // janela de 5 min

    public static function isBlocked(string $ip): bool
    {
        $hash  = self::hash($ip);
        $row   = Database::fetchOne(
            'SELECT attempts, blocked_until, last_attempt FROM auth_throttle WHERE ip_hash = ?',
            [$hash]
        );
        if (!$row) {
            return false;
        }

        $now = time();

        // Bloqueio explícito ativo
        if ((int) $row['blocked_until'] > $now) {
            return true;
        }

        // Bloqueio expirado — limpa
        if ((int) $row['blocked_until'] > 0 && (int) $row['blocked_until'] <= $now) {
            self::clear($ip);
            return false;
        }

        // Janela de tentativas expirada — reseta
        if ($now - (int) $row['last_attempt'] > self::ATTEMPT_WINDOW) {
            self::clear($ip);
            return false;
        }

        return (int) $row['attempts'] >= self::MAX_ATTEMPTS;
    }

    public static function recordFailure(string $ip): void
    {
        $hash = self::hash($ip);
        $now  = time();

        $row = Database::fetchOne(
            'SELECT attempts, last_attempt FROM auth_throttle WHERE ip_hash = ?',
            [$hash]
        );

        if (!$row) {
            Database::insert('auth_throttle', [
                'ip_hash'       => $hash,
                'attempts'      => 1,
                'blocked_until' => 0,
                'last_attempt'  => $now,
            ]);
            return;
        }

        // Janela expirada — reseta para 1 nova tentativa
        if ($now - (int) $row['last_attempt'] > self::ATTEMPT_WINDOW) {
            $newAttempts = 1;
            $blockedUntil = 0;
        } else {
            $newAttempts  = (int) $row['attempts'] + 1;
            $blockedUntil = $newAttempts >= self::MAX_ATTEMPTS ? $now + self::LOCKOUT_TIME : 0;
        }

        Database::update(
            'auth_throttle',
            [
                'attempts'      => $newAttempts,
                'blocked_until' => $blockedUntil,
                'last_attempt'  => $now,
            ],
            'ip_hash = ?',
            [$hash]
        );
    }

    public static function clear(string $ip): void
    {
        Database::query('DELETE FROM auth_throttle WHERE ip_hash = ?', [self::hash($ip)]);
    }

    /**
     * Quanto tempo (em minutos) até desbloqueio. Usado em mensagens de erro.
     */
    public static function lockoutMinutes(): int
    {
        return (int) ceil(self::LOCKOUT_TIME / 60);
    }

    /**
     * Limpa registros antigos (pode ser chamado por bin/cleanup ou cron).
     */
    public static function pruneOld(int $olderThanSeconds = 86400): int
    {
        $threshold = time() - $olderThanSeconds;
        $stmt = Database::query(
            'DELETE FROM auth_throttle WHERE last_attempt < ? AND blocked_until < ?',
            [$threshold, time()]
        );
        return $stmt->rowCount();
    }

    private static function hash(string $ip): string
    {
        // SHA1 — 40 chars, bate com o tipo CHAR(40) PRIMARY KEY do schema.
        return sha1($ip);
    }
}
