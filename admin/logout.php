<?php
define('CARLESSO_CMS', true);
define('BASE_PATH', dirname(dirname(__DIR__)));
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
Auth::logout();
header('Location: /admin/login.php');
exit;
