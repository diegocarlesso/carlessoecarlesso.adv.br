<?php
/**
 * api/csrf-token.php — Devolve um token CSRF fresco em JSON.
 *
 * Usado por formulários públicos (ex: contato) que ficam abertos muito tempo:
 * o JS busca um token novo logo antes de submit, garantindo que mesmo
 * sessões expiradas/recriadas funcionem na primeira tentativa.
 *
 * Sem proteção CSRF aqui (paradoxo) — apenas devolve um token. Não causa
 * mutação de estado. Rate-limit suave por IP via Throttle (nenhum
 * endpoint é totalmente livre).
 */
define('CARLESSO_CMS', true);
define('BASE_PATH', dirname(dirname(__DIR__)));

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/csrf.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

// Stateless HMAC (válido 2h) se disponível; senão cai pro session token.
$token = method_exists('CSRF', 'generateStateless')
    ? CSRF::generateStateless()
    : CSRF::generate();

echo json_encode(['token' => $token]);
