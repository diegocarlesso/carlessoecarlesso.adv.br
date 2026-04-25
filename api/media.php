<?php
/**
 * api/media.php
 * Lista mídia para o picker de blocos
 */
define('CARLESSO_CMS', true);
define('BASE_PATH', dirname(dirname(__DIR__)));
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

Auth::start();
if (!Auth::check()) {
    jsonResponse(['error' => 'Não autorizado.'], 401);
}

$action = $_GET['action'] ?? 'list';

if ($action === 'list') {
    $media = Database::fetchAll(
        "SELECT id, filename, original_name, file_path, file_type, file_size, created_at
         FROM media WHERE file_type LIKE 'image/%' ORDER BY created_at DESC LIMIT 100"
    );
    jsonResponse(['success' => true, 'media' => $media]);
}

jsonResponse(['error' => 'Ação não reconhecida.'], 400);
