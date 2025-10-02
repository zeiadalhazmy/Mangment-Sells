<?php

declare(strict_types=1);
require __DIR__ . '/db.php';
require __DIR__ . '/lib/Cart.php';
Cart::boot();

$items  = Cart::items();
$totals = Cart::totals();

if (!$items) {
    header("Location: cart.php");
    exit;
}

include __DIR__ . '/header.php';
?>
<h1>إتمام الشراء</h1>

<form action="place_order.php" method="post">
    <div>
        <label>الاسم الكامل</label>
        <input type="text" name="customer_name" required>
    </div>
    <div>
        <label>البريد الإلكتروني</label>
        <input type="email" name="customer_email" required>
    </div>
    <div>
        <label>رقم الهاتف</label>
        <input type="text" name="customer_phone" required>
    </div>
    <div>
        <label>عنوان الشحن</label>
        <textarea name="shipping_addr" required></textarea>
    </div>

    <h3>ملخص</h3>
    <p>المجموع: <?= number_format($totals['subtotal'], 2) ?></p>
    <p>ضريبة (15%): <?= number_format($totals['vat'], 2) ?></p>
    <p>شحن: <?= number_format($totals['shipping'], 2) ?></p>
    <h3>الإجمالي: <?= number_format($totals['grand'], 2) ?></h3>

    <button type="submit">تأكيد الطلب</button>
</form>

<?php include __DIR__ . '/footer.php'; ?>