<?php
require_once __DIR__.'/admin_guard.php';
require_once __DIR__.'/db.php';
require_once __DIR__.'/lib/logger.php';

$id = (int)($_GET['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD']==='POST') {
  $id = (int)($_POST['id'] ?? 0);
  $new = $_POST['status'] ?? 'placed';
  $allowed = ['placed','paid','shipped','delivered','cancelled','refunded'];
  if ($id>0 && in_array($new,$allowed,true)) {
    $stmt = $pdo->prepare("UPDATE orders SET status=? WHERE id=?");
    $stmt->execute([$new,$id]);
    log_info('order_status_updated', ['order_id'=>$id,'status'=>$new]);
    header('Location: order_view.php?id='.$id);
    exit;
  }
  header('Location: admin_orders.php');
  exit;
}

// GET: عرض النموذج
$stmt=$pdo->prepare("SELECT id,status FROM orders WHERE id=?");
$stmt->execute([$id]);
$order=$stmt->fetch(PDO::FETCH_ASSOC);
if(!$order){ header('Location: admin_orders.php'); exit; }
?>
<?php include __DIR__.'/header.php'; ?>
<div class="container">
  <h2>تحديث حالة الطلب #<?=$order['id']?></h2>
  <form method="post">
    <input type="hidden" name="id" value="<?=$order['id']?>">
    <select name="status">
      <?php foreach(['placed','paid','shipped','delivered','cancelled','refunded'] as $st): ?>
        <option value="<?=$st?>" <?=$order['status']===$st?'selected':''?>><?=$st?></option>
      <?php endforeach; ?>
    </select>
    <button type="submit">حفظ</button>
    <a href="order_view.php?id=<?=$order['id']?>">رجوع</a>
  </form>
</div>
<?php include __DIR__.'/footer.php'; ?>
