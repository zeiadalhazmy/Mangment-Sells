<?php
declare(strict_types=1);

require __DIR__ . '/../lib/bootstrap.php';

use App\Database;

$pdo = Database::conn();

$pdo->exec("
CREATE TABLE IF NOT EXISTS users (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  username TEXT UNIQUE NOT NULL,
  password TEXT NOT NULL,
  is_admin INTEGER NOT NULL DEFAULT 0,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS products (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT NOT NULL,
  price REAL NOT NULL,
  image TEXT,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS orders (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  customer_name TEXT,
  total REAL NOT NULL,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);
");

echo "✓ Tables ensured.\n";

// إنشاء مدير افتراضي مرة واحدة
$admin = $_ENV['ADMIN_USER'] ?? null;
$hash  = $_ENV['ADMIN_PASS_HASH'] ?? null;

if ($admin && $hash) {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = :u LIMIT 1;");
    $stmt->execute([':u' => $admin]);
    if (!$stmt->fetch()) {
        $stmt = $pdo->prepare("INSERT INTO users(username, password, is_admin) VALUES (:u,:p,1)");
        $stmt->execute([':u' => $admin, ':p' => $hash]);
        echo "✓ Default admin created: {$admin}\n";
    } else {
        echo "… Admin already exists: {$admin}\n";
    }
} else {
    echo "! Skipped admin seed (ADMIN_USER / ADMIN_PASS_HASH not set).\n";
}
