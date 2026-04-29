<?php
declare(strict_types=1);

namespace Carlesso;

/**
 * Kernel — bootstrap do CMS v2.0
 *
 * Responsabilidades:
 *  - Define constantes de path canônicas (BASE_PATH, SRC_PATH, STORAGE_PATH, etc.)
 *  - Carrega o autoloader do Composer (se presente) ou registra fallback PSR-4
 *  - Registra error/exception handlers conforme APP_ENV
 *  - Garante que diretórios de runtime (storage/cache, storage/throttle, ...) existam
 *
 * É chamado por public_html/includes/config.php logo após o load do .env,
 * de forma que admin pages legacy continuam funcionando sem alterações.
 */
final class Kernel
{
    private static bool $booted = false;

    public static function boot(): void
    {
        if (self::$booted) {
            return;
        }
        self::$booted = true;

        self::definePaths();
        self::loadAutoload();
        self::ensureRuntimeDirs();
        self::registerErrorHandlers();
    }

    /**
     * Define constantes de path com auto-detecção de layout.
     *
     * Suporta dois layouts intercambiáveis:
     *
     *   PARENT (preferido — mais seguro, requer hospedagem que permita
     *   arquivos acima de public_html/):
     *     /home/user/
     *       ├── src/
     *       ├── vendor/
     *       ├── bin/
     *       ├── config/
     *       └── public_html/
     *
     *   HOSTINGER / cPanel restrito (tudo dentro de public_html/, com
     *   .htaccess Deny em cada subdiretório de código):
     *     /home/user/public_html/
     *       ├── src/         ← .htaccess Deny
     *       ├── vendor/      ← bloqueado pelo .htaccess raiz (RedirectMatch 404)
     *       ├── bin/         ← .htaccess Deny
     *       ├── config/      ← .htaccess Deny (já vem do projeto original)
     *       ├── storage/     ← .htaccess Deny
     *       └── (resto público)
     *
     * Auto-detecta probando primeiro o layout PARENT; se não encontrar,
     * cai no layout HOSTINGER. .env continua tendo precedência absoluta
     * via SRC_PATH, VENDOR_PATH, BIN_PATH, STORAGE_PATH.
     */
    private static function definePaths(): void
    {
        // BASE_PATH já vem definido por includes/config.php
        if (!defined('BASE_PATH')) {
            define('BASE_PATH', dirname(__DIR__));
        }

        $public = defined('PUBLIC_PATH') ? PUBLIC_PATH : (BASE_PATH . '/public_html');

        if (!defined('SRC_PATH')) {
            define('SRC_PATH', self::resolvePath('SRC_PATH', 'src', $public, isDir: true));
        }
        if (!defined('VENDOR_PATH')) {
            define('VENDOR_PATH', self::resolvePath('VENDOR_PATH', 'vendor', $public, isDir: true, signal: 'autoload.php'));
        }
        if (!defined('BIN_PATH')) {
            define('BIN_PATH', self::resolvePath('BIN_PATH', 'bin', $public, isDir: true));
        }
        if (!defined('STORAGE_PATH')) {
            // STORAGE prefere SEMPRE public_html/ (precisa ser writable; em Hostinger
            // o home dir não é). Mantém .htaccess Deny dentro.
            $storage = self::env('STORAGE_PATH', $public . '/storage');
            define('STORAGE_PATH', $storage);
        }
    }

    /**
     * Resolve um path tentando: .env → BASE_PATH/{rel} → PUBLIC_PATH/{rel}.
     * Se nada existe ainda, retorna o candidato preferido (BASE_PATH) para
     * que o usuário veja erro com path claro.
     *
     * @param string $envKey  Chave .env que sobrescreve tudo se setada
     * @param string $rel     Nome relativo do diretório (e.g. "src")
     * @param string $public  PUBLIC_PATH resolvido
     * @param bool   $isDir   Se true, exige que seja diretório (não arquivo)
     * @param string $signal  Arquivo dentro do candidato que sinaliza presença
     *                        (e.g. "autoload.php" para vendor/). Opcional.
     */
    private static function resolvePath(
        string $envKey,
        string $rel,
        string $public,
        bool $isDir = true,
        string $signal = ''
    ): string {
        $explicit = self::env($envKey);
        if ($explicit) {
            return rtrim($explicit, '/\\');
        }

        $candidates = [
            BASE_PATH . '/' . $rel,    // layout PARENT (preferido)
            $public . '/' . $rel,      // layout HOSTINGER
        ];

        foreach ($candidates as $c) {
            if ($signal !== '') {
                if (is_file($c . '/' . $signal)) {
                    return $c;
                }
            } elseif ($isDir && is_dir($c)) {
                return $c;
            } elseif (!$isDir && is_file($c)) {
                return $c;
            }
        }

        // Nenhum existe ainda — retorna o preferido (PARENT)
        return $candidates[0];
    }

    /**
     * Carrega o autoloader do Composer se disponível.
     * Caso contrário, registra um autoloader PSR-4 mínimo para Carlesso\* —
     * suficiente para o sistema rodar antes de `composer install` ter sido executado.
     */
    private static function loadAutoload(): void
    {
        $autoload = VENDOR_PATH . '/autoload.php';
        if (is_file($autoload)) {
            require_once $autoload;
            return;
        }

        // Fallback PSR-4 para Carlesso\
        spl_autoload_register(static function (string $class): void {
            $prefix = 'Carlesso\\';
            if (!str_starts_with($class, $prefix)) {
                return;
            }
            $relative = substr($class, strlen($prefix));
            $file     = SRC_PATH . '/' . str_replace('\\', '/', $relative) . '.php';
            if (is_file($file)) {
                require_once $file;
            }
        });
    }

    private static function ensureRuntimeDirs(): void
    {
        $dirs = [
            STORAGE_PATH,
            STORAGE_PATH . '/cache',
            STORAGE_PATH . '/cache/htmlpurifier',
            STORAGE_PATH . '/throttle',
            STORAGE_PATH . '/revisions',
            STORAGE_PATH . '/backups',
        ];
        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }
        }
    }

    private static function registerErrorHandlers(): void
    {
        $env = self::env('APP_ENV', 'production');
        if ($env === 'production') {
            ini_set('display_errors', '0');
            ini_set('log_errors', '1');
            error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);
        } else {
            ini_set('display_errors', '1');
            error_reporting(E_ALL);
        }

        // Sessão: estende lifetime para evitar "sessão expirada" em formulários
        // que ficam abertos por tempo (form de contato, edição de produção, etc.)
        // SESSION_LIFETIME do .env (default 7200 = 2h).
        $lifetime = (int) self::env('SESSION_LIFETIME', 7200);
        if ($lifetime > 0 && session_status() === PHP_SESSION_NONE) {
            ini_set('session.gc_maxlifetime', (string) $lifetime);
            ini_set('session.cookie_lifetime', (string) $lifetime);
            // Cookie 'lax' funciona em forms first-party (incluindo AJAX same-origin)
            // sem quebrar redirects pós-OAuth no admin.
            ini_set('session.cookie_samesite', 'Lax');
            ini_set('session.cookie_httponly', '1');
            if (!empty($_SERVER['HTTPS'])) {
                ini_set('session.cookie_secure', '1');
            }
        }
    }

    private static function env(string $key, mixed $default = null): mixed
    {
        if (function_exists('env')) {
            return env($key, $default);
        }
        return $_ENV[$key] ?? getenv($key) ?: $default;
    }
}
