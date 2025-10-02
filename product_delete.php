<?php
declare(strict_types=1);
require_once DIR . '/admin_guard.php';
require_once DIR . '/db.php';
require_once DIR . '/lib/upload.php';

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