<?php
require_once __DIR__ . '/../includes/auth.php';
$user = require_login();

// ?id= is optional. Anything that isn't a positive whole number counts as "not given".
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

if ($id) {
    // AUTHORIZATION: the account must have this id AND belong to the logged-in user.
    // Without "AND user_id = ?" anyone could read any account by changing the URL (IDOR).
    $stmt = db()->prepare('SELECT id, account_number, type, balance, created_at FROM accounts WHERE id = ? AND user_id = ?');
    $stmt->execute([$id, $user['id']]);
    $account = $stmt->fetch();

    if (!$account) {
        http_response_code(404); // same answer for "doesn't exist" and "not yours"
    } else {
        $stmt = db()->prepare('SELECT amount, description, created_at FROM transactions WHERE account_id = ? ORDER BY created_at DESC LIMIT 50');
        $stmt->execute([$account['id']]);
        $transactions = $stmt->fetchAll();
    }
} else {
    $stmt = db()->prepare('SELECT id, account_number, type, balance, created_at FROM accounts WHERE user_id = ? ORDER BY type');
    $stmt->execute([$user['id']]);
    $accounts = $stmt->fetchAll();
}

$title = 'Accounts';
require __DIR__ . '/../includes/header.php';
?>

<?php if ($id && !$account): ?>
    <a class="back" href="<?= BASE_URL ?>/banking/accounts.php">← All accounts</a>
    <div class="card"><h2>Account Not Found</h2><p class="muted">This account doesn't exist or isn't linked to your profile.</p></div>

<?php elseif ($id): ?>
    <a class="back" href="<?= BASE_URL ?>/banking/accounts.php">← All accounts</a>
    <h1><?= e(ucfirst($account['type'])) ?> Account</h1>

    <section class="card total" aria-labelledby="balance-label">
        <h2 id="balance-label" class="total-label">Available Balance</h2>
        <strong class="total-amount"><?= money($account['balance']) ?></strong>
    </section>

    <section class="card">
        <dl class="details">
            <div><dt>Account number</dt><dd><?= e($account['account_number']) ?></dd></div>
            <div><dt>Account type</dt><dd><?= e(ucfirst($account['type'])) ?></dd></div>
            <div><dt>Opened</dt><dd><?= e(date('d M Y', strtotime($account['created_at']))) ?></dd></div>
        </dl>
    </section>

    <section class="card">
        <h2>Transactions</h2>
        <?php require __DIR__ . '/../includes/txn-list.php'; ?>
    </section>

<?php else: ?>
    <h1>Your Accounts</h1>
    <section class="grid">
        <?php foreach ($accounts as $acct): ?>
            <a class="card card-link" href="?id=<?= (int) $acct['id'] ?>">
                <span class="muted"><?= e(ucfirst($acct['type'])) ?> Account</span>
                <strong><?= money($acct['balance']) ?></strong>
                <small class="muted"><?= e($acct['account_number']) ?> · opened <?= e(date('M Y', strtotime($acct['created_at']))) ?></small>
            </a>
        <?php endforeach; ?>
    </section>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
