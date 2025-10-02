<?php
declare(strict_types=1);

use Dotenv\Dotenv;

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

require_once BASE_PATH . '/vendor/autoload.php';

$envFile = BASE_PATH . '/.env';
if (file_exists($envFile)) {
    Dotenv::createImmutable(BASE_PATH)->load();
}

if (!defined('APP_ENV')) {
    define('APP_ENV', $_ENV['APP_ENV'] ?? 'production');
}
if (!defined('APP_DEBUG')) {
    define('APP_DEBUG', filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOL));
}

if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
    ini_set('display_errors', '0');
}

/** URL الأساس */
if (!defined('APP_URL')) {
    define('APP_URL', rtrim($_ENV['APP_URL'] ?? '', '/'));
}

/** مسار قاعدة البيانات */
if (!defined('DB_PATH')) {
    define('DB_PATH', BASE_PATH . '/' . ($_ENV['DB_PATH'] ?? 'storage/store.sqlite'));
}

/** مفتاح التطبيق */
if (!defined('APP_KEY')) {
    define('APP_KEY', $_ENV['APP_KEY'] ?? 'change-me');
}

/** ابدأ الجلسة مبكراً */
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name('store_sess');
    session_start([
        'cookie_httponly' => true,
        'cookie_secure'   => isset($_SERVER['HTTPS']),
        'cookie_samesite' => 'Lax',
    ]);
}
