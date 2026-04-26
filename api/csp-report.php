<?php
/**
 * api/csp-report.php — Recebe relatórios de violação CSP.
 *
 * O .htaccess raiz envia Content-Security-Policy-Report-Only com
 * report-uri /api/csp-report.php. Cada navegador que detecta uma
 * violação posta um JSON aqui.
 *
 * Logamos em storage/cache/csp-reports.log com rotação simples (>5MB).
 *
 * Phase 4 vai surfaceer essas violações no admin para auxiliar a
 * transição de Report-Only → enforced.
 */

define('CARLESSO_CMS', true);
define('BASE_PATH', dirname(dirname(__DIR__)));

require_once __DIR__ . '/../includes/config.php';

if (!defined('STORAGE_PATH')) {
    http_response_code(204);
    exit;
}

$logFile = STORAGE_PATH . '/cache/csp-reports.log';
$maxSize = 5 * 1024 * 1024; // 5MB

// Rotação simples
if (is_file($logFile) && filesize($logFile) > $maxSize) {
    @rename($logFile, $logFile . '.1');
}

$payload = file_get_contents('php://input');
if ($payload === false || $payload === '') {
    http_response_code(204);
    exit;
}

// Tamanho razoável — proteção contra abuso
if (strlen($payload) > 16384) {
    $payload = substr($payload, 0, 16384) . '...[truncado]';
}

$line = sprintf(
    "[%s] %s %s %s\n",
    date('c'),
    $_SERVER['REMOTE_ADDR'] ?? '?',
    substr($_SERVER['HTTP_USER_AGENT'] ?? '?', 0, 200),
    $payload
);

@file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);

http_response_code(204);
