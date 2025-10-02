<?php
declare(strict_types=1);
require_once DIR . '/lib/auth.php';
auth_require_admin();

$msg = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = (string)($_POST['old'] ?? '');
    $new = (string)($_POST['new'] ?? '');
    $again = (string)($_POST['again'] ?? '');

    if ($new === '' || $again === '') {
        $err = 'أدخل كلمة المرور الجديدة.';
    } elseif ($new !== $again) {
        $err = 'كلمتا المرور غير متطابقتين.';
    } else {
        $admin = auth_current_admin();
        if ($admin && auth_change_password((int)$admin['id'], $old, $new)) {
            $msg = 'تم تغيير كلمة المرور بنجاح.';
        } else {
            $err = 'كلمة المرور القديمة غير صحيحة.';
        }
    }
}
?>
<!doctype html>
<html lang="ar" dir="rtl">
<head>
<meta charset="utf-8">
<title>تغيير كلمة المرور</title>
<link rel="stylesheet" href="/styles.css">
</head>
<body>
  <h1>تغيير كلمة المرور</h1>

  <?php if ($msg): ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
  <?php if ($err): ?><div class="alert alert-danger"><?= htmlspecialchars($err) ?></div><?php endif; ?>

  <form method="post">
    <label>كلمة المرور الحالية
      <input type="password" name="old" required>
    </label>
    <label>كلمة المرور الجديدة
      <input type="password" name="new" required>
    </label>
    <label>تأكيد كلمة المرور
      <input type="password" name="again" required>
    </label>
    <button type="submit">حفظ</button>
  </form>
</body>
</html>

