<?php /** @var array $order,$items,$customer,$store */ ?>
<!doctype html>
<html lang="ar" dir="rtl">
<head><meta charset="utf-8"><title>طلبك #<?= htmlspecialchars($order['code']) ?></title></head>
<body style="font-family:Tahoma,Arial,sans-serif">
  <h2>شكرًا لطلبك من <?= htmlspecialchars($store) ?></h2>
  <p>رقم الطلب: <strong><?= htmlspecialchars($order['code']) ?></strong></p>
  <p>الحالة: <strong><?= htmlspecialchars($order['status'] ?? 'placed') ?></strong></p>

  <h3>المنتجات</h3>
  <table border="1" cellpadding="6" cellspacing="0">
    <tr><th>المنتج</th><th>الكمية</th><th>السعر</th></tr>
    <?php foreach ($items as $it): ?>
      <tr>
        <td><?= htmlspecialchars($it['title']) ?></td>
        <td><?= (int)$it['qty'] ?></td>
        <td><?= number_format($it['price'], 2) ?> ر.س</td>
      </tr>
    <?php endforeach; ?>
  </table>

  <p>الإجمالي: <strong><?= number_format($order['total'], 2) ?> ر.س</strong></p>

  <p>بياناتك: <?= htmlspecialchars($customer['name'] ?? '') ?> — <?= htmlspecialchars($customer['email'] ?? '') ?></p>

  <p>سنوافيك بالتحديثات فور تغيير حالة الطلب.</p>
</body>
</html>
