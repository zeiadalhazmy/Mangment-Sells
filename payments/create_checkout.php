<?php
require_once __DIR__.'/../lib/payments.php';

$orderId = (int)($_GET['order_id'] ?? 0);
if ($orderId <= 0) {
  http_response_code(400);
  echo "order_id required";
  exit;
}

try {
  $session = createStripeCheckoutForOrder($pdo, $orderId);
  header("Location: ".$session['url']);
  exit;
} catch (Throwable $e) {
  http_response_code(500);
  echo "Error: ".$e->getMessage();
}
