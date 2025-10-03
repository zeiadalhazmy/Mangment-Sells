<?php
// lib/payments.php
require_once __DIR__.'/../db.php'; // $pdo
require_once __DIR__.'/../vendor/autoload.php'; // stripe-php

// يمكن وضع القيم في متغيرات بيئة أو هنا مؤقتاً للتجربة:
$stripeSecret = getenv('STRIPE_SECRET_KEY') ?: 'sk_test_xxx';
$stripeWebhook = getenv('STRIPE_WEBHOOK_SECRET') ?: 'whsec_xxx';

\Stripe\Stripe::setApiKey($stripeSecret);

/**
 * ضمان وجود أعمدة الدفع في جدول orders
 * (SQLite يسمح ADD COLUMN بدون خطورة – idempotent)
 */
$pdo->exec("ALTER TABLE orders ADD COLUMN payment_provider TEXT DEFAULT NULL");
$pdo->exec("ALTER TABLE orders ADD COLUMN payment_ref TEXT DEFAULT NULL");
$pdo->exec("ALTER TABLE orders ADD COLUMN payment_status TEXT DEFAULT NULL");

/**
 * أنشئ Session لِـ Stripe Checkout لطلب معيّن
 * @return array [url, session_id]
 */
function createStripeCheckoutForOrder(PDO $pdo, int $orderId): array {
    $order = $pdo->query("SELECT * FROM orders WHERE id=$orderId")->fetch(PDO::FETCH_ASSOC);
    if (!$order) throw new Exception("Order not found");

    // عناصر الطلب لتغذية Checkout (اسم/سعر/كمية – هنا نستخدم إجمالي واحد)
    $amountCents = (int) round(((float)$order['total']) * 100); // بوحدة السنت

    // URL العودة/الإلغاء
    $base = baseUrl(); // سنعرّفه بالأسفل
    $successUrl = $base . "/payments/return.php?order_id={$orderId}&session_id={CHECKOUT_SESSION_ID}";
    $cancelUrl  = $base . "/payments/cancel.php?order_id={$orderId}";

    $session = \Stripe\Checkout\Session::create([
        'mode' => 'payment',
        'success_url' => $successUrl,
        'cancel_url'  => $cancelUrl,
        'line_items' => [[
            'price_data' => [
                'currency'     => 'usd',
                'unit_amount'  => $amountCents,
                'product_data' => ['name' => "Order #{$orderId}"],
            ],
            'quantity' => 1,
        ]],
        'metadata' => [ 'order_id' => (string)$orderId ],
    ]);

    // تسجيل معلومات الدفع على الطلب
    $stmt = $pdo->prepare("UPDATE orders SET payment_provider='stripe', payment_ref=?, payment_status='pending' WHERE id=?");
    $stmt->execute([$session->id, $orderId]);

    return ['url' => $session->url, 'session_id'=>$session->id];
}

/** Helper: استنتاج Base URL للتطبيق */
function baseUrl(): string {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost:8080';
    $dir    = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');
    // نرجع جذر المشروع (ملفنا ضمن lib/)
    return $scheme.'://'.$host . str_replace('/lib','', $dir);
}

/** توقيع الويب هوك – سترجعه للمُرسِل */
function stripeWebhookSecret(): string {
    return getenv('STRIPE_WEBHOOK_SECRET') ?: 'whsec_xxx';
}
