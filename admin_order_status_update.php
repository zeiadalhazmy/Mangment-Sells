<?php
require_once __DIR__.'/admin_guard.php';
require_once __DIR__.'/db.php';
require_once __DIR__.'/lib/order_status.php';

$orderId = (int)($_POST['order_id'] ?? 0);
$to      = $_POST['to']      ?? '';
$note    = trim($_POST['note'] ?? '');

if (!$orderId || !$to) {
  http_response_code(400);
  exit('Bad request');
}

// اسم الأدمن من السيشن
session_start();
$actor = $_SESSION['admin_name'] ?? 'admin';

try {
  if (updateOrderStatus($pdo, $orderId, $to, $note, $actor)) {
    header("Location: /admin_orders.php?ok=1");
    exit;
  } else {
    header("Location: /admin_orders.php?err=not-updated");
    exit;
  }
} catch (Throwable $e) {
  header("Location: /admin_orders.php?err=".urlencode($e->getMessage()));
  exit;
}
