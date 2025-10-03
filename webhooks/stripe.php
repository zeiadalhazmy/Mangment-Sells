<?php
// endpoint: /webhooks/stripe.php
require_once __DIR__.'/../lib/payments.php'; // فيه $pdo و stripeWebhookSecret()
$payload = @file_get_contents('php://input');
$sig     = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

$secret  = stripeWebhookSecret(); // whsec_xxx

try {
  $event = \Stripe\Webhook::constructEvent($payload, $sig, $secret);
} catch(\UnexpectedValueException $e) {
  http_response_code(400); echo 'Invalid payload'; exit;
} catch(\Stripe\Exception\SignatureVerificationException $e) {
  http_response_code(400); echo 'Invalid signature'; exit;
}

switch ($event->type) {
  case 'checkout.session.completed':
    // تم الدفع أو أُنشئ Session (قد تكون payment_status = paid)
    $session = $event->data->object; // \Stripe\Checkout\Session
    $orderId = (int)($session->metadata->order_id ?? 0);
    if ($orderId>0) {
      // لو مدفوع، عدّل حالة الطلب
      $status = ($session->payment_status === 'paid') ? 'paid' : 'placed';
      $stmt = $pdo->prepare("UPDATE orders SET status=?, payment_status=?, payment_ref=? WHERE id=?");
      $stmt->execute([$status, $session->payment_status, $session->id, $orderId]);
    }
    break;

  case 'payment_intent.succeeded':
    // في بعض السيناريوهات
    $intent = $event->data->object;
    // يمكن البحث عن order عبر metadata إن كانت مضبوطة هنا أيضاً
    break;

  default:
    // أنواع أخرى إن أردت
    break;
}

http_response_code(200);
echo 'OK';
