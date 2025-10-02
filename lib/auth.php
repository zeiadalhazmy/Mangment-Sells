<?php
// lib/auth.php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function auth_db(): PDO {
    // يعتمد على db.php الذي يرجّع $pdo جاهز
    require_once DIR . '/../db.php';
    /** @var PDO $pdo */
    return $pdo;
}

function auth_is_logged_in(): bool {
    return !empty($_SESSION['admin_id']);
}

function auth_current_admin(): ?array {
    if (!auth_is_logged_in()) return null;
    $pdo = auth_db();
    $stmt = $pdo->prepare("SELECT id, username, created_at FROM admins WHERE id = :id");
    $stmt->execute([':id' => $_SESSION['admin_id']]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

function auth_is_locked(string $username, int $maxAttempts = 5, int $windowMinutes = 15): bool {
    $pdo = auth_db();
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM failed_logins WHERE username = :u AND attempt_at >= datetime('now', :win)");
    $stmt->execute([':u' => $username, ':win' => sprintf('-%d minutes', $windowMinutes)]);
    return (int)$stmt->fetchColumn() >= $maxAttempts;
}

function auth_record_failed(string $username): void {
    $pdo = auth_db();
    $stmt = $pdo->prepare("INSERT INTO failed_logins (username, attempt_at) VALUES (:u, datetime('now'))");
    $stmt->execute([':u' => $username]);
}

function auth_reset_failures(string $username): void {
    $pdo = auth_db();
    $stmt = $pdo->prepare("DELETE FROM failed_logins WHERE username = :u");
    $stmt->execute([':u' => $username]);
}

function auth_login(string $username, string $password): bool {
    $pdo = auth_db();

    if (auth_is_locked($username)) {
        return false; // مقفول مؤقتًا
    }

    $stmt = $pdo->prepare("SELECT id, username, password_hash FROM admins WHERE username = :u");
    $stmt->execute([':u' => $username]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row && password_verify($password, $row['password_hash'])) {
        auth_reset_failures($username);
        $_SESSION['admin_id'] = (int)$row['id'];
        return true;
    }

    // فشل
    auth_record_failed($username);
    return false;
}

function auth_logout(): void {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

function auth_require_admin(): void {
    if (!auth_is_logged_in()) {
        header('Location: /login.php?next=' . urlencode($_SERVER['REQUEST_URI'] ?? '/admin.php'));
        exit;
    }
}

function auth_change_password(int $adminId, string $old, string $new): bool {
    $pdo = auth_db();
    $stmt = $pdo->prepare("SELECT password_hash FROM admins WHERE id = :id");
    $stmt->execute([':id' => $adminId]);
    $hash = $stmt->fetchColumn();

    if (!$hash || !password_verify($old, (string)$hash)) {
        return false;
    }

    $newHash = password_hash($new, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE admins SET password_hash = :h WHERE id = :id");
    return $stmt->execute([':h' => $newHash, ':id' => $adminId]);
}