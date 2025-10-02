<?php
require_once DIR . '/lib/auth.php';
$me = auth_current_admin();
if ($me):
?>
  <nav class="admin-nav">
    <span>مرحبًا، <?= htmlspecialchars($me['username']) ?></span>
    <a href="/change_password.php">تغيير كلمة المرور</a>
    <a href="/logout.php">خروج</a>
  </nav>
<?php endif; ?>
