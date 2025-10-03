<?php
// lib/auth_customer.php
require_once __DIR__ . '/../db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function customer_find_by_email(PDO $pdo, string $email)
{
    $st = $pdo->prepare("SELECT * FROM customers WHERE email = ?");
    $st->execute([$email]);
    return $st->fetch(PDO::FETCH_ASSOC);
}

function customer_find_by_id(PDO $pdo, int $id)
{
    $st = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
    $st->execute([$id]);
    return $st->fetch(PDO::FETCH_ASSOC);
}

function customer_logged_in(): bool
{
    return !empty($_SESSION['customer_id']);
}

function current_customer(PDO $pdo)
{
    return customer_logged_in() ? customer_find_by_id($pdo, (int)$_SESSION['customer_id']) : null;
}

function require_customer_login(PDO $pdo)
{
    if (!customer_logged_in()) {
        header("Location: /customer_login.php?next=" . urlencode($_SERVER['REQUEST_URI'] ?? '/'));
        exit;
    }
}
