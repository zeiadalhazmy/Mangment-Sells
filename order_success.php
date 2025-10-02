<?php

declare(strict_types=1);
require __DIR__ . '/db.php';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
include __DIR__ . '/header.php';
?>
<h1>تم استلام طلبك بنجاح</h1>
<p>رقم الطلب: #<?= $id ?></p>
<p>سنقوم بالتواصل معك لتأكيد الدفع والشحن. شكرًا لك.</p>
<p><a href="catalog.php">العودة للمتجر</a></p>
<?php include __DIR__ . '/footer.php'; ?>