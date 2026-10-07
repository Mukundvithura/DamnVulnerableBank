<?php
// `php labs/setup_labs.php` — adds the lab-only tables and seed data.
// Idempotent and additive: it never touches the real banking tables, so you can
// run it any time without `php setup.php` wiping your accounts.
if (PHP_SAPI !== 'cli') {
    exit('Run this from the command line: php labs/setup_labs.php');
}
require_once __DIR__ . '/../config/database.php';
$pdo = db();

// Stored-XSS guestbook. Comments are saved exactly as typed (that's the vuln).
$pdo->exec("CREATE TABLE IF NOT EXISTS lab_guestbook (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(100) NOT NULL,
    comment    TEXT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB");

if (!$pdo->query('SELECT COUNT(*) FROM lab_guestbook')->fetchColumn()) {
    $pdo->prepare('INSERT INTO lab_guestbook (name, comment) VALUES (?, ?)')
        ->execute(['Arjun Mehta', 'Love the new transfer screen, very fast!']);
}

echo "Lab tables ready.\n";
