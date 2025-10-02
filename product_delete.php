<?php
declare(strict_types=1);
require_once DIR . '/admin_guard.php';
require_once DIR . '/db.php';
require_once DIR . '/lib/upload.php';

$id = (int)($_GET['id'] ?? 0);
if ($id) {
    $stmt = $db->prepare('SELECT image FROM products WHERE id=?');
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row && !empty($row['image'])) {
        Upload::deleteImage($row['image']);
    }
    $db->prepare('DELETE FROM products WHERE id=?')->execute([$id]);
}
header('Location: admin_products.php?msg=deleted');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id) {
    $st = $pdo->prepare("SELECT image_path FROM products WHERE id=:id");
    $st->execute([':id' => $id]);
    $old = $st->fetchColumn();

    $del = $pdo->prepare("DELETE FROM products WHERE id=:id");
    $del->execute([':id' => $id]);

    // احذف الصورة
    delete_product_image($old);
}
header('Location: /admin_products.php');
exit;