<?php
declare(strict_types=1);

namespace Carlesso\Services\Auth;

use Carlesso\Support\Database;

/**
 * Permissions — capabilities granulares por role.
 *
 * Lê role_permissions com cache estático por request.
 * Admin sempre tem TODAS as permissões (short-circuit, sem query).
 *
 * Uso típico:
 *   if (Permissions::roleCan('editor', 'pages.publish')) { ... }
 *   $caps = Permissions::forRole('author');
 *
 * O wrapper amigável fica em AuthService::can() que já considera
 * o usuário logado da sessão.
 */
final class Permissions
{
    /** Cache estático por request — chave: role, valor: array de perm_keys */
    private static array $cache = [];

    /** Lista mestra das 11 capabilities — fonte da verdade para fallback */
    public const ALL_PERMISSIONS = [
        'pages.edit',
        'pages.publish',
        'pages.delete',
        'posts.edit',
        'posts.publish',
        'posts.delete',
        'media.upload',
        'media.delete',
        'users.manage',
        'settings.manage',
        'appearance.manage',
    ];

    /**
     * Verifica se uma role específica tem uma capability.
     * Admin sempre retorna true sem hitting DB.
     */
    public static function roleCan(string $role, string $permKey): bool
    {
        if ($role === 'admin') {
            return true;
        }
        return in_array($permKey, self::forRole($role), true);
    }

    /**
     * Retorna todas as perm_keys atribuídas a uma role.
     * Cache estático por request.
     */
    public static function forRole(string $role): array
    {
        if (isset(self::$cache[$role])) {
            return self::$cache[$role];
        }

        if ($role === 'admin') {
            return self::$cache[$role] = self::ALL_PERMISSIONS;
        }

        try {
            $rows = Database::fetchAll(
                'SELECT perm_key FROM role_permissions WHERE role = ?',
                [$role]
            );
            self::$cache[$role] = array_column($rows, 'perm_key');
        } catch (\Throwable) {
            // Se a tabela não existe ainda (update-v1.3.sql não rodou),
            // cai no mapping default em código para não quebrar admin.
            self::$cache[$role] = self::defaultMapping($role);
        }

        return self::$cache[$role];
    }

    /**
     * Mapeamento default — espelha exatamente o seed do update-v1.3.sql.
     * Usado como fallback quando role_permissions não existe ou está vazio.
     */
    public static function defaultMapping(string $role): array
    {
        return match ($role) {
            'admin'  => self::ALL_PERMISSIONS,
            'editor' => array_values(array_diff(self::ALL_PERMISSIONS, ['users.manage', 'settings.manage'])),
            'author' => ['pages.edit', 'posts.edit', 'media.upload'],
            default  => [],
        };
    }

    /**
     * Limpa o cache (útil em testes ou após mudanças em role_permissions).
     */
    public static function flushCache(): void
    {
        self::$cache = [];
    }
}
