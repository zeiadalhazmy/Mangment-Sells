<?php
require_once __DIR__.'/admin_guard.php';
require_once __DIR__.'/db.php';
require_once __DIR__.'/lib/order_status.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: /admin_orders.php'); exit; }

$st = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
$st->execute([$id]);
$order = $st->fetch(PDO::FETCH_ASSOC);
if (!$order) { header('Location: /admin_orders.php'); exit; }

$lg = $pdo->prepare("SELECT * FROM order_status_logs WHERE order_id=? ORDER BY created_at DESC");
$lg->execute([$id]);
$logs = $lg->fetchAll(PDO::FETCH_ASSOC);

include __DIR__.'/header.php';
?>
<div class="container">
  <h2>تفاصيل الطلب #<?=$order['id']?></h2>

  <div class="cards">
    <div class="card"><b>الحالة</b><div><?=$order['status']?></div></div>
    <div class="card"><b>الإجمالي</b><div><?=number_format((float)$order['total'],2)?></div></div>
    <div class="card"><b>أُنشئ</b><div><?=htmlspecialchars($order['created_at'])?></div></div>
    <div class="card"><b>آخر تحديث حالة</b><div><?=htmlspecialchars($order['status_updated_at'] ?? '')?></div></div>
  </div>

  <h3>سجل تغييرات الحالة</h3>
  <table class="table">
    <thead><tr><th>من</th><th>إلى</th><th>ملاحظة</th><th>بواسطة</th><th>التاريخ</th></tr></thead>
    <tbody>
      <?php foreach ($logs as $l): ?>
        <tr>
          <td><?=htmlspecialchars($l['from_status'])?></td>
          <td><?=htmlspecialchars($l['to_status'])?></td>
          <td><?=htmlspecialchars($l['note'])?></td>
          <td><?=htmlspecialchars($l['changed_by'])?></td>
          <td><?=htmlspecialchars($l['created_at'])?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$logs): ?><tr><td colspan="5">لا يوجد سجل.</td></tr><?php endif; ?>
    </tbody>
  </table>

  <p><a class="btn" href="/admin_orders.php">عودة للقائمة</a></p>
</div>
<?php include __DIR__.'/footer.php'; ?>
