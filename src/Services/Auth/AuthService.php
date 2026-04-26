<?php
declare(strict_types=1);

namespace Carlesso\Services\Auth;

use Carlesso\Support\Database;
use Carlesso\Support\Env;

/**
 * AuthService — autenticação, sessão, permissões.
 *
 * Migrado de public_html/includes/auth.php preservando 100% da API estática
 * usada pelos admin pages legacy. Adições rev 1.2:
 *  - rate limiting real via Throttle (substitui o counter de sessão quebrado)
 *  - Auth::can('perm.key') usando Permissions service
 *  - requireRole() vira shim compatível em torno de can()
 *
 * O alias `Auth` é declarado em public_html/includes/auth.php (shim),
 * de modo que código antigo `Auth::check()` continua funcionando.
 */
final class AuthService
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_set_cookie_params([
                'lifetime' => 0,
                'path'     => '/',
                'secure'   => isset($_SERVER['HTTPS']),
                'httponly' => true,
                'samesite' => 'Strict',
            ]);
            session_start();
        }
    }

    public static function login(string $username, string $password): array
    {
        self::start();

        $ip = self::getClientIP();

        if (Throttle::isBlocked($ip)) {
            return [
                'success' => false,
                'message' => 'Muitas tentativas. Aguarde ' . Throttle::lockoutMinutes() . ' minutos.',
            ];
        }

        $user = Database::fetchOne(
            'SELECT * FROM usuarios WHERE username = ? OR email = ? LIMIT 1',
            [$username, $username]
        );

        if (!$user || !password_verify($password, $user['password'])) {
            Throttle::recordFailure($ip);
            return ['success' => false, 'message' => 'Usuário ou senha incorretos.'];
        }

        // Conta desativada (campo enabled, adicionado em update-v1.5.sql)
        if (array_key_exists('enabled', $user) && (int) $user['enabled'] === 0) {
            // Não conta como tentativa falha — credencial está correta
            return ['success' => false, 'message' => 'Esta conta está desativada. Contate o administrador.'];
        }

        // Login bem-sucedido
        Throttle::clear($ip);
        self::regenerateSession();

        $_SESSION['user_id']   = $user['id'];
        $_SESSION['username']  = $user['username'];
        $_SESSION['role']      = $user['role'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['login_at']  = time();
        $_SESSION['ip']        = $ip;
        $_SESSION['must_change_password'] = (int) ($user['must_change_password'] ?? 0);

        Database::query(
            'UPDATE usuarios SET last_login = NOW() WHERE id = ?',
            [$user['id']]
        );

        return ['success' => true, 'role' => $user['role']];
    }

    public static function logout(): void
    {
        self::start();
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'] ?? '',
                $params['secure'],
                $params['httponly']
            );
        }
        session_destroy();
    }

    public static function check(): bool
    {
        self::start();

        if (empty($_SESSION['user_id'])) {
            return false;
        }

        // Session timeout
        $lifetime = Env::getInt('SESSION_LIFETIME', 7200);
        if (time() - (int) ($_SESSION['login_at'] ?? 0) > $lifetime) {
            self::logout();
            return false;
        }

        // IP binding com prefixo /24 (não IP exato — evita logout em CGNAT/4G).
        // O comportamento estrito antigo é mantido apenas se SESSION_IP_STRICT=1 no .env.
        $strict = Env::getBool('SESSION_IP_STRICT', false);
        $bound  = (string) ($_SESSION['ip'] ?? '');
        $now    = self::getClientIP();
        if ($bound !== '' && !self::ipMatches($bound, $now, $strict)) {
            self::logout();
            return false;
        }

        $_SESSION['login_at'] = time();
        return true;
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            header('Location: /admin/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI'] ?? '/admin/'));
            exit;
        }

        // Força troca de senha se o admin marcou (rev v1.5).
        // Ignora as próprias páginas perfil.php e logout.php para não loopar.
        $self = basename($_SERVER['PHP_SELF'] ?? '');
        if (
            !empty($_SESSION['must_change_password'])
            && !in_array($self, ['perfil.php', 'logout.php'], true)
        ) {
            header('Location: /admin/perfil.php?reset=required');
            exit;
        }
    }

    /**
     * Compat shim: requireRole($role) continua funcionando.
     *
     * O comportamento antigo era hierárquico (author < editor < admin).
     * Mantemos esse contrato para os admin pages que chamam
     * `Auth::requireRole('editor')` etc.
     *
     * Para checagens granulares, use Auth::can('pages.publish').
     */
    public static function requireRole(string $role): void
    {
        self::requireLogin();
        $hierarchy = ['author' => 1, 'editor' => 2, 'admin' => 3];
        $userLevel = $hierarchy[$_SESSION['role'] ?? ''] ?? 0;
        $reqLevel  = $hierarchy[$role] ?? 99;
        if ($userLevel < $reqLevel) {
            http_response_code(403);
            die('Acesso negado.');
        }
    }

    /**
     * NOVO (rev 1.2): checagem granular de capability.
     * Retorna false para usuários não logados.
     */
    public static function can(string $permKey): bool
    {
        self::start();
        $role = (string) ($_SESSION['role'] ?? '');
        if ($role === '') {
            return false;
        }
        return Permissions::roleCan($role, $permKey);
    }

    /**
     * Atalho: dispara 403 se o usuário não tem a capability.
     */
    public static function requireCan(string $permKey): void
    {
        self::requireLogin();
        if (!self::can($permKey)) {
            http_response_code(403);
            die('Acesso negado: permissão "' . htmlspecialchars($permKey) . '" necessária.');
        }
    }

    public static function isAdmin(): bool
    {
        return ($_SESSION['role'] ?? '') === 'admin';
    }

    public static function user(): array
    {
        return [
            'id'        => $_SESSION['user_id'] ?? null,
            'username'  => $_SESSION['username'] ?? '',
            'role'      => $_SESSION['role'] ?? '',
            'full_name' => $_SESSION['full_name'] ?? '',
        ];
    }

    private static function getClientIP(): string
    {
        // Honra X-Forwarded-For apenas se TRUSTED_PROXY estiver setado.
        $trusted = Env::get('TRUSTED_PROXY', '');
        if ($trusted && ($_SERVER['REMOTE_ADDR'] ?? '') === $trusted) {
            $xff = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '';
            if ($xff !== '') {
                return trim(explode(',', $xff)[0]);
            }
        }
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    /**
     * IP binding tolerante:
     *  - strict=true → equality
     *  - strict=false → mesmo /24 IPv4 ou mesmo /64 IPv6
     */
    private static function ipMatches(string $a, string $b, bool $strict): bool
    {
        if ($strict) {
            return $a === $b;
        }
        if (filter_var($a, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) && filter_var($b, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return self::cidrMatch($a, $b, 24);
        }
        if (filter_var($a, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) && filter_var($b, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return self::cidrMatch($a, $b, 64);
        }
        return $a === $b;
    }

    private static function cidrMatch(string $a, string $b, int $bits): bool
    {
        $binA = inet_pton($a);
        $binB = inet_pton($b);
        if ($binA === false || $binB === false) {
            return false;
        }
        $bytes = intdiv($bits, 8);
        $rest  = $bits % 8;
        if (substr($binA, 0, $bytes) !== substr($binB, 0, $bytes)) {
            return false;
        }
        if ($rest === 0) {
            return true;
        }
        $mask = chr(0xFF << (8 - $rest) & 0xFF);
        return ($binA[$bytes] & $mask) === ($binB[$bytes] & $mask);
    }

    private static function regenerateSession(): void
    {
        session_regenerate_id(true);
    }
}
