<?php
/**
 * Carrega variáveis de ambiente do arquivo .env
 * O arquivo .env deve estar FORA do public_html
 */

defined('BASE_PATH') || define('BASE_PATH', dirname(dirname(__DIR__)));
defined('CONFIG_PATH') || define('CONFIG_PATH', BASE_PATH . '/config');
defined('PUBLIC_PATH') || define('PUBLIC_PATH', __DIR__ . '/..');

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
        $value = trim($value);
        // Remove aspas se presentes
        $value = trim($value, '"\'');
        if (!array_key_exists($key, $_ENV)) {
            $_ENV[$key]    = $value;
            $_SERVER[$key] = $value;
            putenv("$key=$value");
        }
    }
}

loadEnv(CONFIG_PATH . '/.env');

function env(string $key, mixed $default = null): mixed
{
    return $_ENV[$key] ?? getenv($key) ?: $default;
}
