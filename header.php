<?php
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/logger.php';

log_info('checkout_started', ['cart_total' => $total]);
send_security_headers();
$me = auth_current_admin();
if ($me):
?>

  <?php
  require_once __DIR__ . '/lib/auth_customer.php';
  $c = current_customer($pdo);
  ?>
  <nav>
    <!-- روابط أخرى -->
    <?php if ($c): ?>
      <a href="/my_orders.php">طلباتي</a>
      <a href="/customer_logout.php">تسجيل الخروج</a>
    <?php else: ?>
      <a href="/customer_login.php">تسجيل الدخول</a>
      <a href="/customer_register.php">إنشاء حساب</a>
    <?php endif; ?>
  </nav>


  <nav class="admin-nav">
    <span>مرحبًا، <?= htmlspecialchars($me['username']) ?></span>
    <a href="catalog.php">المتجر</a>
    <a href="cart.php">عربة التسوّق</a>
    <a href="/change_password.php">تغيير كلمة المرور</a>
    <a href="/logout.php">خروج</a>
  </nav>
<?php endif; ?>