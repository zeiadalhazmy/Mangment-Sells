<?php
/**
 * SCRUM-91 — Backup SQLite DB safely (with WAL checkpoint + manifest).
 * Usage:
 *   php scripts/backup_db.php
 *   php scripts/backup_db.php --retention=7     # (اختياري) يحذف النسخ الأقدم من 7 أيام
 */

date_default_timezone_set('UTC');

$root = realpath(__DIR__ . '/..');
$storageDir = "$root/storage";
$backupDir  = "$storageDir/backups";
if (!is_dir($backupDir)) {
    mkdir($backupDir, 0775, true);
}

// تحديد مسار قاعدة البيانات من db.php إن وُجد
$dbFile = null;
$dbPhp  = "$root/db.php";
if (file_exists($dbPhp)) {
    // نحاول قراءة $DB_FILE إن كان معرفًا
    include $dbPhp;
    if (isset($DB_FILE)) {
        $dbFile = $DB_FILE;
    }
}

if (!$dbFile) {
    // افتراضي
    $dbFile = "$storageDir/store.sqlite";
}

if (!file_exists($dbFile)) {
    fwrite(STDERR, "ERROR: SQLite file not found: $dbFile\n");
    exit(1);
}

$ts = date('Ymd_His');
$base = "store_$ts";
$dstDb  = "$backupDir/{$base}.sqlite";
$dstWal = "$backupDir/{$base}.sqlite-wal";
$dstShm = "$backupDir/{$base}.sqlite-shm";

// 1) إيقاف أي سجلات WAL عالقة وضمان ثبات الملف
try {
    $sqlite = new SQLite3($dbFile, SQLITE3_OPEN_READWRITE);
    // Force WAL checkpoint for a clean copy (safe if not in WAL mode)
    @$sqlite->exec('PRAGMA wal_checkpoint(FULL);');
    @$sqlite->exec('PRAGMA optimize;');
    $sqlite->close();
} catch (Throwable $e) {
    // نكمل مع تحذير فقط
    fwrite(STDERR, "WARN: Could not checkpoint/optimize: {$e->getMessage()}\n");
}

// 2) نسخ الملفات (db + wal + shm إن وُجدت)
if (!copy($dbFile, $dstDb)) {
    fwrite(STDERR, "ERROR: Failed to copy DB to $dstDb\n");
    exit(1);
}
if (file_exists("$dbFile-wal")) {
    copy("$dbFile-wal", $dstWal);
}
if (file_exists("$dbFile-shm")) {
    copy("$dbFile-shm", $dstShm);
}

// 3) توليد manifest للتحقق
$manifest = [
    'db'       => basename($dstDb),
    'wal'      => file_exists($dstWal) ? basename($dstWal) : null,
    'shm'      => file_exists($dstShm) ? basename($dstShm) : null,
    'created'  => date('c'),
    'checksum' => [
        'db'  => hash_file('sha256', $dstDb),
        'wal' => file_exists($dstWal) ? hash_file('sha256', $dstWal) : null,
        'shm' => file_exists($dstShm) ? hash_file('sha256', $dstShm) : null,
    ],
];
file_put_contents("$backupDir/{$base}.json", json_encode($manifest, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));

echo "Backup created:\n  $dstDb\n";
if (file_exists($dstWal)) echo "  $dstWal\n";
if (file_exists($dstShm)) echo "  $dstShm\n";
echo "  $backupDir/{$base}.json\n";

// 4) (اختياري) سياسة الاحتفاظ البسيطة بالأيام
$retentionDays = null;
foreach ($argv as $arg) {
    if (str_starts_with($arg, '--retention=')) {
        $retentionDays = (int)substr($arg, strlen('--retention='));
    }
}
if ($retentionDays) {
    $cutoff = time() - ($retentionDays * 86400);
    $files = glob("$backupDir/store_*.sqlite");
    foreach ($files as $file) {
        if (filemtime($file) < $cutoff) {
            $stem = preg_replace('/\.sqlite$/', '', basename($file));
            @unlink("$backupDir/{$stem}.sqlite");
            @unlink("$backupDir/{$stem}.sqlite-wal");
            @unlink("$backupDir/{$stem}.sqlite-shm");
            @unlink("$backupDir/{$stem}.json");
            echo "Rotated old backup: $stem\n";
        }
    }
}
