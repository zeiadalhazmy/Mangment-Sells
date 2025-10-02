<?php
require_once __DIR__ . '/lib/auth.php';
send_security_headers();
$me = auth_current_admin();
if ($me):
?>
  <nav class="admin-nav">
    <span>مرحبًا، <?= htmlspecialchars($me['username']) ?></span>
    <a href="catalog.php">المتجر</a>
    <a href="cart.php">عربة التسوّق</a>
    <a href="/change_password.php">تغيير كلمة المرور</a>
    <a href="/logout.php">خروج</a>
  </nav>
<?php endif; ?>