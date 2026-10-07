<?php
require_once __DIR__ . '/../includes/auth.php';

// The token is in the URL: don't leak it to other sites through the Referer header.
header('Referrer-Policy: no-referrer');

$token = param($_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : $_GET, 'token');
$errors = [];

// Find a reset that matches, is unused, and hasn't expired (checked with the DB clock).
$reset = false;
if (preg_match('/^[0-9a-f]{64}$/', $token)) {
    $stmt = db()->prepare('SELECT id, user_id FROM password_resets WHERE token_hash = ? AND used = 0 AND expires_at > NOW()');
    $stmt->execute([hash('sha256', $token)]);
    $reset = $stmt->fetch();
}

if ($reset && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $new     = password_param($_POST, 'new_password');
    $confirm = password_param($_POST, 'confirm_password');

    if (strlen($new) < 8) {
        $errors[] = 'Password must be at least 8 characters.';
    } elseif ($new !== $confirm) {
        $errors[] = 'Passwords do not match.';
    } else {
        $pdo = db();
        $pdo->beginTransaction();
        $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
            ->execute([password_hash($new, PASSWORD_DEFAULT), $reset['user_id']]);
        // Single use: burn this link and any other open ones for the user.
        $pdo->prepare('UPDATE password_resets SET used = 1 WHERE user_id = ?')->execute([$reset['user_id']]);
        audit($reset['user_id'], 'password_reset_completed');
        $pdo->commit();
        redirect('/auth/login.php?reset=1');
    }
}

$title = 'Choose a new password';
require __DIR__ . '/../includes/header.php';
?>
<div class="auth-card">
    <h1>Choose a new password</h1>

    <?php if (!$reset): ?>
        <div class="alert" role="alert">This reset link is invalid, already used, or expired.</div>
        <p class="muted switch"><a href="<?= BASE_URL ?>/auth/forgot-password.php">Request a new link</a></p>
    <?php else: ?>

        <?php foreach ($errors as $error): ?>
            <div class="alert" role="alert"><?= e($error) ?></div>
        <?php endforeach; ?>
        <form method="post">
            <input type="hidden" name="token" value="<?= e($token) ?>">
            <label>New password
                <input type="password" name="new_password" autocomplete="new-password" minlength="8" required autofocus>
            </label>
            <label>Confirm new password
                <input type="password" name="confirm_password" autocomplete="new-password" minlength="8" required>
            </label>
            <button class="btn" type="submit">Set password</button>
        </form>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
