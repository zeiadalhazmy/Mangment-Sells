<?php
require_once __DIR__.'/admin_guard.php';
require_once __DIR__.'/db.php';
require_once __DIR__.'/lib/logger.php'; // اختياري إن سبق أن أضفت Logger

// إنشاء الجداول إن لم تكن موجودة (للتجربة / أول تشغيل)
$pdo->exec("
CREATE TABLE IF NOT EXISTS orders (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  customer_name TEXT,
  customer_email TEXT,
  total REAL DEFAULT 0,
  status TEXT DEFAULT 'placed',
  created_at TEXT DEFAULT (datetime('now','localtime'))
);
");
$pdo->exec("
CREATE TABLE IF NOT EXISTS order_items (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  order_id INTEGER,
  product_name TEXT,
  qty INTEGER,
  price REAL,
  FOREIGN KEY(order_id) REFERENCES orders(id) ON DELETE CASCADE
);
");

// فلاتر
$status    = $_GET['status']   ?? '';
$q         = trim($_GET['q']   ?? '');
$from      = $_GET['from']     ?? '';
$to        = $_GET['to']       ?? '';

// ترقيم
$page   = max(1, (int)($_GET['page'] ?? 1));
$limit  = 15;
$offset = ($page - 1) * $limit;

// بناء الشرط
$where = [];
$params = [];

if ($status !== '' && in_array($status, ['placed','paid','shipped','delivered','cancelled','refunded'])) {
  $where[] = "status = :status";
  $params[':status'] = $status;
}
if ($q !== '') {
  $where[] = "(id = :qid OR customer_name LIKE :q OR customer_email LIKE :q2)";
  $params[':qid'] = (int)$q;      // سيطابق id فقط لو q رقم
  $params[':q']  = '%'.$q.'%';
  $params[':q2'] = '%'.$q.'%';
}
if ($from !== '') { $where[] = "date(created_at) >= date(:from)"; $params[':from'] = $from; }
if ($to   !== '') { $where[] = "date(created_at) <= date(:to)";   $params[':to']   = $to; }

$sqlWhere = $where ? ('WHERE '.implode(' AND ', $where)) : '';

// العد الإجمالي
$stmt = $pdo->prepare("SELECT COUNT(*) FROM orders $sqlWhere");
$stmt->execute($params);
$totalRows = (int)$stmt->fetchColumn();

// الجلب
$sql = "SELECT * FROM orders $sqlWhere ORDER BY datetime(created_at) DESC LIMIT :lim OFFSET :off";
$stmt = $pdo->prepare($sql);
foreach ($params as $k=>$v) { $stmt->bindValue($k,$v); }
$stmt->bindValue(':lim',$limit,PDO::PARAM_INT);
$stmt->bindValue(':off',$offset,PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// لروابط التصدير/الطباعة بنفس الفلاتر
$qs = http_build_query(['status'=>$status,'q'=>$q,'from'=>$from,'to'=>$to]);

?>
<?php include __DIR__.'/header.php'; ?>
<style>
.table{width:100%;border-collapse:collapse}
.table th,.table td{border:1px solid #ddd;padding:8px;font-size:14px}
.table th{background:#f5f5f5}
.filters{display:flex;gap:8px;flex-wrap:wrap;margin:12px 0}
.filters input,.filters select{padding:6px 8px}
.actions a{margin-right:8px}
.badge{padding:3px 7px;border-radius:12px;background:#eee}
.badge.placed{background:#ffecc7}
.badge.paid{background:#d7ffd7}
.badge.shipped{background:#d7eeff}
.badge.delivered{background:#cbead1}
.badge.cancelled,.badge.refunded{background:#ffd7d7}
.pager a{padding:6px 10px;border:1px solid #ccc;margin-right:6px;text-decoration:none}
.pager .active{background:#333;color:#fff}
.head-actions{margin:12px 0;display:flex;gap:10px;align-items:center}
</style>

<div class="container">
  <h2>إدارة الطلبات</h2>

  <form class="filters" method="get">
    <input type="text" name="q" value="<?=htmlspecialchars($q)?>" placeholder="بحث: رقم/اسم/إيميل">
    <select name="status">
      <option value="">كل الحالات</option>
      <?php
      $statuses = ['placed'=>'placed','paid'=>'paid','shipped'=>'shipped','delivered'=>'delivered','cancelled'=>'cancelled','refunded'=>'refunded'];
      foreach($statuses as $key=>$lbl): ?>
        <option value="<?=$key?>" <?= $status===$key?'selected':''?>><?=$key?></option>
      <?php endforeach; ?>
    </select>
    <label>من: <input type="date" name="from" value="<?=htmlspecialchars($from)?>"></label>
    <label>إلى: <input type="date" name="to" value="<?=htmlspecialchars($to)?>"></label>
    <button type="submit">تصفية</button>
    <a href="admin_orders.php">تفريغ</a>
  </form>

  <div class="head-actions">
    <strong>العدد:</strong> <?=$totalRows?>
    <a href="scripts/export_orders_csv.php?<?=$qs?>">تصدير CSV</a>
    <a href="admin_orders.php?<?=$qs?>&print=1" target="_blank">عرض للطبـاعة</a>
  </div>

  <?php if(isset($_GET['print'])): ?>
    <script>window.onload=()=>{window.print();}</script>
  <?php endif; ?>

  <table class="table">
    <thead>
      <tr>
        <th>#</th>
        <th>العميل</th>
        <th>الإيميل</th>
        <th>الإجمالي</th>
        <th>الحالة</th>
        <th>تاريخ الإنشاء</th>
        <th>إجراءات</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach($rows as $r): ?>
        <tr>
          <td><?=$r['id']?></td>
          <td><?=htmlspecialchars($r['customer_name'])?></td>
          <td><?=htmlspecialchars($r['customer_email'])?></td>
          <td><?=number_format((float)$r['total'],2)?></td>
          <td><span class="badge <?=$r['status']?>"><?=$r['status']?></span></td>
          <td><?=htmlspecialchars($r['created_at'])?></td>
          <td class="actions">
            <a href="order_view.php?id=<?=$r['id']?>">تفاصيل</a>
            <a href="order_status_update.php?id=<?=$r['id']?>">تحديث حالة</a>
          </td>
        </tr>
      <?php endforeach; if(!$rows): ?>
        <tr><td colspan="7" style="text-align:center;color:#777">لا توجد بيانات</td></tr>
      <?php endif; ?>
    </tbody>
  </table>

  <?php
  $pages = (int)ceil($totalRows / $limit);
  if ($pages > 1): ?>
    <div class="pager" style="margin-top:10px">
      <?php for($i=1;$i<=$pages;$i++):
        $link = 'admin_orders.php?'.http_build_query(array_merge($_GET,['page'=>$i]));
        $cls = $i===$page ? 'active' : '';
      ?>
        <a class="<?=$cls?>" href="<?=$link?>"><?=$i?></a>
      <?php endfor; ?>
    </div>
  <?php endif; ?>
</div>

<?php include __DIR__.'/footer.php'; ?>
