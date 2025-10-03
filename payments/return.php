<?php
require_once __DIR__.'/../db.php';
require_once __DIR__.'/../vendor/autoload.php';
require_once __DIR__.'/../lib/payments.php';

$orderId   = (int)($_GET['order_id'] ?? 0);
$sessionId = $_GET['session_id'] ?? '';

if ($orderId <= 0 || !$sessionId) {
  header('Location: /admin_orders.php');
  exit;
}

// نقرأ Session من Stripe لعرض رسالة لائقة
$stripe = new \Stripe\StripeClient(getenv('STRIPE_SECRET_KEY') ?: 'sk_test_xxx');
$session = $stripe->checkout->sessions->retrieve($sessionId, []);

$statusText = 'قيد المعالجة';
if ($session && $session->payment_status === 'paid') {
  $statusText = 'تم الدفع بنجاح';
}

// لا نحدّث الحالة هنا (المرجّح التحديث عبر الويب هوك). فقط نعرض رسالة.
?>
<?php include __DIR__.'/../header.php'; ?>
<div class="container">
  <h2>طلب #<?=$orderId?></h2>
  <p>حالة الدفع: <strong><?=$statusText?></strong></p>
  <p><a href="/order_view.php?id=<?=$orderId?>">عرض تفاصيل الطلب</a></p>
</div>
<?php include __DIR__.'/../footer.php'; ?>
