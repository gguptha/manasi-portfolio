<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

date_default_timezone_set('Asia/Kolkata');

define('ROOT_PATH', dirname(__DIR__));
define('INCLUDES_PATH', __DIR__);
define('UPLOAD_PATH', ROOT_PATH . DIRECTORY_SEPARATOR . 'uploads');

require INCLUDES_PATH . DIRECTORY_SEPARATOR . 'functions.php';
require INCLUDES_PATH . DIRECTORY_SEPARATOR . 'auth.php';

$configFile = INCLUDES_PATH . DIRECTORY_SEPARATOR . 'config.php';
$installLock = ROOT_PATH . DIRECTORY_SEPARATOR . 'install.lock';

$scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
$isInstallScript = substr($scriptName, -12) === '/install.php' || substr($scriptName, -11) === 'install.php';

if (!is_file($configFile)) {
    if ($isInstallScript) {
        return;
    }
    if (is_file(ROOT_PATH . DIRECTORY_SEPARATOR . 'install.php') && !is_file($installLock)) {
        header('Location: ' . base_url('install.php'));
        exit;
    }
    http_response_code(500);
    echo 'Missing includes/config.php. Copy includes/config.example.php and edit your database settings, or run install.php.';
    exit;
}

require $configFile;
require INCLUDES_PATH . DIRECTORY_SEPARATOR . 'db.php';

if (!defined('BASE_URL')) {
    define('BASE_URL', 'auto');
}
