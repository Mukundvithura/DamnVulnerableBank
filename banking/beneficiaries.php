<?php
require_once __DIR__ . '/../includes/auth.php';
$user = require_login();

$errors = [];
$form = ['account_number' => '', 'nickname' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = param($_POST, 'action');

    if ($action === 'delete') {
        // AUTHORIZATION: only delete if this payee belongs to you.
        $id = filter_var(param($_POST, 'id'), FILTER_VALIDATE_INT) ?: 0;
        $stmt = db()->prepare('DELETE FROM beneficiaries WHERE id = ? AND user_id = ?');
        $stmt->execute([$id, $user['id']]);
        $_SESSION['flash'] = $stmt->rowCount() ? 'Payee removed.' : 'Payee not found.';
        redirect('/banking/beneficiaries.php');
    }

    if ($action === 'add') {
        foreach ($form as $field => $_) {
            $form[$field] = param($_POST, $field);
        }

        // Validate on the way IN: right shape and length.
        if (!preg_match('/^\d{12}$/', $form['account_number'])) {
            $errors[] = 'Enter a 12-digit account number.';
        }
        if (!preg_match('/^.{1,50}$/u', $form['nickname'])) {
            $errors[] = 'Nickname must be 1–50 characters.';
        }

        if (!$errors) {
            // The payee's real name comes from the bank's records, not from the user.
            $stmt = db()->prepare('SELECT a.user_id, u.full_name FROM accounts a JOIN users u ON u.id = a.user_id WHERE a.account_number = ?');
            $stmt->execute([$form['account_number']]);
            $owner = $stmt->fetch();

            if (!$owner) {
                $errors[] = 'No account found with that number.';
            } elseif ($owner['user_id'] === $user['id']) {
                $errors[] = "That's your own account. Use Transfer to move money between your accounts.";
            } else {
                try {
                    db()->prepare('INSERT INTO beneficiaries (user_id, name, account_number, nickname) VALUES (?, ?, ?, ?)')
                        ->execute([$user['id'], $owner['full_name'], $form['account_number'], $form['nickname']]);
                    $_SESSION['flash'] = $owner['full_name'] . ' added as a payee.';
                    redirect('/banking/beneficiaries.php');
                } catch (PDOException $e) {
                    if (($e->errorInfo[1] ?? null) !== 1062) {   // 1062 = UNIQUE (user_id, account_number)
                        throw $e;
                    }
                    $errors[] = 'This account is already in your payees.';
                }
            }
        }
    }
}

$stmt = db()->prepare('SELECT id, name, account_number, nickname FROM beneficiaries WHERE user_id = ? ORDER BY nickname');
$stmt->execute([$user['id']]);
$payees = $stmt->fetchAll();

$title = 'Beneficiaries';
require __DIR__ . '/../includes/header.php';
?>
<h1>Beneficiaries</h1>

<section class="card">
    <h2>Saved Payees</h2>
    <?php if (!$payees): ?>
        <p class="muted">No payees yet. Add one below.</p>
    <?php endif; ?>
    <ul class="payees">
        <?php foreach ($payees as $p): ?>
            <li>
                <!-- Encode on the way OUT: nickname was typed by a user, so it's escaped with e(). -->
                <span class="who"><?= e($p['nickname']) ?>
                    <small class="muted"><?= e($p['name']) ?> · <?= masked($p['account_number']) ?></small>
                </span>
                <a class="btn-small" href="<?= BASE_URL ?>/banking/transfer.php?to=<?= e($p['account_number']) ?>">Pay</a>
                <form method="post" onsubmit="return confirm('Remove this payee?')">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                    <button class="btn-small ghost" type="submit">Remove</button>
                </form>
            </li>
        <?php endforeach; ?>
    </ul>
</section>

<form class="card stack narrow" method="post" id="add-payee">
    <h2>Add a Payee</h2>
    <?php foreach ($errors as $error): ?>
        <div class="alert" role="alert"><?= e($error) ?></div>
    <?php endforeach; ?>
    <input type="hidden" name="action" value="add">
    <label>Account number
        <input name="account_number" value="<?= e($form['account_number']) ?>" inputmode="numeric" pattern="\d{12}" maxlength="12" placeholder="12 digits" required>
    </label>
    <label>Nickname
        <input name="nickname" value="<?= e($form['nickname']) ?>" maxlength="50" placeholder="e.g. Landlord" required>
    </label>
    <button class="btn" type="submit">Add payee</button>
</form>
<?php require __DIR__ . '/../includes/footer.php'; ?>
