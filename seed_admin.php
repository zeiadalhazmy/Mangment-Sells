<?php
// scripts/seed_admin.php
declare(strict_types=1);

require_once DIR . '/../db.php';

$sql = file_get_contents(DIR . '/20241002_auth.sql');
$pdo->exec($sql);

// أنشئ admin إذا مش موجود
$stmt = $pdo->query("SELECT COUNT(*) FROM admins WHERE username='admin'");
if ((int)$stmt->fetchColumn() === 0) {
    $hash = password_hash('admin123', PASSWORD_DEFAULT);
    $ins = $pdo->prepare("INSERT INTO admins(username, password_hash) VALUES('admin', :h)");
    $ins->execute([':h' => $hash]);
    echo "Seeded admin (admin/admin123)\n";
} else {
    echo "Admin already exists.\n";
}
