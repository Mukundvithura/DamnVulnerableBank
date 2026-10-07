<?php
require_once __DIR__ . '/../includes/auth.php';
$user = require_login();

// Administrators don't hold accounts; their home is the Admin console.
// Every route to "home" (login, /, brand link, Dashboard link) lands here, so this one check covers all of them.
if ($user['role'] === 'admin') {
    redirect('/admin/dashboard.php');
}

// Only this user's accounts: the WHERE user_id = ? is the authorization check.
$stmt = db()->prepare('SELECT id, account_number, type, balance FROM accounts WHERE user_id = ? ORDER BY type');
$stmt->execute([$user['id']]);
$accounts = $stmt->fetchAll();
$total = array_sum(array_column($accounts, 'balance'));

$stmt = db()->prepare(
    'SELECT t.amount, t.description, t.created_at, a.type, a.account_number
       FROM transactions t
       JOIN accounts a ON a.id = t.account_id
      WHERE a.user_id = ?
      ORDER BY t.created_at DESC, t.id DESC
      LIMIT 5'
);
$stmt->execute([$user['id']]);
$transactions = $stmt->fetchAll();

// Up to two saved payees become one-tap "Pay …" shortcuts.
$stmt = db()->prepare('SELECT nickname, account_number FROM beneficiaries WHERE user_id = ? ORDER BY nickname LIMIT 2');
$stmt->execute([$user['id']]);
$payees = $stmt->fetchAll();

// The sign-in BEFORE this one (the newest row is the current session's own login).
$stmt = db()->prepare("SELECT created_at FROM audit_logs WHERE user_id = ? AND action = 'login' ORDER BY id DESC LIMIT 1 OFFSET 1");
$stmt->execute([$user['id']]);
$lastLogin = $stmt->fetchColumn();

$title = 'Dashboard';
require __DIR__ . '/../includes/header.php';
?>
<div class="page-head">
    <h1>Welcome, <?= e($user['full_name']) ?></h1>
    <p class="muted last-login">
        <?= $lastLogin ? 'Last login ' . e(date('d M Y, g:i A', strtotime($lastLogin))) . ' IST' : 'This is your first sign-in.' ?>
    </p>
</div>

<?php if (!$accounts): ?>
    <section class="card">
        <h2>No Accounts Yet</h2>
        <p class="muted">There are no bank accounts linked to this profile, so there's nothing to show here yet.</p>
    </section>
<?php else: ?>
    <section class="card total" aria-labelledby="total-label">
        <h2 id="total-label" class="total-label">Total Balance</h2>
        <strong class="total-amount"><?= money($total) ?></strong>
        <p class="as-of">Across <?= count($accounts) ?> account<?= count($accounts) === 1 ? '' : 's' ?> · as of <?= e(date('d M Y, g:i A')) ?> IST</p>
        <div class="quick-actions">
            <a class="btn-small inverse" href="<?= BASE_URL ?>/banking/transfer.php">Transfer money</a>
            <?php foreach ($payees as $p): // nickname was typed by a user, so it's escaped like any other user text ?>
                <a class="btn-small ghost-light" href="<?= BASE_URL ?>/banking/transfer.php?to=<?= e($p['account_number']) ?>">Pay <?= e($p['nickname']) ?></a>
            <?php endforeach; ?>
            <?php if (!$payees): ?>
                <a class="btn-small ghost-light" href="<?= BASE_URL ?>/banking/beneficiaries.php#add-payee">Add a payee</a>
            <?php endif; ?>
            <a class="btn-small ghost-light" href="<?= BASE_URL ?>/banking/statements.php">Statements</a>
        </div>
    </section>

    <section aria-labelledby="accounts-label">
        <h2 id="accounts-label" class="sr-only">Your Accounts</h2>
        <div class="grid">
            <?php foreach ($accounts as $acct): ?>
                <a class="card card-link account-card" href="<?= BASE_URL ?>/banking/accounts.php?id=<?= (int) $acct['id'] ?>">
                    <span class="muted"><?= e(ucfirst($acct['type'])) ?> Account</span>
                    <strong><?= money($acct['balance']) ?></strong>
                    <span class="account-foot">
                        <small class="muted"><?= masked($acct['account_number']) ?></small>
                        <span class="card-cue" aria-hidden="true">View details →</span>
                    </span>
                </a>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="card recent">
        <div class="card-head">
            <h2>Recent Transactions</h2>
            <a class="view-all" href="<?= BASE_URL ?>/banking/transactions.php">View all<span class="sr-only"> transactions</span> →</a>
        </div>
        <?php require __DIR__ . '/../includes/txn-list.php'; ?>
    </section>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
