<?php
/**
 * api/upload.php
 * Endpoint de upload para TinyMCE e biblioteca de mídia
 */
define('CARLESSO_CMS', true);
define('BASE_PATH', dirname(dirname(__DIR__)));
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

// Apenas POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Método não permitido.'], 405);
}

// Auth — TinyMCE e painel requerem login
Auth::start();
if (!Auth::check()) {
    jsonResponse(['error' => 'Não autorizado.'], 401);
}

// CSRF — aceita token via header ou POST
$token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['_csrf'] ?? null;
if (!CSRF::validate($token)) {
    jsonResponse(['error' => 'Token CSRF inválido.'], 403);
}

// Arquivo
$file = $_FILES['file'] ?? $_FILES['upload'] ?? null; // TinyMCE usa 'file'
if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
    jsonResponse(['success' => false, 'message' => 'Nenhum arquivo recebido ou erro no upload.'], 400);
}

$result = handleUpload($file);

if (!$result['success']) {
    jsonResponse(['success' => false, 'message' => $result['message']], 422);
}

// Formato esperado pelo TinyMCE 6
jsonResponse([
    'success'  => true,
    'location' => $result['url'],   // TinyMCE usa 'location'
    'url'      => $result['url'],
    'filename' => $result['filename'],
    'id'       => $result['id'],
]);
