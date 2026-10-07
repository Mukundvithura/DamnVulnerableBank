<?php
require_once __DIR__ . '/_lab.php';
$user = require_login();

// THE VULNERABILITY: this demo login tells the attacker WHICH usernames exist
// (a different message for "no such user" vs "wrong password") and applies no
// rate limiting or delay — so usernames can be enumerated and passwords brute
// forced. It only reports what WOULD happen; it does not actually log you in.
$msg = '';
$msgOk = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $u = $_POST['username'] ?? '';
    $p = $_POST['password'] ?? '';
    $stmt = db()->prepare('SELECT password_hash FROM users WHERE username = ?');
    $stmt->execute([$u]);
    $hash = $stmt->fetchColumn();
    if ($hash === false) {
        $msg = 'No account with that username.';            // VULN: confirms the username is free
    } elseif (!password_verify($p, $hash)) {
        $msg = 'Incorrect password for that user.';          // VULN: confirms the username EXISTS
    } else {
        $msg = 'Those credentials are valid. (No lockout and no delay — brute force is cheap.)';
        $msgOk = true;
    }
}
lab_top('auth');
?>
<form method="post" class="card stack narrow">
    <label>Username <input name="username" placeholder="try 'arjun', then 'nobody'"></label>
    <label>Password <input name="password" type="password"></label>
    <button class="btn" type="submit">Log in</button>
</form>
<?php if ($msg): ?><div class="alert <?= $msgOk ? 'success' : '' ?>"><?= e($msg) ?></div><?php endif; ?>
<?php lab_fix('
    <p>Fixes, by weakness:</p>
    <ul>
      <li><strong>User enumeration:</strong> return ONE generic message for every
      failure — <em>"Invalid username or password."</em> — and verify a dummy hash
      when the user is missing so the response time does not give it away.</li>
      <li><strong>Brute force:</strong> rate-limit by username and IP, add a small
      delay, and lock or CAPTCHA after repeated failures.</li>
      <li><strong>Session fixation:</strong> call
      <code>session_regenerate_id(true)</code> on login — the real app already does
      this (see <code>auth/login.php</code>).</li>
    </ul>
'); ?>
<?php lab_bottom(); ?>
