<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/lib/auth_customer.php';
require_customer_login($pdo);

$customer = current_customer($pdo);

$st = $pdo->prepare("SELECT id, created_at, status, total, payment_status
                     FROM orders WHERE customer_id = ?
                     ORDER BY id DESC");
$st->execute([(int)$customer['id']]);
$orders = $st->fetchAll(PDO::FETCH_ASSOC);
?>
<?php include __DIR__ . '/header.php'; ?>
<div class="container">
    <h2>طلباتي</h2>
    <p>مرحباً، <?= htmlspecialchars($customer['name']) ?></p>

    <?php if (!$orders): ?>
        <p>لا توجد طلبات بعد.</p>
    <?php else: ?>
        <table class="table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>التاريخ</th>
                    <th>الحالة</th>
                    <th>الدفع</th>
                    <th>الإجمالي</th>
                    <th>عرض</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $o): ?>
                    <tr>
                        <td><?= $o['id'] ?></td>
                        <td><?= htmlspecialchars($o['created_at']) ?></td>
                        <td><?= htmlspecialchars($o['status']) ?></td>
                        <td><?= htmlspecialchars($o['payment_status'] ?: '—') ?></td>
                        <td><?= number_format((float)$o['total'], 2) ?></td>
                        <td><a href="/order_view.php?id=<?= $o['id'] ?>">تفاصيل</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
<?php include __DIR__ . '/footer.php'; ?>