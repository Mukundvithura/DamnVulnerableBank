<?php
require_once __DIR__ . '/../includes/auth.php';

$sent  = false;
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = param($_POST, 'email');

    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $stmt = db()->prepare('SELECT id, full_name FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $found = $stmt->fetch();

        if ($found) {
            // 32 random bytes = 64 hex chars: impossible to guess.
            $token = bin2hex(random_bytes(32));

            // Only one live link at a time: older unused links stop working.
            db()->prepare('UPDATE password_resets SET used = 1 WHERE user_id = ? AND used = 0')->execute([$found['id']]);
            // Store only the HASH of the token, so a leaked DB doesn't leak working links.
            db()->prepare('INSERT INTO password_resets (user_id, token_hash, expires_at) VALUES (?, ?, NOW() + INTERVAL 30 MINUTE)')
                ->execute([$found['id'], hash('sha256', $token)]);

            // APP_URL comes from config, never from the request's Host header.
            $link = APP_URL . BASE_URL . '/auth/reset-password.php?token=' . $token;
            send_mail($email, 'Reset your BankVWA password',
                "Hi {$found['full_name']},\n\nReset your password here (valid for 30 minutes):\n$link\n\nIf you didn't ask for this, ignore this email.");
            audit($found['id'], 'password_reset_requested');
        }
        // ponytail: response time still differs slightly when the email exists; queue the email
        // in the background if timing-based account discovery matters.
    }
    // Same answer either way, so this form can't be used to check who has an account.
    $sent = true;
}

$title = 'Forgot password';
require __DIR__ . '/../includes/header.php';
?>
<div class="auth-card">
    <h1>Reset your password</h1>
    <p class="muted">Enter your email and we will send you a reset link.</p>

    <?php if ($sent): ?>
        <div class="alert success" role="status">If that email is registered, we've sent a reset link. It expires in 30 minutes.</div>
    <?php else: ?>
        <form method="post">
            <label>Email
                <input type="email" name="email" value="<?= e($email) ?>" autocomplete="email" required autofocus>
            </label>
            <button class="btn" type="submit">Send reset link</button>
        </form>
    <?php endif; ?>
    <p class="muted switch"><a href="<?= BASE_URL ?>/auth/login.php">Back to sign in</a></p>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
