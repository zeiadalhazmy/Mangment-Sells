<?php
declare(strict_types=1);
require_once DIR . '/lib/auth.php';

$error = '';
$locked = false;
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = (string)($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $error = 'الرجاء إدخال اسم المستخدم وكلمة المرور.';
    } else {
        if (auth_is_locked($username)) {
            $locked = true;
            $error = 'تم قفل الحساب مؤقتًا لكثرة المحاولات. حاول لاحقًا.';
        } else {
            if (auth_login($username, $password)) {
                $next = $_GET['next'] ?? '/admin.php';
                header('Location: ' . $next);
                exit;
            }
            $error = 'بيانات الدخول غير صحيحة.';
        }
    }
}
?>
<!doctype html>
<html lang="ar" dir="rtl">
<head>
<meta charset="utf-8">
<title>تسجيل الدخول</title>
<link rel="stylesheet" href="/styles.css">
</head>
<body>
  <h1>تسجيل الدخول للوحة الإدارة</h1>
  <?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
  <?php endif; ?>

  <form method="post">
    <label>اسم المستخدم
      <input type="text" name="username" value="<?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?>" required>
    </label>
    <label>كلمة المرور
      <input type="password" name="password" required>
    </label>
    <button type="submit" <?= $locked ? 'disabled' : '' ?>>دخول</button>
  </form>
</body>
</html>
