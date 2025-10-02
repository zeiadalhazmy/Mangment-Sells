<?php
/**
 * SCRUM-91 — Restore SQLite DB from a backup.
 * Usage:
 *   php scripts/restore_db.php --file=store_20251002_123000.sqlite
 */

$root = realpath(__DIR__ . '/..');
$storageDir = "$root/storage";
$backupDir  = "$storageDir/backups";

$dbFile = null;
$dbPhp  = "$root/db.php";
if (file_exists($dbPhp)) {
    include $dbPhp;
    if (isset($DB_FILE)) $dbFile = $DB_FILE;
}
if (!$dbFile) $dbFile = "$storageDir/store.sqlite";

if (!is_dir($backupDir)) {
    fwrite(STDERR, "ERROR: Backups dir not found.\n");
    exit(1);
}

$srcFile = null;
foreach ($argv as $arg) {
    if (str_starts_with($arg, '--file=')) {
        $srcFile = substr($arg, strlen('--file='));
    }
}
if (!$srcFile) {
    fwrite(STDERR, "Usage: php scripts/restore_db.php --file=store_YYYYmmdd_HHMMSS.sqlite\n");
    exit(1);
}

$srcDb  = "$backupDir/$srcFile";
$stem   = preg_replace('/\.sqlite$/', '', $srcFile);
$srcWal = "$backupDir/{$stem}.sqlite-wal";
$srcShm = "$backupDir/{$stem}.sqlite-shm";
$srcMan = "$backupDir/{$stem}.json";

if (!file_exists($srcDb)) {
    fwrite(STDERR, "ERROR: backup file not found: $srcDb\n");
    exit(1);
}

// تحقق من الـ checksum إن توفّر manifest
if (file_exists($srcMan)) {
    $man = json_decode(file_get_contents($srcMan), true);
    if (isset($man['checksum']['db'])) {
        $calc = hash_file('sha256', $srcDb);
        if ($calc !== $man['checksum']['db']) {
            fwrite(STDERR, "ERROR: DB checksum mismatch, aborting.\n");
            exit(1);
        }
    }
}

// إغلاق أي اتصال قد يكون مفتوح قبل الاستعادة (بضمان إيقاف الخدمة/التطبيق)

// نسخ
if (!copy($srcDb, $dbFile)) {
    fwrite(STDERR, "ERROR: Failed to restore DB to $dbFile\n");
    exit(1);
}
if (file_exists($srcWal)) copy($srcWal, "$dbFile-wal");
if (file_exists($srcShm)) copy($srcShm, "$dbFile-shm");

echo "Restored DB from:\n  $srcDb\n";
