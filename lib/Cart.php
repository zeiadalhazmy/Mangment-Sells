<?php

declare(strict_types=1);

class Cart
{
    public static function boot(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        $_SESSION['cart'] ??= []; // [product_id => ['id'=>..,'name'=>..,'price'=>..,'qty'=>..]]
    }

    public static function items(): array
    {
        self::boot();
        return $_SESSION['cart'];
    }

    public static function add(array $product, int $qty = 1): void
    {
        self::boot();
        $id = (int)$product['id'];
        if (!isset($_SESSION['cart'][$id])) {
            $_SESSION['cart'][$id] = [
                'id'    => $id,
                'name'  => $product['name'],
                'price' => (float)$product['price'],
                'qty'   => 0,
            ];
        }
        $_SESSION['cart'][$id]['qty'] += max(1, $qty);
    }

    public static function update(int $id, int $qty): void
    {
        self::boot();
        if (isset($_SESSION['cart'][$id])) {
            if ($qty <= 0) unset($_SESSION['cart'][$id]);
            else $_SESSION['cart'][$id]['qty'] = $qty;
        }
    }

    public static function remove(int $id): void
    {
        self::boot();
        unset($_SESSION['cart'][$id]);
    }

    public static function clear(): void
    {
        self::boot();
        $_SESSION['cart'] = [];
    }

    public static function totals(): array
    {
        self::boot();
        $subtotal = 0;
        foreach ($_SESSION['cart'] as $line) {
            $subtotal += $line['price'] * $line['qty'];
        }
        $vat = round($subtotal * 0.15, 2);        // 15% مثال
        $shipping = ($subtotal > 0) ? 10.0 : 0.0; // ثابت كمثال
        $grand = $subtotal + $vat + $shipping;
        return compact('subtotal', 'vat', 'shipping', 'grand');
    }
}
