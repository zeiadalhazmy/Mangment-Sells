<?php
require __DIR__ . '/db.php';
require __DIR__ . '/lib/Upload.php';

// جلب المنتج عند التعديل
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$product = null;
if ($id) {
    $stmt = $db->prepare('SELECT * FROM products WHERE id = ?');
    $stmt->execute([$id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $desc  = trim($_POST['description'] ?? '');

    if ($title === '')     $errors[] = 'العنوان مطلوب';
    if ($price <= 0)       $errors[] = 'السعر غير صالح';

    // معالجة الصورة (اختيارية)
    $imageBase = $product['image'] ?? null;
    try {
        if (isset($_FILES['image']) && ($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            // حذف القديمة إن وُجدت
            if (!empty($imageBase)) {
                Upload::deleteImage($imageBase);
            }
            $imageBase = Upload::saveImage($_FILES['image']);
        }
    } catch (Throwable $e) {
        $errors[] = 'رفع الصورة: ' . $e->getMessage();
    }

    if (!$errors) {
        if ($id) {
            $stmt = $db->prepare('UPDATE products SET title=?, price=?, description=?, image=? WHERE id=?');
            $stmt->execute([$title, $price, $desc, $imageBase, $id]);
        } else {
            $stmt = $db->prepare('INSERT INTO products (title, price, description, image) VALUES (?,?,?,?)');
            $stmt->execute([$title, $price, $desc, $imageBase]);
            $id = (int)$db->lastInsertId();
        }
        header('Location: admin_products.php?msg=saved');
        exit;
    }
}
?>
<?php include __DIR__ . '/header.php'; ?>
<h2><?= $id ? 'تعديل منتج' : 'إضافة منتج' ?></h2>

<?php if ($errors): ?>
<div class="error">
    <ul><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul>
</div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data">
    <label>العنوان
        <input type="text" name="title" value="<?= htmlspecialchars($product['title'] ?? '') ?>" required>
    </label>

    <label>السعر
        <input type="number" step="0.01" name="price" value="<?= htmlspecialchars($product['price'] ?? '0') ?>" required>
    </label>

    <label>الوصف
        <textarea name="description" rows="4"><?= htmlspecialchars($product['description'] ?? '') ?></textarea>
    </label>

    <label>صورة المنتج (jpg/png/webp/gif – 5MB)
        <input type="file" name="image" accept="image/*">
    </label>

    <?php if (!empty($product['image'])): ?>
        <p>الصورة الحالية:</p>
        <img src="storage/uploads/thumbs/<?= htmlspecialchars($product['image']) ?>" style="max-width:120px;border:1px solid #ddd">
    <?php endif; ?>

    <button type="submit">حفظ</button>
</form>
<?php include __DIR__ . '/footer.php'; ?>
