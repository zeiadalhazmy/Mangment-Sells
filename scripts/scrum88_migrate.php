<?php
// scripts/scrum88_migrate.php
// تشغيله مرة واحدة لتهيئة "التصنيفات" وعمود category_id

require DIR . '/../db.php';

try {
    // 1) إنشاء جدول التصنيفات إن لم يكن موجودًا
    $db->exec("
        CREATE TABLE IF NOT EXISTS categories (
            id   INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL UNIQUE,
            slug TEXT NOT NULL UNIQUE
        );
    ");

    // 2) إضافة عمود category_id للمنتجات إن لم يكن موجودًا
    $cols = $db->query("PRAGMA table_info(products)")->fetchAll(PDO::FETCH_ASSOC);
    $hasCat = false;
    foreach ($cols as $c) {
        if ($c['name'] === 'category_id') { $hasCat = true; break; }
    }
    if (!$hasCat) {
        $db->exec("ALTER TABLE products ADD COLUMN category_id INTEGER NULL;");
        $db->exec("CREATE INDEX IF NOT EXISTS idx_products_category ON products(category_id);");
    }

    // 3) إدخال بيانات افتراضية للتصنيفات (مرّة واحدة)
    $exists = (int)$db->query("SELECT COUNT(*) FROM categories")->fetchColumn();
    if ($exists === 0) {
        $ins = $db->prepare("INSERT INTO categories(name, slug) VALUES (?, ?)");
        foreach ([
            ['Electronics','electronics'],
            ['Clothing','clothing'],
            ['Books','books'],
            ['Home','home'],
        ] as $row) $ins->execute($row);
    }

    echo "SCRUM-88 migration done.\n";
} catch (Throwable $e) {
    http_response_code(500);
    echo "Migration error: ".$e->getMessage()."\n";
    exit(1);
}