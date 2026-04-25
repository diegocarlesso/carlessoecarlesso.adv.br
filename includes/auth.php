<?php
require_once __DIR__ . '/db.php';

class Auth
{
    private static int $maxAttempts = 5;
    private static int $lockoutTime = 300; // 5 minutos

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

        // Rate limit
        if (self::isRateLimited($ip)) {
            return ['success' => false, 'message' => 'Muitas tentativas. Aguarde ' . self::$lockoutTime / 60 . ' minutos.'];
        }

        $user = Database::fetchOne(
            'SELECT * FROM usuarios WHERE username = ? OR email = ? LIMIT 1',
            [$username, $username]
        );

        if (!$user || !password_verify($password, $user['password'])) {
            self::recordFailedAttempt($ip);
            return ['success' => false, 'message' => 'Usuário ou senha incorretos.'];
        }

        // Login bem-sucedido
        self::clearFailedAttempts($ip);
        self::regenerateSession();

        $_SESSION['user_id']   = $user['id'];
        $_SESSION['username']  = $user['username'];
        $_SESSION['role']      = $user['role'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['login_at']  = time();
        $_SESSION['ip']        = $ip;

        // Atualiza last_login
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
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
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
        $lifetime = (int) env('SESSION_LIFETIME', 7200);
        if (time() - $_SESSION['login_at'] > $lifetime) {
            self::logout();
            return false;
        }

        // IP binding (proteção session hijacking)
        if ($_SESSION['ip'] !== self::getClientIP()) {
            self::logout();
            return false;
        }

        // Atualiza timestamp
        $_SESSION['login_at'] = time();
        return true;
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            header('Location: /admin/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
            exit;
        }
    }

    public static function requireRole(string $role): void
    {
        self::requireLogin();
        $roles = ['author' => 1, 'editor' => 2, 'admin' => 3];
        $userLevel = $roles[$_SESSION['role']] ?? 0;
        $reqLevel  = $roles[$role] ?? 99;
        if ($userLevel < $reqLevel) {
            http_response_code(403);
            die('Acesso negado.');
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
        return $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    private static function isRateLimited(string $ip): bool
    {
        $key       = 'rl_' . md5($ip);
        $attempts  = (int) ($_SESSION[$key . '_count'] ?? 0);
        $lastTime  = (int) ($_SESSION[$key . '_time'] ?? 0);

        // Reset janela expirada
        if (time() - $lastTime > self::$lockoutTime) {
            $_SESSION[$key . '_count'] = 0;
            return false;
        }

        return $attempts >= self::$maxAttempts;
    }

    private static function recordFailedAttempt(string $ip): void
    {
        $key = 'rl_' . md5($ip);
        $_SESSION[$key . '_count'] = ($_SESSION[$key . '_count'] ?? 0) + 1;
        $_SESSION[$key . '_time']  = time();
    }

    private static function clearFailedAttempts(string $ip): void
    {
        $key = 'rl_' . md5($ip);
        unset($_SESSION[$key . '_count'], $_SESSION[$key . '_time']);
    }

    private static function regenerateSession(): void
    {
        session_regenerate_id(true);
    }
}
