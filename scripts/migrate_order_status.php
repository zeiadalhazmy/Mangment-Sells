<?php
// scripts/migrate_order_status.php
declare(strict_types=1);

require_once __DIR__ . '/../db.php'; // تأكد أن المسار يصل فعلاً إلى db.php الذي يعرّف $pdo

// تهيئة وضع الأخطاء
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

/**
 * إضافة عمود إذا لم يكن موجوداً (خاصة بـ SQLite)
 */
if (!function_exists('addColumnIfNotExists')) {
    function addColumnIfNotExists(PDO $pdo, string $table, string $column, string $type): void
    {
        // قراءة أعمدة الجدول
        $cols  = $pdo->query("PRAGMA table_info($table)")->fetchAll(PDO::FETCH_ASSOC);
        $names = array_column($cols, 'name');

        // لو العمود موجود نخرج
        if (in_array($column, $names, true)) {
            return;
        }

        // إضافة العمود
        $pdo->exec("ALTER TABLE $table ADD COLUMN $column $type");
    }
}

try {
    // تشغيل المعاملة لسلامة التغييرات
    $pdo->beginTransaction();

    // أعمدة حالة الطلب والطوابع الزمنية
    addColumnIfNotExists($pdo, 'orders', 'status',            "TEXT DEFAULT 'placed'");
    addColumnIfNotExists($pdo, 'orders', 'status_updated_at', 'TEXT');
    addColumnIfNotExists($pdo, 'orders', 'paid_at',           'TEXT');
    addColumnIfNotExists($pdo, 'orders', 'shipped_at',        'TEXT');
    addColumnIfNotExists($pdo, 'orders', 'delivered_at',      'TEXT');
    addColumnIfNotExists($pdo, 'orders', 'cancelled_at',      'TEXT');
    addColumnIfNotExists($pdo, 'orders', 'refunded_at',       'TEXT');
    addColumnIfNotExists($pdo, 'orders', 'status_note',       'TEXT');

    // جدول سجل تغييرات الحالات
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS order_status_logs (
            id          INTEGER PRIMARY KEY AUTOINCREMENT,
            order_id    INTEGER NOT NULL,
            from_status TEXT,
            to_status   TEXT NOT NULL,
            note        TEXT,
            changed_by  TEXT,
            created_at  TEXT DEFAULT (datetime('now'))
        )
    ");

    // فهارس مفيدة
    $pdo->exec("
        CREATE INDEX IF NOT EXISTS idx_orders_status
        ON orders(status)
    ");
    $pdo->exec("
        CREATE INDEX IF NOT EXISTS idx_orders_status_updated
        ON orders(status_updated_at)
    ");
    $pdo->exec("
        CREATE INDEX IF NOT EXISTS idx_order_logs_order_id
        ON order_status_logs(order_id)
    ");

    $pdo->commit();

    echo "Migration OK!\n";
} catch (Throwable $e) {
    // رجوع عن المعاملة عند حدوث خطأ
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    // طباعة الخطأ لتعرف السبب
    fwrite(STDERR, "Migration failed: " . $e->getMessage() . "\n");
    exit(1);
}
