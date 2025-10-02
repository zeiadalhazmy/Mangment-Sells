زياد الحزمي, [02/10/2025 05:00 ص]
<?php
// catalog.php — صفحة مستقلّة لمهمة SCRUM-88
require __DIR__ . '/db.php';
require __DIR__ . '/lib/catalog.php';
require __DIR__ . '/header.php';

// مدخلات
$categoryId = isset($_GET['category']) ? ($_GET['category']) : null;
$query      = isset($_GET['q']) ? trim($_GET['q']) : null;
$page       = max(1, ($_GET['page'] ?? 1));
$perPage    = 8;



// بيانات
$cats   = db_categories($db);
$total  = db_products_count($db, $categoryId, $query);
$pages  = max(1, (ceil($total / $perPage)));
$page   = min($page, $pages);
$offset = ($page - 1) * $perPage;
$items  = db_products_page($db, $categoryId, $query, $perPage, $offset);

// مساعد لبناء الروابط مع الاحتفاظ بالباراميترات
function build_url(array $params): string {
    $base = strtok($_SERVER['REQUEST_URI'], '?');
    $q    = array_filter(array_merge($_GET, $params), fn($v) => $v !== '' && $v !== null);
    return $base . ($q ? ('?' . http_build_query($q)) : '');
}
?>
<style>
/* CSS بسيط مدمج مع الصفحة — لا نعدل ملفات أخرى */
.scrum88 .wrap { display:grid; grid-template-columns: 220px 1fr; gap:18px; }
@media (max-width:900px){ .scrum88 .wrap{ grid-template-columns:1fr; } }

.scrum88 .panel { border:1px solid #eee; border-radius:8px; padding:12px; background:#fff; }
.scrum88 .cats{ list-style:none; padding:0; margin:8px 0; display:flex; flex-direction:column; gap:8px; }
.scrum88 .cats a{ padding:8px 10px; border:1px solid #ddd; border-radius:8px; text-decoration:none; color:#222; }
.scrum88 .cats a.active{ background:#111; color:#fff; border-color:#111; }

.scrum88 .search { display:flex; gap:8px; margin-top:10px; }
.scrum88 .search input{ flex:1; padding:8px 10px; border:1px solid #ddd; border-radius:8px; }

.scrum88 .grid{ display:grid; grid-template-columns: repeat(auto-fill, minmax(190px,1fr)); gap:16px; }
.scrum88 .card{ border:1px solid #eee; border-radius:10px; padding:12px; }
.scrum88 .card img{ width:100%; height:150px; object-fit:cover; border-radius:8px; }
.scrum88 .card h4{ font-size:15px; margin:8px 0 4px; }
.scrum88 .price{ font-weight:bold; margin-bottom:8px; }
.scrum88 .btn{ display:inline-block; padding:8px 10px; background:#111; color:#fff; border-radius:8px; text-decoration:none; }

.scrum88 .pagination{ margin-top:16px; display:flex; gap:6px; flex-wrap:wrap; }
.scrum88 .pagination a{ padding:6px 10px; border:1px solid #ccc; border-radius:8px; text-decoration:none; color:#222; }
.scrum88 .pagination a.active{ background:#111; color:#fff; border-color:#111; }
</style>

<main class="scrum88">
  <h2>كتالوج المنتجات — تصنيفات + ترقيم (SCRUM-88)</h2>
  <div class="wrap">
    <aside class="panel">
      <h3>التصنيفات</h3>
      <ul class="cats">
        <li><a href="<?= htmlspecialchars(build_url(['category'=>null,'page'=>1])) ?>"
               class="<?= $categoryId ? '' : 'active' ?>">الكل</a></li>
        <?php foreach ($cats as $c): ?>
          <li><a href="<?= htmlspecialchars(build_url(['category'=>$c['id'],'page'=>1])) ?>"
                 class="<?= ($categoryId===$c['id']) ? 'active' : '' ?>">
              <?= htmlspecialchars($c['name']) ?>
          </a></li>
        <?php endforeach; ?>
      </ul>

      <form class="search" method="get" action="">
        <input type="hidden" name="category" value="<?= $categoryId ?>">
        <input type="text"   name="q" value="<?= htmlspecialchars($query ?? '') ?>" placeholder="ابحث…">
        <button class="btn" type="submit">بحث</button>
      </form>
    </aside>

    <section>
      <div class="panel" style="margin-bottom:12px">
        <strong><?= $total ?></strong> نتيجة
        <?php if ($categoryId): ?> ضمن التصنيف المختار<?php endif; ?>
      </div>
<div class="grid">
        <?php foreach ($items as $p): ?>
          <article class="card">
            <img src="<?= htmlspecialchars($p['image'] ?? 'uploads/no-image.png') ?>" alt="">
            <h4><?= htmlspecialchars($p['name']) ?></h4>
            <div class="price"><?= number_format(($p['price']), 2) ?> ر.س</div>
            <a class="btn" href="cart.php?action=add&id=<?= ($p['id'] )?>">أضف للسلة</a>
          </article>
        <?php endforeach; ?>
<a href="cart.php?action=add&id=<?= $p['id'] ?>">أضف إلى العربة</a>
        <?php if (!$items): ?>
          <p class="panel">لا توجد نتائج مطابقة.</p>
        <?php endif; ?>
      </div>

      <?php if ($pages > 1): ?>
        <nav class="pagination">
          <?php for ($i=1; $i<=$pages; $i++): ?>
            <a href="<?= htmlspecialchars(build_url(['page'=>$i])) ?>"
               class="<?= ($i===$page) ? 'active' : '' ?>"><?= $i ?></a>
          <?php endfor; ?>
        </nav>
      <?php endif; ?>
    </section>
  </div>
</main>

<?php require __DIR__ . '/footer.php'; ?>