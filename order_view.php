<?php
require_once __DIR__.'/admin_guard.php';
require_once __DIR__.'/db.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { header('Location: admin_orders.php'); exit; }

// قراءة الطلب والعناصر
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id=?");
$stmt->execute([$id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$order) { header('Location: admin_orders.php'); exit; }

$stmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id=?");
$stmt->execute([$id]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<?php include __DIR__.'/header.php'; ?>
<div class="container">
  <h2>تفاصيل الطلب #<?=$order['id']?></h2>
  <p><strong>العميل:</strong> <?=htmlspecialchars($order['customer_name'])?> — <?=htmlspecialchars($order['customer_email'])?></p>
  <p><strong>الحالة:</strong> <?=$order['status']?> | <strong>التاريخ:</strong> <?=$order['created_at']?></p>
  <p><strong>الإجمالي:</strong> <?=number_format((float)$order['total'],2)?></p>

  <h3>العناصر</h3>
  <table class="table">
    <thead><tr><th>المنتج</th><th>الكمية</th><th>السعر</th><th>الإجمالي</th></tr></thead>
    <tbody>
      <?php $sum=0; foreach($items as $it): $line=$it['qty']*$it['price']; $sum+=$line; ?>
      <tr>
        <td><?=htmlspecialchars($it['product_name'])?></td>
        <td><?=$it['qty']?></td>
        <td><?=number_format((float)$it['price'],2)?></td>
        <td><?=number_format((float)$line,2)?></td>
      </tr>
      <?php endforeach; ?>
      <tr><td colspan="3" style="text-align:right"><strong>الإجمالي:</strong></td><td><?=number_format((float)$sum,2)?></td></tr>
    </tbody>
  </table>

  <p>
    <a href="order_status_update.php?id=<?=$order['id']?>">تحديث حالة</a> |
    <a href="admin_orders.php">العودة</a>
  </p>
</div>
<?php include __DIR__.'/footer.php'; ?>
