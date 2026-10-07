<?php
require_once __DIR__ . '/../includes/auth.php';
$user = require_login();

$errors = ['name' => [], 'password' => []];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = param($_POST, 'action');

    if ($action === 'name') {
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
        $current = password_param($_POST, 'current_password');
        $new     = password_param($_POST, 'new_password');
        $confirm = password_param($_POST, 'confirm_password');

        $stmt = db()->prepare('SELECT password_hash FROM users WHERE id = ?');
        $stmt->execute([$user['id']]);
        $hash = $stmt->fetchColumn();

        // Re-authenticate: being logged in isn't enough to change the password.
        if (!password_verify($current, $hash)) {
            $errors['password'][] = 'Current password is incorrect.';
            audit($user['id'], 'password_change_failed');
        } elseif (strlen($new) < 8) {
            $errors['password'][] = 'New password must be at least 8 characters.';
        } elseif ($new !== $confirm) {
            $errors['password'][] = 'New passwords do not match.';
        } elseif (password_verify($new, $hash)) {
            $errors['password'][] = 'New password must be different from the current one.';
        } else {
            db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
                ->execute([password_hash($new, PASSWORD_DEFAULT), $user['id']]);
            // ponytail: only this session gets a new ID; logging out OTHER devices needs a
            // per-user session version stored in the DB and checked in require_login().
            session_regenerate_id(true);
            audit($user['id'], 'password_changed');
            $_SESSION['flash'] = 'Password changed.';
            redirect('/banking/profile.php');
        }
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
    <label>Current password
        <input type="password" name="current_password" autocomplete="current-password" required>
    </label>
    <label>New password
        <input type="password" name="new_password" autocomplete="new-password" minlength="8" required>
    </label>
    <label>Confirm new password
        <input type="password" name="confirm_password" autocomplete="new-password" minlength="8" required>
    </label>
    <button class="btn" type="submit">Change password</button>
</form>
<?php require __DIR__ . '/../includes/footer.php'; ?>
