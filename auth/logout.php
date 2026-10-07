<?php
require_once __DIR__ . '/../includes/auth.php';

// Logout changes state, so it only accepts POST (the navbar button).
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/dashboard/');
}

$_SESSION = [];                                   // 1. empty the session data
$p = session_get_cookie_params();                 // 2. expire the cookie in the browser
setcookie(session_name(), '', time() - 3600, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
session_destroy();                                // 3. delete the session file on the server

redirect('/auth/login.php');
