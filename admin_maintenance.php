<?php
require_once __DIR__.'/admin_guard.php'; // تأكد من الحماية
$root = __DIR__;
$backupDir = __DIR__ . '/storage/backups';

$msg = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['do_backup'])) {
        $out = [];
        $ret = 0;
        exec('php ' . escapeshellarg(__DIR__.'/scripts/backup_db.php') . ' 2>&1', $out, $ret);
        $msg = $ret === 0 ? 'Backup created.' : 'Backup failed: '.implode("\n",$out);
    } elseif (isset($_POST['restore']) && isset($_POST['file'])) {
        $file = basename($_POST['file']);
        $out = [];
        $ret = 0;
        exec('php ' . escapeshellarg(__DIR__.'/scripts/restore_db.php') . ' --file=' . escapeshellarg($file) .' 2>&1', $out, $ret);
        $msg = $ret === 0 ? 'Restored from backup.' : 'Restore failed: '.implode("\n",$out);
    }
}

$files = glob($backupDir.'/store_*.sqlite');
usort($files, fn($a,$b)=> filemtime($b) <=> filemtime($a));
?>
<!doctype html>
<html lang="ar" dir="rtl">
<head><meta charset="utf-8"><title>الصيانة والنسخ الاحتياطي</title></head>
<body>
<h1>النسخ الاحتياطي لقاعدة البيانات</h1>
<?php if ($msg): ?><pre><?=htmlspecialchars($msg)?></pre><?php endif; ?>

<form method="post">
  <button name="do_backup">إنشاء نسخة احتياطية الآن</button>
</form>

<h2>النسخ المتوفرة</h2>
<table border="1" cellpadding="6">
  <tr><th>الملف</th><th>التاريخ</th><th>حجم</th><th>استعادة</th></tr>
  <?php foreach ($files as $f): ?>
    <tr>
      <td><?=htmlspecialchars(basename($f))?></td>
      <td><?=date('Y-m-d H:i:s', filemtime($f))?></td>
      <td><?=number_format(filesize($f)/1024, 1)?> KB</td>
      <td>
        <form method="post" onsubmit="return confirm('سيتم الاستعادة من النسخة: <?=htmlspecialchars(basename($f))?> — هل أنت متأكد؟');">
          <input type="hidden" name="file" value="<?=htmlspecialchars(basename($f))?>">
          <button name="restore">استعادة</button>
        </form>
      </td>
    </tr>
  <?php endforeach; ?>
</table>
</body>
</html>
