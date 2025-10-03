<?php
require_once __DIR__ . '/../db.php';

// customers table
$pdo->exec("
CREATE TABLE IF NOT EXISTS customers (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT NOT NULL,
  email TEXT NOT NULL UNIQUE,
  password_hash TEXT NOT NULL,
  created_at TEXT NOT NULL DEFAULT (datetime('now'))
);
");

// add customer_id to orders if not exists
$pdo->exec("ALTER TABLE orders ADD COLUMN customer_id INTEGER NULL;");

// index for performance
$pdo->exec("CREATE INDEX IF NOT EXISTS idx_orders_customer_id ON orders(customer_id);");

echo "Migration done.\n";
