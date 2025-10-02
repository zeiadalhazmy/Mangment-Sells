<?php
/**
 * Keep N daily backups, M weekly, Y monthly (simple strategy).
 */
$root = realpath(__DIR__ . '/..');
$backupDir = "$root/storage/backups";

$keepDaily  = 7;
$keepWeekly = 4;
$keepMonthly= 6;

$files = glob("$backupDir/store_*.sqlite");
usort($files, fn($a,$b) => filemtime($b) <=> filemtime($a));

$daily   = [];
$weekly  = [];
$monthly = [];

foreach ($files as $f) {
    $t = filemtime($f);
    $ymd = date('Y-m-d', $t);
    $yw  = date('o-W', $t);
    $ym  = date('Y-m', $t);

    $tagDaily[$ymd]   ??= $f;
    $tagWeekly[$yw]   ??= $f;
    $tagMonthly[$ym]  ??= $f;
}

$daily   = array_values($tagDaily ?? []);
$weekly  = array_values($tagWeekly ?? []);
$monthly = array_values($tagMonthly ?? []);

$protect = array_slice($daily, 0, $keepDaily)
        + array_slice($weekly, 0, $keepWeekly)
        + array_slice($monthly,0, $keepMonthly);

$protect = array_unique($protect);

foreach ($files as $f) {
    if (!in_array($f, $protect, true)) {
        $stem = preg_replace('/\.sqlite$/', '', basename($f));
        @unlink($f);
        @unlink("$backupDir/{$stem}.sqlite-wal");
        @unlink("$backupDir/{$stem}.sqlite-shm");
        @unlink("$backupDir/{$stem}.json");
        echo "Rotated: $stem\n";
    }
}
