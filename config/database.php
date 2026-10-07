<?php
require_once __DIR__ . '/config.php';

// Returns one shared PDO connection (created on first call).
function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // SQL errors throw instead of failing silently
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // rows come back as ['column' => value]
            PDO::ATTR_EMULATE_PREPARES   => false,                  // real server-side prepared statements
        ]);
        $pdo->exec("SET time_zone = '+05:30'");   // Indian Standard Time, matching PHP's timezone

    }
    return $pdo;
}
