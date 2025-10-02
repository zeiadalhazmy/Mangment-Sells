<?php
declare(strict_types=1);
require_once DIR . '/admin_guard.php';
require_once DIR . '/db.php';

$q = trim($_GET['q'] ?? '');
$where = '';
$params = [];
if ($q !== '') {
    $where = "WHERE name LIKE :q OR sku LIKE :q";
    $params[':q'] = "%{$q}%";
}

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

$total = (int)$pdo->prepare("SELECT COUNT(*) FROM products {$where}")
                  ->execute($params)
                  ->fetchColumn();

$stmt = $pdo->prepare("SELECT * FROM products {$where} ORDER BY id DESC LIMIT :limit OFFSET :offset");
foreach ($params as $k => $v) {
    $stmt->bindValue($k, $v, PDO::PARAM_STR);
}
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="ar" dir="rtl">
<head>
<meta charset="utf-8">
<title>المنتجات</title>
<link rel="stylesheet" href="/styles.css">
</head>
<body>
  <h1>المنتجات</h1>

  <form method="get" class="search">
    <input type="text" name="q" placeholder="بحث بالاسم أو SKU" value="<?= htmlspecialchars($q) ?>">
    <button>بحث</button>
    <a href="/product_form.php" class="btn">+ منتج جديد</a>
  </form>

  <table class="table">
    <thead>
      <tr>
        <th>#</th>
        <th>الصورة</th>
        <th>الاسم</th>
        <th>SKU</th>
        <th>السعر</th>
        <th>المخزون</th>
        <th>الحالة</th>
        <th>إجراءات</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($rows as $r): ?>
      <tr>
        <td><?= (int)$r['id'] ?></td>
        <td>
          <?php if (!empty($r['image_path'])): ?>
            <img src="/<?= htmlspecialchars($r['image_path']) ?>" alt="" style="height:50px">
          <?php endif; ?>
        </td>
        <td><?= htmlspecialchars($r['name']) ?></td>
        <td><?= htmlspecialchars($r['sku']) ?></td>
        <td><?= number_format((float)$r['price'], 2) ?></td>
        <td><?= (int)$r['stock'] ?></td>
        <td><?= ((int)$r['is_active'] ? 'نشط' : 'متوقف') ?></td>
        <td>
          <a href="/product_form.php?id=<?= (int)$r['id'] ?>">تعديل</a>
          |
          <a href="/product_delete.php?id=<?= (int)$r['id'] ?>" onclick="return confirm('حذف المنتج؟')">حذف</a>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if (!$rows): ?>
      <tr><td colspan="8">لا توجد نتائج.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>

  <?php
    $pages = (int)ceil($total / $perPage);
    if ($pages > 1):
  ?>
  <nav class="pagination">
    <?php for ($i=1; $i<=$pages; $i++): ?>
      <a class="<?= $i===$page?'active':'' ?>" href="?page=<?= $i ?>&q=<?= urlencode($q) ?>"><?= $i ?></a>
    <?php endfor; ?>
  </nav>
  <?php endif; ?>
</body>
</html>