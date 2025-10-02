<?php

declare(strict_types=1);
require __DIR__ . '/db.php';
require __DIR__ . '/lib/Cart.php';
Cart::boot();

$action = $_GET['action'] ?? '';
$id     = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($action === 'add' && $id > 0) {
    $stmt = $pdo->prepare("SELECT id, name, price FROM products WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $prod = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($prod) {
        Cart::add($prod, 1);
        header("Location: cart.php");
        exit;
    }
}
if ($action === 'remove' && $id > 0) {
    Cart::remove($id);
    header("Location: cart.php");
    exit;
}
if ($action === 'clear') {
    Cart::clear();
    header("Location: cart.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['qty']) && is_array($_POST['qty'])) {
    foreach ($_POST['qty'] as $pid => $q) {
        Cart::update((int)$pid, (int)$q);
    }
    header("Location: cart.php");
    exit;
}

$items  = Cart::items();
$totals = Cart::totals();
include __DIR__ . '/header.php';
?>
<h1>عربة التسوّق</h1>

<?php if (!$items): ?>
    <p>العربة فارغة.</p>
<?php else: ?>
    <form method="post">
        <table class="table">
            <thead>
                <tr>
                    <th>المنتج</th>
                    <th>السعر</th>
                    <th>الكمية</th>
                    <th>الإجمالي</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $line): ?>
                    <tr>
                        <td><?= htmlspecialchars($line['name']) ?></td>
                        <td><?= number_format($line['price'], 2) ?></td>
                        <td>
                            <input type="number" min="0" name="qty[<?= $line['id'] ?>]" value="<?= $line['qty'] ?>" style="width:80px">
                        </td>
                        <td><?= number_format($line['price'] * $line['qty'], 2) ?></td>
                        <td><a href="cart.php?action=remove&id=<?= $line['id'] ?>">حذف</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <p>المجموع: <?= number_format($totals['subtotal'], 2) ?></p>
        <p>ضريبة (15%): <?= number_format($totals['vat'], 2) ?></p>
        <p>شحن: <?= number_format($totals['shipping'], 2) ?></p>
        <h3>الإجمالي: <?= number_format($totals['grand'], 2) ?></h3>

        <div style="display:flex; gap:10px;">
            <button type="submit">تحديث الكميات</button>
            <a class="btn" href="checkout.php">إتمام الشراء</a>
            <a class="btn btn-danger" href="cart.php?action=clear">تفريغ العربة</a>
        </div>
    </form>
<?php endif; ?>

<?php include __DIR__ . '/footer.php'; ?>