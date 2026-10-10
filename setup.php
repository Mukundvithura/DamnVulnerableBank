<?php
// `php setup.php`             rebuilds the database and loads fictional sample data (wipes everything).
// `php setup.php --if-empty`  only does that if the tables don't exist yet (used by Docker on start).
if (PHP_SAPI !== 'cli') {
    exit('Run this from the command line: php setup.php');
}
require_once __DIR__ . '/config/config.php';

// Connect without a database selected, because database.sql creates it.
$pdo = new PDO('mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';charset=utf8mb4', DB_USER, DB_PASS, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);

if (in_array('--if-empty', $argv, true)) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = ? AND table_name = 'users'");
    $stmt->execute([DB_NAME]);
    if ($stmt->fetchColumn()) {
        echo "Database already set up, keeping existing data.\n";
        exit;
    }
}
$pdo->exec("SET time_zone = '+05:30'");   // same clock as the app (see config/database.php)
$pdo->exec(file_get_contents(__DIR__ . '/database.sql'));
$pdo->exec('USE ' . DB_NAME);

// Fictional people only. [username, email, full name, password, role, accounts]
$users = [
    ['arjun', 'arjun@bankvwa.local', 'Arjun Mehta', 'Bank@1234', 'customer', [
        ['100200300401', 'savings', 62500.00, [
            [5000, 'Salary credit', 1], [-1250, 'Amazon purchase', 2],
            [-500, 'Transfer to Priya Nair', 3], [2000, 'Flipkart refund', 4],
        ]],
        ['100200300402', 'current', 21750.00, [
            [-899, 'Electricity bill', 5], [-350, 'Swiggy order', 6],
        ]],
    ]],
    ['priya', 'priya@bankvwa.local', 'Priya Nair', 'Bank@1234', 'customer', [
        ['100200300501', 'savings', 145000.50, [
            [500, 'Transfer from Arjun Mehta', 3], [-2400, 'Mobile recharge', 7],
        ]],
    ]],
    ['admin', 'admin@bankvwa.local', 'Bank Administrator', 'Admin@1234', 'admin', []],
];

$insUser = $pdo->prepare('INSERT INTO users (username, email, full_name, password_hash, password, role) VALUES (?, ?, ?, ?, ?, ?)');
$insAcct = $pdo->prepare('INSERT INTO accounts (user_id, account_number, type, balance) VALUES (?, ?, ?, ?)');
$insTxn  = $pdo->prepare('INSERT INTO transactions (account_id, amount, description, created_at)
                          VALUES (?, ?, ?, NOW() - INTERVAL ? DAY)');

foreach ($users as [$username, $email, $name, $password, $role, $accounts]) {
    $insUser->execute([$username, $email, $name, password_hash($password, PASSWORD_DEFAULT), $password, $role]);
    $userId = $pdo->lastInsertId();
    foreach ($accounts as [$number, $type, $balance, $txns]) {
        $insAcct->execute([$userId, $number, $type, $balance]);
        $accountId = $pdo->lastInsertId();
        foreach ($txns as [$amount, $desc, $daysAgo]) {
            $insTxn->execute([$accountId, $amount, $desc, $daysAgo]);
        }
    }
    echo "Created user: $username / $password\n";
}

// A previous sign-in for each customer, so "Last login" has something to show on first use.
$pdo->exec("INSERT INTO audit_logs (user_id, action, details, ip_address, created_at)
            SELECT id, 'login', '', '127.0.0.1', NOW() - INTERVAL 1 DAY - INTERVAL 3 HOUR FROM users WHERE role = 'customer'");

// Arjun has Priya saved as a payee.
$pdo->exec("INSERT INTO beneficiaries (user_id, name, account_number, nickname)
            SELECT u.id, 'Priya Nair', '100200300501', 'Priya (flatmate)' FROM users u WHERE u.username = 'arjun'");

echo "BankVWA database ready.\n";
