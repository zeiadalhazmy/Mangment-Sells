<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/lib/auth_customer.php';

$errors = [];
$email = strtolower(trim($_POST['email'] ?? ''));
$pass  = $_POST['password'] ?? '';
$next  = $_GET['next'] ?? '/my_orders.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer = customer_find_by_email($pdo, $email);
    if (!$customer || !password_verify($pass, $customer['password_hash'])) {
        $errors[] = 'بيانات الدخول غير صحيحة';
    } else {
        $_SESSION['customer_id'] = (int)$customer['id'];
        header("Location: " . ($next ?: '/my_orders.php'));
        exit;
    }
}
?>
<?php include __DIR__ . '/header.php'; ?>
<div class="container">
    <h2>تسجيل الدخول</h2>
    <?php if ($errors): ?>
        <div class="alert alert-danger"><?php echo implode('<br>', array_map('htmlspecialchars', $errors)); ?></div>
    <?php endif; ?>
    <form method="post">
        <label>البريد الإلكتروني</label>
        <input type="email" name="email" value="<?= htmlspecialchars($email) ?>" required>

        <label>كلمة المرور</label>
        <input type="password" name="password" required>

        <button type="submit">دخول</button>
    </form>
    <p>مستخدم جديد؟ <a href="/customer_register.php">إنشاء حساب</a></p>
</div>
<?php include __DIR__ . '/footer.php'; ?>