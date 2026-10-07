<?php
require_once __DIR__ . '/../includes/auth.php';
$user = require_login();

// "YYYY-MM-DD" if it's a real calendar date, otherwise ''.
function valid_date(string $s): string
{
    $d = DateTime::createFromFormat('!Y-m-d', $s);
    return ($d && $d->format('Y-m-d') === $s) ? $s : '';
}

// 1. Read the filters from the URL. Anything invalid is simply ignored.
$q       = param($_GET, 'q');
$type    = in_array(param($_GET, 'type'), ['credit', 'debit'], true) ? param($_GET, 'type') : '';
$account = filter_var(param($_GET, 'account'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: null;
$from    = valid_date(param($_GET, 'from'));
$to      = valid_date(param($_GET, 'to'));

// 2. Build the query: SQL pieces go in $where, user values go in $params. Never mix them.
$where  = ['a.user_id = ?'];          // always present: you only ever see your own transactions
$params = [$user['id']];

if ($account) {
    $where[]  = 'a.id = ?';
    $params[] = $account;
}
if ($type === 'credit') {
    $where[] = 't.amount > 0';
}
if ($type === 'debit') {
    $where[] = 't.amount < 0';
}
if ($from) {
    $where[]  = 't.created_at >= ?';
    $params[] = $from;
}
if ($to) {
    $where[]  = 't.created_at < ? + INTERVAL 1 DAY';  // include the whole "to" day
    $params[] = $to;
}
if ($q !== '') {
    $where[]  = 't.description LIKE ?';
    $params[] = '%' . addcslashes($q, '%_\\') . '%';  // treat % and _ typed by the user literally
}

// ponytail: newest 100 only, add LIMIT/OFFSET paging when an account has more history than that
$stmt = db()->prepare(
    'SELECT t.amount, t.description, t.created_at, a.type, a.account_number
       FROM transactions t
       JOIN accounts a ON a.id = t.account_id
      WHERE ' . implode(' AND ', $where) . '
      ORDER BY t.created_at DESC, t.id DESC
      LIMIT 100'
);
$stmt->execute($params);
$transactions = $stmt->fetchAll();

$amounts = array_column($transactions, 'amount');
$credits = array_sum(array_filter($amounts, fn($a) => $a > 0));
$debits  = array_sum(array_filter($amounts, fn($a) => $a < 0));

// The user's own accounts, for the dropdown.
$stmt = db()->prepare('SELECT id, type, account_number FROM accounts WHERE user_id = ? ORDER BY type');
$stmt->execute([$user['id']]);
$myAccounts = $stmt->fetchAll();

$filtered  = $q !== '' || $type || $account || $from || $to;
$emptyText = $filtered ? 'No transactions match these filters.' : 'No transactions yet.';

$title = 'Transactions';
require __DIR__ . '/../includes/header.php';
?>
<h1>Transaction History</h1>

<form class="card filters" method="get">
    <label class="search">Search description
        <input type="search" name="q" value="<?= e($q) ?>" placeholder="e.g. Amazon, Salary">
    </label>
    <label>Account
        <select name="account">
            <option value="">All accounts</option>
            <?php foreach ($myAccounts as $a): ?>
                <option value="<?= (int) $a['id'] ?>" <?= $account === $a['id'] ? 'selected' : '' ?>>
                    <?= e(ucfirst($a['type'])) ?> •••• <?= e(substr($a['account_number'], -4)) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>Type
        <select name="type">
            <option value="">Credits & debits</option>
            <option value="credit" <?= $type === 'credit' ? 'selected' : '' ?>>Credits only</option>
            <option value="debit" <?= $type === 'debit' ? 'selected' : '' ?>>Debits only</option>
        </select>
    </label>
    <label>From
        <input type="date" name="from" value="<?= e($from) ?>">
    </label>
    <label>To
        <input type="date" name="to" value="<?= e($to) ?>">
    </label>
    <div class="filter-actions">
        <a class="btn btn-ghost" href="<?= BASE_URL ?>/banking/transactions.php">Reset</a>
        <button class="btn" type="submit">Apply filters</button>
    </div>
</form>

<section class="card">
    <div class="summary">
        <span><strong><?= count($transactions) ?></strong> transaction<?= count($transactions) === 1 ? '' : 's' ?><?php if ($q !== ''): ?> matching “<?= e($q) ?>”<?php endif; ?></span>
        <span class="credit">In: <?= money($credits) ?></span>
        <span class="debit">Out: <?= money($debits) ?></span>
    </div>
    <?php require __DIR__ . '/../includes/txn-list.php'; ?>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
