<?php
require_once __DIR__ . '/../includes/auth.php';

if (!empty($_SESSION['user_id'])) {
    redirect('/dashboard/');
}

$errors = [];
$form = ['username' => '', 'full_name' => '', 'email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($form as $field => $_) {
        $form[$field] = param($_POST, $field);
    }
    $password = password_param($_POST, 'password');
    $confirm  = password_param($_POST, 'confirm');

    // Server-side validation: the browser's checks can be skipped, these can't.
    if (!preg_match('/^[a-zA-Z0-9_]{3,30}$/', $form['username'])) {
        $errors[] = 'Username must be 3–30 letters, numbers or underscores.';
    }
    if (!preg_match('/^.{1,100}$/u', $form['full_name'])) {   // 1–100 characters (/u = count Unicode chars, not bytes)
        $errors[] = 'Please enter your full name.';
    }
    if (!filter_var($form['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters.';
    }
    if ($password !== $confirm) {
        $errors[] = 'Passwords do not match.';
    }

    if (!$errors) {
        $pdo = db();
        try {
            // Both inserts succeed together or neither happens.
            $pdo->beginTransaction();

            $stmt = $pdo->prepare('INSERT INTO users (username, email, full_name, password_hash) VALUES (?, ?, ?, ?)');
            $stmt->execute([$form['username'], $form['email'], $form['full_name'], password_hash($password, PASSWORD_DEFAULT)]);
            $userId = $pdo->lastInsertId();

            // ponytail: random account number relies on the UNIQUE index; a clash (1 in ~10^11) just fails the signup
            $accountNumber = (string) random_int(100000000000, 999999999999);
            $stmt = $pdo->prepare("INSERT INTO accounts (user_id, account_number, type, balance) VALUES (?, ?, 'savings', 0)");
            $stmt->execute([$userId, $accountNumber]);

            $pdo->commit();
            redirect('/auth/login.php?registered=1');
        } catch (PDOException $e) {
            $pdo->rollBack();
            // 1062 = duplicate entry: the database's UNIQUE constraint caught it.
            if (($e->errorInfo[1] ?? null) !== 1062) {
                throw $e;
            }
            $errors[] = str_contains($e->getMessage(), "'email'")
                ? 'That email is already registered.'
                : 'That username is taken.';
        }
    }
}

$title = 'Open an account';
require __DIR__ . '/../includes/header.php';
?>
<div class="auth-card">
    <h1>Open an account</h1>
    <p class="muted">Your new savings account is ready as soon as you sign up.</p>

    <?php foreach ($errors as $error): ?>
        <div class="alert" role="alert"><?= e($error) ?></div>
    <?php endforeach; ?>

    <form method="post">
        <label>Full name
            <input name="full_name" value="<?= e($form['full_name']) ?>" autocomplete="name" required maxlength="100">
        </label>
        <label>Username
            <input name="username" value="<?= e($form['username']) ?>" autocomplete="username" required pattern="[a-zA-Z0-9_]{3,30}">
        </label>
        <label>Email
            <input type="email" name="email" value="<?= e($form['email']) ?>" autocomplete="email" required>
        </label>
        <label>Password
            <input type="password" name="password" autocomplete="new-password" required minlength="8">
        </label>
        <label>Confirm password
            <input type="password" name="confirm" autocomplete="new-password" required minlength="8">
        </label>
        <button class="btn" type="submit">Create account</button>
    </form>
    <p class="muted switch">Already a customer? <a href="<?= BASE_URL ?>/auth/login.php">Sign in</a></p>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
