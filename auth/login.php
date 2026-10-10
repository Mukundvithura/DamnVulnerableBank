<?php
require_once __DIR__ . '/../includes/auth.php';

if (!empty($_SESSION['user_id'])) {
    redirect('/dashboard/');
}

$error = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = param($_POST, 'username');
    $password = password_param($_POST, 'password');

    // VULN (SQL injection): username and password are concatenated straight into the
    // query, so a returned row means "authenticated". Classic bypasses:
    //   username:  admin' --            (logs in as admin, any password)
    //   username:  ' OR '1'='1' --      (logs in as the first user)
    // VULN (broken auth): no rate limiting / lockout, and no session_regenerate_id()
    // after login, so the session is open to fixation.
    $sql = "SELECT id FROM users WHERE username = '$username' AND password = '$password'";
    $found = db()->query($sql)->fetch();

    if ($found) {
        $_SESSION['user_id'] = $found['id'];
        audit($found['id'], 'login');
        redirect('/dashboard/');
    }
    $error = 'Invalid username or password.';
}

$title = 'Sign in';
require __DIR__ . '/../includes/header.php';
?>
<div class="auth-card">
    <h1>Welcome back</h1>
    <p class="muted">Sign in to your BankVWA account.</p>

    <?php if (isset($_GET['registered'])): ?>
        <div class="alert success" role="status">Account created. Please sign in.</div>
    <?php endif; ?>
    <?php if (isset($_GET['reset'])): ?>
        <div class="alert success" role="status">Password updated. Please sign in.</div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert" role="alert"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="post">
        <label>Username
            <input name="username" value="<?= e($username) ?>" autocomplete="username" required autofocus>
        </label>
        <label>Password
            <input type="password" name="password" autocomplete="current-password" required>
        </label>
        <button class="btn" type="submit">Sign in</button>
    </form>
    <p class="muted switch"><a href="<?= BASE_URL ?>/auth/forgot-password.php">Forgot password?</a></p>
    <p class="muted switch">New to BankVWA? <a href="<?= BASE_URL ?>/auth/register.php">Open an account</a></p>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
