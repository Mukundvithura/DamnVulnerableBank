<?php
// Intentionally misconfigured CORS API for the lab. It reflects the caller's
// Origin AND allows credentials — the combination that lets any site the victim
// visits read their data using the victim's own session cookie.
require_once __DIR__ . '/../../includes/auth.php';
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

$stmt = db()->prepare('SELECT u.full_name, u.email, COALESCE(SUM(a.balance), 0) AS total_balance
                       FROM users u LEFT JOIN accounts a ON a.user_id = u.id
                       WHERE u.id = ? GROUP BY u.id');
$stmt->execute([$_SESSION['user_id']]);
echo json_encode($stmt->fetch());
