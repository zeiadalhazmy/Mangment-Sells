<?php
// export_sales_csv.php
require_once __DIR__.'/admin_guard.php';
require_once __DIR__.'/db.php';

$period = $_GET['period'] ?? 'day';
$from   = $_GET['from']   ?? date('Y-m-d', strtotime('-30 days'));
$to     = $_GET['to']     ?? date('Y-m-d');
$status = $_GET['status'] ?? '';
$toEnd  = date('Y-m-d 23:59:59', strtotime($to));

$fmt = ($period === 'month') ? "%Y-%m" : "%Y-%m-%d";

$sql = "SELECT
          strftime('$fmt', created_at) AS bucket,
          COUNT(*) AS orders_count,
          SUM(total) AS revenue,
          AVG(total) AS aov,
          SUM(CASE WHEN payment_status='paid' THEN total ELSE 0 END) AS paid_revenue
        FROM orders
        WHERE datetime(created_at) BETWEEN datetime(?) AND datetime(?)";

$params = [$from, $toEnd];
if ($status !== '') {
  $sql .= " AND status = ? ";
  $params[] = $status;
}
$sql .= " GROUP BY bucket ORDER BY bucket ASC ";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="sales_'.$period.'_'.$from.'_to_'.$to.'.csv"');

$out = fopen('php://output', 'w');
// BOM ليتعرف Excel على UTF-8
fwrite($out, "\xEF\xBB\xBF");

fputcsv($out, ['الفترة','عدد الطلبات','الإيراد الكلي','إيراد مدفوع','متوسط الفاتورة']);

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
  fputcsv($out, [
    $row['bucket'],
    (int)$row['orders_count'],
    number_format((float)$row['revenue'], 2, '.', ''),
    number_format((float)$row['paid_revenue'], 2, '.', ''),
    number_format((float)$row['aov'], 2, '.', ''),
  ]);
}
fclose($out);
exit;
