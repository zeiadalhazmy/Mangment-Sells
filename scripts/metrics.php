<?php
require_once __DIR__ . '/../lib/logger.php';
header('Content-Type: text/plain; charset=utf-8');

/* مثال بسيط: عدد الطلبات اليومية من جدول orders (إن وُجد) */
$dbPath = __DIR__ . '/../storage/sqlite/store.sqlite';
$ordersToday = 'n/a';
try {
    if (file_exists($dbPath)) {
        $pdo = new PDO('sqlite:' . $dbPath, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        $ordersToday = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE date(created_at) = date('now','localtime')")->fetchColumn();
    }
} catch (Throwable $e) {
    log_error('metrics_db_error', ['error' => $e->getMessage()]);
}

echo "# basic metrics\n";
echo "uptime_seconds " . (int) (time() - $_SERVER['REQUEST_TIME']) . "\n";
echo "orders_today " . $ordersToday . "\n";
