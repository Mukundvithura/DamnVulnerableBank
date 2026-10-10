<?php
require_once __DIR__ . '/../includes/auth.php';
$user = require_login();

$errors = ['name' => [], 'password' => []];
$avatar = ['url' => '', 'result' => '', 'done' => false];

// $_REQUEST = GET or POST. The name change stays POST-only; the password change
// deliberately accepts either (see the CSRF note below).
$action = param($_POST, 'action') ?: param($_GET, 'action');

if ($action === 'name' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = param($_POST, 'full_name');
    if (!preg_match('/^.{1,100}$/u', $name)) {
        $errors['name'][] = 'Display name must be 1–100 characters.';
    } else {
        // WHERE id = the session's user: you can only ever edit yourself.
        db()->prepare('UPDATE users SET full_name = ? WHERE id = ?')->execute([$name, $user['id']]);
        audit($user['id'], 'profile_name_changed');
        $_SESSION['flash'] = 'Display name updated.';
        redirect('/banking/profile.php');
    }
}

if ($action === 'password') {
    // VULN (CSRF): no anti-CSRF token, no re-authentication with the current password,
    // and it accepts GET as well as POST. So a forged request from any page the victim
    // visits while logged in — e.g. an auto-submitting form or a link — resets their
    // password and hands the account to the attacker.
    $new     = password_param($_REQUEST, 'new_password');
    $confirm = password_param($_REQUEST, 'confirm_password');

    if (strlen($new) < 8) {
        $errors['password'][] = 'New password must be at least 8 characters.';
    } elseif ($new !== $confirm) {
        $errors['password'][] = 'New passwords do not match.';
    } else {
        db()->prepare('UPDATE users SET password_hash = ?, password = ? WHERE id = ?')
            ->execute([password_hash($new, PASSWORD_DEFAULT), $new, $user['id']]);
        audit($user['id'], 'password_changed');
        $_SESSION['flash'] = 'Password changed.';
        redirect('/banking/profile.php');
    }
}

if ($action === 'avatar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $avatar['url'] = param($_POST, 'avatar_url');
    // VULN (SSRF): the server fetches whatever URL the user supplies, with no allowlist
    // of scheme or host. It can be aimed at internal services the user's own browser
    // can't reach — the DB on 127.0.0.1:3307, cloud metadata (169.254.169.254), file://, etc.
    if ($avatar['url'] !== '') {
        $ctx = stream_context_create(['http' => ['timeout' => 4, 'ignore_errors' => true]]);
        $body = @file_get_contents($avatar['url'], false, $ctx);
        $avatar['result'] = $body === false ? '(could not fetch that URL)' : substr($body, 0, 2000);
        $avatar['done'] = true;
    }
}

$stmt = db()->prepare('SELECT username, email, full_name, created_at FROM users WHERE id = ?');
$stmt->execute([$user['id']]);
$profile = $stmt->fetch();

$title = 'Profile';
require __DIR__ . '/../includes/header.php';
?>
<h1>Profile</h1>

<section class="card">
    <dl class="details">
        <div><dt>Username</dt><dd><?= e($profile['username']) ?></dd></div>
        <div><dt>Email</dt><dd><?= e($profile['email']) ?></dd></div>
        <div><dt>Customer since</dt><dd><?= e(date('d M Y', strtotime($profile['created_at']))) ?></dd></div>
    </dl>
</section>

<form class="card stack narrow" method="post">
    <h2>Display Name</h2>
    <?php foreach ($errors['name'] as $error): ?>
        <div class="alert" role="alert"><?= e($error) ?></div>
    <?php endforeach; ?>
    <input type="hidden" name="action" value="name">
    <label>Full name
        <input name="full_name" value="<?= e($profile['full_name']) ?>" maxlength="100" autocomplete="name" required>
    </label>
    <button class="btn" type="submit">Save name</button>
</form>

<form class="card stack narrow" method="post">
    <h2>Change Password</h2>
    <?php foreach ($errors['password'] as $error): ?>
        <div class="alert" role="alert"><?= e($error) ?></div>
    <?php endforeach; ?>
    <input type="hidden" name="action" value="password">
    <label>New password
        <input type="password" name="new_password" autocomplete="new-password" minlength="8" required>
    </label>
    <label>Confirm new password
        <input type="password" name="confirm_password" autocomplete="new-password" minlength="8" required>
    </label>
    <button class="btn" type="submit">Change password</button>
</form>
<form class="card stack narrow" method="post">
    <h2>Profile Picture</h2>
    <input type="hidden" name="action" value="avatar">
    <label>Image URL
        <input name="avatar_url" value="<?= e($avatar['url']) ?>" placeholder="https://example.com/me.png">
    </label>
    <button class="btn" type="submit">Fetch picture</button>
    <p class="muted hint">The bank's server downloads the image for you.</p>
</form>
<?php if ($avatar['done']): ?>
    <section class="card">
        <h2>Server fetched this:</h2>
        <pre><?= e($avatar['result']) ?></pre>
    </section>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
