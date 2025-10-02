<?php
// lib/upload.php
declare(strict_types=1);

/**
 * Image Upload Helper (GD-based)
 * - يتحقق من نوع الملف وحجمه
 * - يغيّر الاسم لاسم عشوائي آمن
 * - ينشئ مصغّر thumbnail
 */

class Upload
{
    const MAX_SIZE = 5 * 1024 * 1024; // 5MB
    const ALLOWED  = ['image/jpeg','image/png','image/webp','image/gif'];

    public static function uploadDir(): string {
        return __DIR__ . '/../storage/uploads';
    }

    public static function thumbsDir(): string {
        return self::uploadDir() . '/thumbs';
    }

    public static function ensureDirs(): void {
        foreach ([self::uploadDir(), self::thumbsDir()] as $d) {
            if (!is_dir($d)) {
                mkdir($d, 0755, true);
            }
        }
    }

    public static function saveImage(array $file): ?string
    {
        self::ensureDirs();

        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return null; // لا يوجد ملف
        }

        // حجم
        if (($file['size'] ?? 0) === 0 || $file['size'] > self::MAX_SIZE) {
            throw new RuntimeException('حجم الصورة غير مسموح (حتى 5MB).');
        }

        // نوع حقيقي عبر finfo
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file($file['tmp_name']) ?: 'application/octet-stream';
        if (!in_array($mime, self::ALLOWED, true)) {
            throw new RuntimeException('نوع الملف غير مسموح.');
        }

        // امتداد حسب المايم
        $ext = match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
            'image/gif'  => 'gif',
            default      => 'bin'
        };

        $base = bin2hex(random_bytes(8)) . '.' . $ext;
        $dest = self::uploadDir() . '/' . $base;

        // قراءة الصورة إلى GD
        $im = self::imageCreateFrom($file['tmp_name'], $mime);
        if (!$im) {
            throw new RuntimeException('فشل في قراءة الصورة.');
        }

        // تصغير إلى حدود 1280x1280
        $resized = self::resizeToMax($im, 1280, 1280);
        self::imageOutput($resized, $dest, $mime);

        // thumbnail 300x300
        $thumb = self::resizeCover($resized, 300, 300);
        self::imageOutput($thumb, self::thumbsDir() . '/' . $base, $mime);

        imagedestroy($im);
        imagedestroy($resized);
        imagedestroy($thumb);

        return $base;
    }

    public static function deleteImage(?string $base): void
    {
        if (!$base) return;
        @unlink(self::uploadDir() . '/' . $base);
        @unlink(self::thumbsDir() . '/' . $base);
    }

    /* ------------ أدوات GD ------------ */

    private static function imageCreateFrom(string $path, string $mime)
    {
        return match ($mime) {
            'image/jpeg' => imagecreatefromjpeg($path),
            'image/png'  => imagecreatefrompng($path),
            'image/webp' => function_exists('imagecreatefromwebp') ? imagecreatefromwebp($path) : null,
            'image/gif'  => imagecreatefromgif($path),
            default      => null
        };
    }

    private static function imageOutput($im, string $dest, string $mime): void
    {
        switch ($mime) {
            case 'image/jpeg': imagejpeg($im, $dest, 85); break;
            case 'image/png':  imagepng($im,  $dest, 6);  break;
            case 'image/webp':
                if (function_exists('imagewebp')) { imagewebp($im, $dest, 85); }
                else { imagejpeg($im, $dest, 85); }
                break;
            case 'image/gif':  imagegif($im,  $dest); break;
            default: imagejpeg($im, $dest, 85);
        }
        @chmod($dest, 0644);
    }

    private static function resizeToMax($im, int $maxW, int $maxH)
    {
        $w = imagesx($im); $h = imagesy($im);
        $ratio = min($maxW / $w, $maxH / $h, 1);
        $nw = (int)($w * $ratio); $nh = (int)($h * $ratio);

        $dst = imagecreatetruecolor($nw, $nh);
        imagealphablending($dst, false); imagesavealpha($dst, true);
        imagecopyresampled($dst, $im, 0,0, 0,0, $nw,$nh, $w,$h);
        return $dst;
    }

    // قصّ لتغطية أبعاد ثابتة (مصغّر مربّع)
    private static function resizeCover($im, int $tw, int $th)
    {
        $w = imagesx($im); $h = imagesy($im);
        $srcRatio = $w / $h; $dstRatio = $tw / $th;

        if ($srcRatio > $dstRatio) {
            // قص من العرض
            $newW = (int)($h * $dstRatio);
            $x = (int)(($w - $newW) / 2);
            $y = 0; $sw = $newW; $sh = $h;
        } else {
            // قص من الارتفاع
            $newH = (int)($w / $dstRatio);
            $x = 0; $y = (int)(($h - $newH) / 2);
            $sw = $w; $sh = $newH;
        }

        $dst = imagecreatetruecolor($tw, $th);
        imagealphablending($dst, false); imagesavealpha($dst, true);
        imagecopyresampled($dst, $im, 0,0, $x,$y, $tw,$th, $sw,$sh);
        return $dst;
    }
}


/*
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

/* يحذف ملف صورة قديم إن وجد (مسار نسبي داخل المشروع) */
function delete_product_image(?string $relativePath): void
{
    if (!$relativePath) return;
    $absolute = DIR . '/../' . ltrim($relativePath, '/');
    if (is_file($absolute)) {
        @unlink($absolute);
    }
}