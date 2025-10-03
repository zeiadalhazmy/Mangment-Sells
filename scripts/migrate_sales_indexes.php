<?php
require_once __DIR__.'/../db.php';
$pdo->exec("CREATE INDEX IF NOT EXISTS idx_orders_created_at ON orders(created_at);");
$pdo->exec("CREATE INDEX IF NOT EXISTS idx_orders_status ON orders(status);");
echo "Indexes created.\n";
