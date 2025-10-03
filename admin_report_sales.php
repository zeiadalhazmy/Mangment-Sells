<?php
// admin_report_sales.php
require_once __DIR__.'/admin_guard.php';   // يتحقق أنك مدير
require_once __DIR__.'/db.php';

$period = $_GET['period'] ?? 'day';   // day | month
$from   = $_GET['from']   ?? date('Y-m-d', strtotime('-30 days'));
$to     = $_GET['to']     ?? date('Y-m-d');   // شامل
$status = $_GET['status'] ?? '';              // اختياري

// تحويل to إلى نهاية اليوم
$toEnd  = date('Y-m-d 23:59:59', strtotime($to));

$fmt = ($period === 'month') ? "%Y-%m" : "%Y-%m-%d";

$sql = "SELECT
          strftime('$fmt', created_at) AS bucket,
          COUNT(*)                     AS orders_count,
          SUM(total)                   AS revenue,
          AVG(total)                   AS aov,
          SUM(CASE WHEN payment_status='paid' THEN total ELSE 0 END) AS paid_revenue
        FROM orders
        WHERE datetime(created_at) BETWEEN datetime(?) AND datetime(?)
      ";

$params = [$from, $toEnd];

if ($status !== '') {
  $sql .= " AND status = ? ";
  $params[] = $status;
}

$sql .= " GROUP BY bucket ORDER BY bucket ASC ";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ملخص عام
$sumSql = "SELECT
             COUNT(*) AS total_orders,
             SUM(total) AS total_revenue,
             AVG(total) AS avg_order_value
           FROM orders
           WHERE datetime(created_at) BETWEEN datetime(?) AND datetime(?)";

$sumParams = [$from, $toEnd];
if ($status !== '') {
  $sumSql .= " AND status = ? ";
  $sumParams[] = $status;
}
$sumStmt = $pdo->prepare($sumSql);
$sumStmt->execute($sumParams);
$summary = $sumStmt->fetch(PDO::FETCH_ASSOC);

$exportUrl = '/export_sales_csv.php?period='.urlencode($period)
           . '&from='.urlencode($from).'&to='.urlencode($to)
           . ($status !== '' ? '&status='.urlencode($status) : '');

include __DIR__.'/header.php';
?>
<div class="container">
  <h2>تقرير المبيعات (<?=$period === 'month' ? 'شهري' : 'يومي'?>)</h2>

  <form class="filters" method="get" action="/admin_report_sales.php">
    <label>الفترة:</label>
    <select name="period">
      <option value="day"   <?=$period==='day'?'selected':''?>>يومي</option>
      <option value="month" <?=$period==='month'?'selected':''?>>شهري</option>
    </select>

    <label>من</label>
    <input type="date" name="from" value="<?=htmlspecialchars($from)?>">

    <label>إلى</label>
    <input type="date" name="to" value="<?=htmlspecialchars($to)?>">

    <label>حالة الطلب (اختياري)</label>
    <input type="text" name="status" placeholder="placed/paid/shipped/..." value="<?=htmlspecialchars($status)?>">

    <button type="submit">تحديث</button>
    <a class="btn" href="<?=$exportUrl?>">تصدير CSV</a>
  </form>

  <div class="cards">
    <div class="card"><b>عدد الطلبات</b><div><?= (int)($summary['total_orders'] ?? 0) ?></div></div>
    <div class="card"><b>إجمالي المبيعات</b><div><?= number_format((float)($summary['total_revenue'] ?? 0), 2) ?></div></div>
    <div class="card"><b>متوسط الفاتورة</b><div><?= number_format((float)($summary['avg_order_value'] ?? 0), 2) ?></div></div>
  </div>

  <table class="table">
    <thead>
      <tr>
        <th>الفترة</th>
        <th>عدد الطلبات</th>
        <th>الإيراد (الكل)</th>
        <th>إيراد مدفوع</th>
        <th>متوسط الفاتورة</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><?=htmlspecialchars($r['bucket'])?></td>
          <td><?= (int)$r['orders_count'] ?></td>
          <td><?= number_format((float)$r['revenue'], 2) ?></td>
          <td><?= number_format((float)$r['paid_revenue'], 2) ?></td>
          <td><?= number_format((float)$r['aov'], 2) ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$rows): ?>
        <tr><td colspan="5">لا توجد بيانات ضمن المدى الزمني المحدد.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>
<?php include __DIR__.'/footer.php'; ?>
