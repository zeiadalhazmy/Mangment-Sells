<?php
http_response_code(200);
header('Content-Type: application/json; charset=utf-8');

$ok = true;
$checks = [];

/* فحص كتابة الملفات */
$writable = is_writable(__DIR__ . '/../storage/logs');
$checks['fs_writable'] = $writable;
$ok = $ok && $writable;

/* فحص SQLite (وجود الملف أو القدرة على إنشائه) */
$dbFile = __DIR__ . '/../storage/sqlite/store.sqlite';
$canTouch = is_dir(dirname($dbFile)) && (file_exists($dbFile) || is_writable(dirname($dbFile)));
$checks['sqlite_ready'] = $canTouch;
$ok = $ok && $canTouch;

echo json_encode([
    'status' => $ok ? 'OK' : 'DEGRADED',
    'ts'     => date('c'),
    'checks' => $checks
], JSON_UNESCAPED_UNICODE);
