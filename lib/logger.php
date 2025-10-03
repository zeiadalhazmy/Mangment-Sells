<?php
// lib/logger.php
function app_log(string $level, string $message, array $context = []): void {
    $dir = __DIR__ . '/../storage/logs';
    if (!is_dir($dir)) { @mkdir($dir, 0775, true); }
    $file = $dir . '/app.log';

    $entry = [
        'ts'      => date('c'),
        'level'   => strtoupper($level),
        'message' => $message,
        'context' => $context,
        'ip'      => $_SERVER['REMOTE_ADDR'] ?? null,
        'uri'     => $_SERVER['REQUEST_URI'] ?? null,
        'method'  => $_SERVER['REQUEST_METHOD'] ?? null,
        'uid'     => session_id() ?: null,
    ];
    @file_put_contents($file, json_encode($entry, JSON_UNESCAPED_UNICODE) . PHP_EOL, FILE_APPEND);
}

// اختصارات
function log_info(string $msg, array $ctx = []) { app_log('info', $msg, $ctx); }
function log_warn(string $msg, array $ctx = []) { app_log('warn', $msg, $ctx); }
function log_error(string $msg, array $ctx = []) { app_log('error', $msg, $ctx); }
