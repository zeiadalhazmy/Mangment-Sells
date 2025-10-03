<?php
require_once __DIR__.'/../admin_guard.php';
require_once __DIR__.'/../db.php';

// فلاتر (نفس أسماء GET في admin_orders.php)
$status = $_GET['status'] ?? '';
$q      = trim($_GET['q'] ?? '');
$from   = $_GET['from'] ?? '';
$to     = $_GET['to']   ?? '';

$where=[]; $params=[];
if ($status !== '' && in_array($status,['placed','paid','shipped','delivered','cancelled','refunded'])) {
  $where[]="status=:status"; $params[':status']=$status;
}
if ($q!=='') {
  $where[]="(id=:qid OR customer_name LIKE :q OR customer_email LIKE :q2)";
  $params[':qid']=(int)$q;
  $params[':q']='%'.$q.'%'; $params[':q2']='%'.$q.'%';
}
if ($from!==''){ $where[]="date(created_at)>=date(:from)"; $params[':from']=$from; }
if ($to!==''){ $where[]="date(created_at)<=date(:to)";   $params[':to']=$to; }

$sqlWhere = $where?('WHERE '.implode(' AND ',$where)):'';

$stmt=$pdo->prepare("SELECT * FROM orders $sqlWhere ORDER BY datetime(created_at) DESC");
$stmt->execute($params);
$rows=$stmt->fetchAll(PDO::FETCH_ASSOC);

// رأس CSV
$fn='orders_'.date('Ymd_His').'.csv';
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="'.$fn.'"');
$fh = fopen('php://output','w');
// BOM UTF-8
fprintf($fh, chr(0xEF).chr(0xBB).chr(0xBF));

// العناوين
fputcsv($fh, ['id','customer_name','customer_email','total','status','created_at']);
// البيانات
foreach($rows as $r){
  fputcsv($fh, [$r['id'],$r['customer_name'],$r['customer_email'],$r['total'],$r['status'],$r['created_at']]);
}
fclose($fh);
