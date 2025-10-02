<?php
declare(strict_types=1);
require __DIR__ . '/db.php';
require __DIR__ . '/lib/Cart.php';
Cart::boot();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: cart.php');
    exit;
}

$items  = Cart::items();
$totals = Cart::totals();

if (!$items) {
    header('Location: cart.php');
    exit;
}

$name  = trim($_POST['customer_name'] ?? '');
$email = trim($_POST['customer_email'] ?? '');
$phone = trim($_POST['customer_phone'] ?? '');
$addr  = trim($_POST['shipping_addr'] ?? '');

if ($name === ''  || $email === '' || $phone === '' || $addr === '') {
    exit('الرجاء تعبئة كل الحقول المطلوبة.');
}

$pdo->beginTransaction();
try {
    // 1) إنشاء سجل الطلب
    $stmt = $pdo->prepare("
      INSERT INTO orders (customer_name, customer_email, customer_phone, shipping_addr,
                          subtotal, vat, shipping_cost, grand_total, status)
      VALUES (:n, :e, :p, :a, :sub, :vat, :ship, :grand, 'placed')
    ");
    $stmt->execute([
        ':n'    => $name,
        ':e'    => $email,
        ':p'    => $phone,
        ':a'    => $addr,
        ':sub'  => $totals['subtotal'],
        ':vat'  => $totals['vat'],
        ':ship' => $totals['shipping'],
        ':grand'=> $totals['grand'],
    ]);
    $orderId = (int)$pdo->lastInsertId();

    // 2) تفاصيل الأصناف
    $itemStmt = $pdo->prepare("
      INSERT INTO order_items (order_id, product_id, product_name, unit_price, qty, line_total)
      VALUES (:oid, :pid, :name, :price, :qty, :total)
    ");

    foreach ($items as $line) {
        $itemStmt->execute([
            ':oid'   => $orderId,
            ':pid'   => $line['id'],
            ':name'  => $line['name'],
            ':price' => $line['price'],
            ':qty'   => $line['qty'],
            ':total' => $line['price'] * $line['qty'],
        ]);
    }

    $pdo->commit();
    Cart::clear();

    header("Location: order_success.php?id={$orderId}");
    exit;
} catch (Throwable $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo "خطأ أثناء إنشاء الطلب: " . htmlspecialchars($e->getMessage());
}