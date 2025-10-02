<?php
require __DIR__ . '/../db.php';

// تحقّق من وجود العمود 'image'
$cols = $db->query("PRAGMA table_info(products)")->fetchAll(PDO::FETCH_ASSOC);
$hasImage = false;
foreach ($cols as $c) {
    if (strcasecmp($c['name'], 'image') === 0) { $hasImage = true; break; }
}
if (!$hasImage) {
    $db->exec("ALTER TABLE products ADD COLUMN image TEXT NULL");
    echo "تمت إضافة العمود image ✅", PHP_EOL;
} else {
    echo "العمود image موجود مسبقًا ✔", PHP_EOL;
}
