<?php
// Bank balance API (JSON). Returns the logged-in customer's accounts and balances.
//
// VULN (CORS misconfiguration): it reflects the caller's Origin header back in
// Access-Control-Allow-Origin AND sets Access-Control-Allow-Credentials: true.
// That combination tells the browser "any website may read this response using the
// visitor's cookies", so any site the victim visits while logged in can silently
// pull their balances. The safe version allows only a specific trusted origin.
require_once __DIR__ . '/../includes/auth.php';
header('Content-Type: application/json');

if (isset($_SERVER['HTTP_ORIGIN'])) {
    header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']); // VULN: reflects any origin
    header('Access-Control-Allow-Credentials: true');                  // VULN: + credentials
}

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'not logged in']);
    exit;
}

$stmt = db()->prepare('SELECT u.full_name, a.account_number, a.type, a.balance
                       FROM users u JOIN accounts a ON a.user_id = u.id
                       WHERE u.id = ? ORDER BY a.type');
$stmt->execute([$_SESSION['user_id']]);
$rows = $stmt->fetchAll();

echo json_encode([
    'name'     => $rows[0]['full_name'] ?? null,
    'accounts' => array_map(fn($r) => [
        'account_number' => $r['account_number'],
        'type'           => $r['type'],
        'balance'        => $r['balance'],
    ], $rows),
]);
