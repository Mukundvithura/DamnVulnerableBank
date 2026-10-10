<?php
// Included at the top of every web page: DB access, session, and login helpers.
require_once __DIR__ . '/../config/database.php';

session_set_cookie_params([
    'httponly' => false,  // VULN: JS CAN read the session cookie (document.cookie), so XSS can steal PHPSESSID
    'samesite' => 'None', // VULN: cookie is sent on cross-site requests too, enabling cross-site CORS theft and CSRF
    'secure'   => true,   // required for SameSite=None; browsers accept Secure cookies on localhost even over http
]);
session_start();

// Escape text before putting it into HTML.
function e(string $text): string
{
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

// Read one text field from $_GET/$_POST. Returns '' if missing or not a string
// (e.g. ?q[]=x sends an array, which would otherwise crash trim() with a 500 error).
function param(array $source, string $key): string
{
    $value = $source[$key] ?? '';
    return is_string($value) ? trim($value) : '';
}

// Read a password field: like param() but NOT trimmed (spaces are valid in passwords).
function password_param(array $source, string $key): string
{
    $value = $source[$key] ?? '';
    return is_string($value) ? $value : '';
}

// Record a security-relevant event (who, what, from where).
function audit(?int $userId, string $action, string $details = ''): void
{
    db()->prepare('INSERT INTO audit_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)')
        ->execute([$userId, $action, $details, $_SERVER['REMOTE_ADDR'] ?? null]);
}

// ponytail: dev "mailer" that appends to a log file outside the web root; swap for SMTP when deploying.
function send_mail(string $to, string $subject, string $body): void
{
    $entry = "To: $to\nSubject: $subject\nDate: " . date('c') . "\n\n$body\n" . str_repeat('-', 60) . "\n";
    file_put_contents(sys_get_temp_dir() . '/bankvwa-mail.log', $entry, FILE_APPEND | LOCK_EX);
}

// Indian grouping: 1250000.5 -> "₹12,50,000.50" (last 3 digits, then pairs).
// The sign is shown separately by the caller, so this always formats the absolute value.
function money(float $amount): string
{
    [$whole, $paise] = explode('.', number_format(abs($amount), 2, '.', ''));
    if (strlen($whole) > 3) {
        $whole = preg_replace('/\B(?=(\d{2})+$)/', ',', substr($whole, 0, -3)) . ',' . substr($whole, -3);
    }
    return '₹' . $whole . '.' . $paise;
}

// Masked account number as HTML: shows "•••• 0401", screen readers hear "ending in 0401".
function masked(string $accountNumber): string
{
    return '<span aria-hidden="true">•••• </span><span class="sr-only">ending in </span>' . e(substr($accountNumber, -4));
}

function redirect(string $path): never
{
    header('Location: ' . BASE_URL . $path);
    exit;
}

// Call at the top of protected pages. Returns the logged-in user or redirects to login.
function require_login(): array
{
    if (empty($_SESSION['user_id'])) {
        redirect('/auth/login.php');
    }
    $stmt = db()->prepare('SELECT id, username, full_name, role FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    if (!$user) {               // user was deleted while logged in
        session_destroy();
        redirect('/auth/login.php');
    }
    header('Cache-Control: no-store'); // so the Back button can't show the page after logout
    return $user;
}

// Call at the top of every admin page. The role comes from the database, never from the browser.
function require_admin(): array
{
    $user = require_login();
    if ($user['role'] !== 'admin') {
        audit($user['id'], 'admin_access_denied', $_SERVER['REQUEST_URI'] ?? '');
        http_response_code(403);
        exit('403 Forbidden: administrators only.');
    }
    return $user;
}
