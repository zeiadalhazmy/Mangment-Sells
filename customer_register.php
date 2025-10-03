<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/lib/auth_customer.php';

$errors = [];
$name = trim($_POST['name'] ?? '');
$email = strtolower(trim($_POST['email'] ?? ''));
$pass  = $_POST['password'] ?? '';
$pass2 = $_POST['password2'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($name === '')   $errors[] = 'الاسم مطلوب';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'بريد غير صالح';
    if ($pass === '' || strlen($pass) < 6) $errors[] = 'كلمة المرور 6 أحرف على الأقل';
    if ($pass !== $pass2) $errors[] = 'تأكيد كلمة المرور غير مطابق';

    if (!$errors) {
        if (customer_find_by_email($pdo, $email)) {
            $errors[] = 'البريد مستخدم مسبقًا';
        } else {
            $hash = password_hash($pass, PASSWORD_DEFAULT);
            $st = $pdo->prepare("INSERT INTO customers(name,email,password_hash) VALUES(?,?,?)");
            $st->execute([$name, $email, $hash]);
            $_SESSION['customer_id'] = (int)$pdo->lastInsertId();
            header("Location: /my_orders.php");
            exit;
        }
    }
}
?>
<?php include __DIR__ . '/header.php'; ?>
<div class="container">
    <h2>تسجيل عميل جديد</h2>
    <?php if ($errors): ?>
        <div class="alert alert-danger"><?php echo implode('<br>', array_map('htmlspecialchars', $errors)); ?></div>
    <?php endif; ?>
    <form method="post">
        <label>الاسم</label>
        <input type="text" name="name" value="<?= htmlspecialchars($name) ?>" required>

        <label>البريد الإلكتروني</label>
        <input type="email" name="email" value="<?= htmlspecialchars($email) ?>" required>

        <label>كلمة المرور</label>
        <input type="password" name="password" required>

        <label>تأكيد كلمة المرور</label>
        <input type="password" name="password2" required>

        <button type="submit">تسجيل</button>
    </form>
    <p>لديك حساب؟ <a href="/customer_login.php">تسجيل الدخول</a></p>
</div>
<?php include __DIR__ . '/footer.php'; ?>