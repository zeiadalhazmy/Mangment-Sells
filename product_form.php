<?php
declare(strict_types=1);
require_once DIR . '/admin_guard.php';
require_once DIR . '/db.php';
require_once DIR . '/lib/upload.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$product = [
  'name' => '',
  'sku'  => '',
  'price' => 0.00,
  'stock' => 0,
  'category' => '',
  'description' => '',
  'image_path' => null,
  'is_active' => 1
];

if ($id) {
    $st = $pdo->prepare("SELECT * FROM products WHERE id=:id");
    $st->execute([':id' => $id]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        http_response_code(404);
        exit('لم يتم العثور على المنتج.');
    }
    $product = $row;
}

$err = '';
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name  = trim($_POST['name'] ?? '');
    $sku   = trim($_POST['sku'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $stock = (int)($_POST['stock'] ?? 0);
    $category = trim($_POST['category'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    if ($name === '' || $sku === '') {
        $err = 'الاسم و SKU إجباريان.';
    } else {
        try {
            // رفع الصورة (إن وجدت)
            $newImage = upload_product_image($_FILES['image'] ?? null);

            if ($id) {
                // تعديل
                $params = [
                    ':name' => $name,
                    ':sku' => $sku,
                    ':price' => $price,
                    ':stock' => $stock,
                    ':category' => $category,
                    ':description' => $description,
                    ':is_active' => $is_active,
                    ':id' => $id,
                ];

                if ($newImage) {
                    // احذف القديمة واحفظ الجديدة
                    delete_product_image($product['image_path']);
                    $params[':image_path'] = $newImage;
                    $sql = "UPDATE products
                            SET name=:name, sku=:sku, price=:price, stock=:stock,
                                category=:category, description=:description,
                                image_path=:image_path, is_active=:is_active,
                                updated_at=datetime('now')
                            WHERE id=:id";
                } else {
                    $sql = "UPDATE products
                            SET name=:name, sku=:sku, price=:price, stock=:stock,
                                category=:category, description=:description,
                                is_active=:is_active,
                                updated_at=datetime('now')
                            WHERE id=:id";
                }

                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $msg = 'تم حفظ التعديلات.';
            } else {
                // إنشاء
                $stmt = $pdo->prepare("INSERT INTO products
                    (name, sku, price, stock, category, description, image_path, is_active)
                    VALUES(:name, :sku, :price, :stock, :category, :description, :image_path, :is_active)");
                $stmt->execute([
                    ':name' => $name,
                    ':sku' => $sku,
                    ':price' => $price,
                    ':stock' => $stock,
                    ':category' => $category,
                    ':description' => $description,
                    ':image_path' => $newImage,
                    ':is_active' => $is_active
                ]);

                $id = (int)$pdo->lastInsertId();
                $msg = 'تم إنشاء المنتج.';
            }

            // أعد تحميل البيانات بعد الحفظ
            $st = $pdo->prepare("SELECT * FROM products WHERE id=:id");
            $st->execute([':id' => $id]);
            $product = $st->fetch(PDO::FETCH_ASSOC);}

        catch(exc){}
    }}