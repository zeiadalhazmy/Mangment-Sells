<?php

declare(strict_types=1);

require __DIR__ . '/db.php';                 // يجب أن ينشئ $pdo = new PDO('sqlite:.../store.sqlite')
$sql = file_get_contents(__DIR__ . '/scripts/20241002_orders.sql');
$pdo->exec($sql);
echo "orders & order_items migrated.\n";
