<?php
/**
 * config.php — Bootstrap base do CMS Carlesso.
 *
 * Carrega .env, define paths canônicos e dispara Carlesso\Kernel::boot()
 * que registra autoloader (Composer ou fallback PSR-4) + handlers de erro.
 *
 * Compatibilidade: assinatura legacy mantida — admin pages e index.php
 * continuam fazendo `require_once __DIR__ . '/../includes/config.php';`
 * exatamente como antes. As funções globais env() e loadEnv() seguem
 * disponíveis.
 */

defined('BASE_PATH')   || define('BASE_PATH',   dirname(dirname(__DIR__)));
defined('PUBLIC_PATH') || define('PUBLIC_PATH', __DIR__ . '/..');

// Auto-detecção de CONFIG_PATH para suportar dois layouts:
//   PARENT (preferido):  BASE_PATH/config/.env  (acima do public_html/)
//   HOSTINGER (cPanel restrito): PUBLIC_PATH/config/.env  (dentro de public_html/)
// .env explícito sobrescreve via constante CONFIG_PATH antes deste include.
if (!defined('CONFIG_PATH')) {
    $configCandidates = [
        BASE_PATH   . '/config',
        PUBLIC_PATH . '/config',
    ];
    $resolvedConfigPath = $configCandidates[0]; // default = PARENT
    foreach ($configCandidates as $candidate) {
        if (is_file($candidate . '/.env')) {
            $resolvedConfigPath = $candidate;
            break;
        }
    }
    define('CONFIG_PATH', $resolvedConfigPath);
}

if (!function_exists('loadEnv')) {
    function loadEnv(string $path): void
    {
        if (!file_exists($path)) {
            die('ERRO CRÍTICO: Arquivo .env não encontrado em: ' . $path);
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if (str_starts_with($line, '#') || !str_contains($line, '=')) {
                continue;
            }
            [$key, $value] = explode('=', $line, 2);
            $key   = trim($key);
            $value = trim(trim($value), '"\'');
            if (!array_key_exists($key, $_ENV)) {
                $_ENV[$key]    = $value;
                $_SERVER[$key] = $value;
                putenv("$key=$value");
            }
        }
    }
}

loadEnv(CONFIG_PATH . '/.env');

if (!function_exists('env')) {
    function env(string $key, mixed $default = null): mixed
    {
        return $_ENV[$key] ?? getenv($key) ?: $default;
    }
}

// ── Bootstrap Carlesso\Kernel (refactor v2.0) ─────────────────────────────
// Carrega o Kernel direto (não depende de Composer ainda), e o Kernel
// decide se usa vendor/autoload.php ou o fallback PSR-4 interno.
//
// Auto-detecta o layout (PARENT vs HOSTINGER) probando ambos candidatos.
$kernelCandidates = [
    BASE_PATH   . '/src/Kernel.php',  // layout PARENT (preferido)
    PUBLIC_PATH . '/src/Kernel.php',  // layout HOSTINGER
];
foreach ($kernelCandidates as $kernelFile) {
    if (is_file($kernelFile)) {
        require_once $kernelFile;
        \Carlesso\Kernel::boot();
        break;
    }
}
