<?php
declare(strict_types=1);

namespace App;

use PDO;
use PDOException;

final class Database
{
    private static ?PDO $pdo = null;

    public static function conn(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $dsn = 'sqlite:' . DB_PATH;

        try {
            self::$pdo = new PDO($dsn, null, null, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);

            // تحسينات أداء SQLite
            self::$pdo->exec("PRAGMA journal_mode = WAL;");
            self::$pdo->exec("PRAGMA foreign_keys = ON;");
            self::$pdo->exec("PRAGMA synchronous = NORMAL;");

        } catch (PDOException $e) {
            http_response_code(500);
            exit('DB connection failed: ' . ($GLOBALS['APP_DEBUG'] ? $e->getMessage() : ''));
        }

        return self::$pdo;
    }
}
