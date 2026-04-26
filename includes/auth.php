<?php
/**
 * auth.php — Shim de compatibilidade.
 *
 * A AuthService real vive em src/Services/Auth/AuthService.php
 * (namespace Carlesso\Services\Auth). Este arquivo apenas garante que o
 * Kernel está bootado e cria um alias global `Auth` apontando para ela.
 *
 * API legacy preservada: Auth::check(), Auth::login(), Auth::logout(),
 * Auth::requireLogin(), Auth::requireRole($role), Auth::isAdmin(), Auth::user().
 *
 * Adições rev 1.2 (compatíveis):
 *   Auth::can($permKey)       — checagem granular contra role_permissions
 *   Auth::requireCan($permKey)— atalho que dispara 403
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

if (!class_exists('Auth', false)) {
    class_exists(\Carlesso\Services\Auth\AuthService::class, true);
    class_alias(\Carlesso\Services\Auth\AuthService::class, 'Auth');
}
