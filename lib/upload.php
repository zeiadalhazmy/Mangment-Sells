<?php
// lib/upload.php
declare(strict_types=1);

/
 * يرفع صورة المنتج إلى /uploads/products ويعيد المسار النسبي (string)
 * أو يعيد null إذا لم يُرفع شيء.
 * يرمي Exception عند فشل التحقق.
 */
function upload_product_image(?array $file): ?string
{
    if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null; // لا توجد صورة
    }
    if (!isset($file['tmp_name'], $file['name'], $file['error'])) {
        throw new RuntimeException('ملف الرفع غير صالح.');
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('فشل الرفع (كود: ' . $file['error'] . ').');
    }

    // تحقّق الحجم (مثال: 3MB)
    if (($file['size'] ?? 0) > 3 * 1024 * 1024) {
        throw new RuntimeException('أقصى حجم للصورة 3MB.');
    }

    // تحقّق النوع
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($file['tmp_name']);
    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];
    if (!isset($allowed[$mime])) {
        throw new RuntimeException('يُسمح بـ JPG/PNG/WEBP فقط.');
    }

    // مجلد الرفع
    $baseDir = __DIR__ . '/../uploads/products';
    if (!is_dir($baseDir)) {
        if (!mkdir($baseDir, 0775, true) && !is_dir($baseDir)) {
            throw new RuntimeException('تعذر إنشاء مجلد الرفع.');
        }
    }

    // اسم فريد
    $ext = $allowed[$mime];
    $safeName = bin2hex(random_bytes(8)) . '.' . $ext;

    $dest = $baseDir . '/' . $safeName;
    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        throw new RuntimeException('تعذر حفظ الملف المرفوع.');
    }

    // المسار النسبي الذي سيخزن في DB
    return 'uploads/products/' . $safeName;
}

/ يحذف ملف صورة قديم إن وجد (مسار نسبي داخل المشروع) */
function delete_product_image(?string $relativePath): void
{
    if (!$relativePath) return;
    $absolute = DIR . '/../' . ltrim($relativePath, '/');
    if (is_file($absolute)) {
        @unlink($absolute);
    }
}