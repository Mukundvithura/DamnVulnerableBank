<?php
require_once __DIR__ . '/../includes/auth.php';
$user = require_login();

// Spreadsheet apps run cells starting with = + - @ as formulas ("CSV injection").
// A leading ' makes Excel/Sheets treat the cell as plain text.
function csv_safe(string $text): string
{
    return preg_match('/^[=+\-@\t\r]/', $text) ? "'" . $text : $text;
}

// No filenames, no paths: just an account id and a month, both strictly validated.
$accountId = filter_var(param($_GET, 'account'), FILTER_VALIDATE_INT) ?: 0;
$month     = param($_GET, 'month');
$selected  = $accountId && preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month);

if ($selected) {
    $start = "$month-01";
    $end   = date('Y-m-d', strtotime("$start +1 month"));

    // AUTHORIZATION via "AND a.user_id = ?". Balances are worked backwards from today's
    // balance, all in DECIMAL inside the database:
    //   closing = balance now - everything after the month
    //   opening = closing - this month's net movement
    $stmt = db()->prepare(
        'SELECT type, account_number, closing - net AS opening, closing, net
           FROM (SELECT a.type, a.account_number,
                        a.balance - COALESCE(SUM(CASE WHEN t.created_at >= ? THEN t.amount END), 0) AS closing,
                        COALESCE(SUM(CASE WHEN t.created_at >= ? AND t.created_at < ? THEN t.amount END), 0) AS net
                   FROM accounts a
                   LEFT JOIN transactions t ON t.account_id = a.id
                  WHERE a.id = ? AND a.user_id = ?
                  GROUP BY a.id) x'
    );
    $stmt->execute([$end, $start, $end, $accountId, $user['id']]);
    $account = $stmt->fetch();

    if ($account) {
        $stmt = db()->prepare('SELECT amount, description, created_at FROM transactions
                                WHERE account_id = ? AND created_at >= ? AND created_at < ?
                                ORDER BY created_at, id');
        $stmt->execute([$accountId, $start, $end]);
        $transactions = $stmt->fetchAll();

        if (param($_GET, 'format') === 'csv') {
            // The download name is built by us from validated values, never taken from the URL.
            $filename = 'BankVWA-statement-' . substr($account['account_number'], -4) . "-$month.csv";
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('X-Content-Type-Options: nosniff');

            $out = fopen('php://output', 'w');
            fputcsv($out, ['Date', 'Description', 'Debit', 'Credit'], escape: '');
            fputcsv($out, [$start, 'Opening balance', '', $account['opening']], escape: '');
            foreach ($transactions as $t) {
                $isDebit = $t['amount'] < 0;
                fputcsv($out, [
                    date('Y-m-d', strtotime($t['created_at'])),
                    csv_safe($t['description']),
                    $isDebit ? ltrim($t['amount'], '-') : '',
                    $isDebit ? '' : $t['amount'],
                ], escape: '');
            }
            fputcsv($out, [date('Y-m-d', strtotime("$end -1 day")), 'Closing balance', '', $account['closing']], escape: '');
            exit;
        }
    } else {
        http_response_code(404);
    }
} else {
    // List: every (account, month) that has activity.
    $stmt = db()->prepare(
        "SELECT a.id, a.type, a.account_number, DATE_FORMAT(t.created_at, '%Y-%m') AS month, COUNT(*) AS txn_count
           FROM accounts a
           JOIN transactions t ON t.account_id = a.id
          WHERE a.user_id = ?
          GROUP BY a.id, month
          ORDER BY a.type, month DESC"
    );
    $stmt->execute([$user['id']]);
    $byAccount = [];
    foreach ($stmt->fetchAll() as $row) {
        $byAccount[$row['id']][] = $row;
    }
}

$title = 'Statements';
require __DIR__ . '/../includes/header.php';
?>

<?php if ($selected && !$account): ?>
    <a class="back no-print" href="<?= BASE_URL ?>/banking/statements.php">← All statements</a>
    <div class="card"><h2>Statement Not Found</h2><p class="muted">This account doesn't exist or isn't linked to your profile.</p></div>

<?php elseif ($selected): ?>
    <div class="no-print toolbar">
        <a class="back" href="<?= BASE_URL ?>/banking/statements.php">← All statements</a>
        <span>
            <button class="btn-small" type="button" onclick="window.print()">Print / Save as PDF</button>
            <a class="btn-small" href="?account=<?= (int) $accountId ?>&month=<?= e($month) ?>&format=csv">Download CSV</a>
        </span>
    </div>

    <section class="card statement">
        <header>
            <div><strong class="brandmark"><?= APP_NAME ?></strong><span class="muted">Account Statement</span></div>
            <div class="period"><?= e(date('F Y', strtotime($start))) ?></div>
        </header>
        <dl class="details">
            <div><dt>Account holder</dt><dd><?= e($user['full_name']) ?></dd></div>
            <div><dt>Account</dt><dd><?= e(ucfirst($account['type'])) ?> · <?= e($account['account_number']) ?></dd></div>
            <div><dt>Opening balance</dt><dd><?= money($account['opening']) ?></dd></div>
            <div><dt>Closing balance</dt><dd><?= money($account['closing']) ?></dd></div>
        </dl>
        <table>
            <thead><tr><th>Date</th><th>Description</th><th class="num">Debit</th><th class="num">Credit</th></tr></thead>
            <tbody>
            <?php foreach ($transactions as $t): ?>
                <tr>
                    <td><?= e(date('d M', strtotime($t['created_at']))) ?></td>
                    <td><?= e($t['description']) ?></td>
                    <td class="num debit"><?= $t['amount'] < 0 ? money($t['amount']) : '' ?></td>
                    <td class="num credit"><?= $t['amount'] >= 0 ? money($t['amount']) : '' ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$transactions): ?>
                <tr><td colspan="4" class="muted">No transactions this month.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
        <p class="muted fineprint">Generated <?= e(date('d M Y, H:i')) ?>. BankVWA is a training lab, so this is not a real bank statement.</p>
    </section>

<?php else: ?>
    <h1>Statements</h1>
    <?php if (!$byAccount): ?>
        <div class="card"><p class="muted">No statements yet. They appear once an account has transactions.</p></div>
    <?php endif; ?>
    <?php if ($byAccount): ?>
    <section class="card">
        <?php foreach ($byAccount as $id => $months): // one grouped list, a heading per account ?>
            <div class="list-group">
                <h2><?= e(ucfirst($months[0]['type'])) ?> Account <span class="muted"><?= masked($months[0]['account_number']) ?></span></h2>
                <ul class="payees">
                    <?php foreach ($months as $m): ?>
                        <li>
                            <span class="who"><?= e(date('F Y', strtotime($m['month'] . '-01'))) ?>
                                <small class="muted"><?= (int) $m['txn_count'] ?> transaction<?= $m['txn_count'] == 1 ? '' : 's' ?></small>
                            </span>
                            <a class="btn-small ghost-blue" href="?account=<?= (int) $id ?>&month=<?= e($m['month']) ?>&format=csv">CSV</a>
                            <a class="btn-small" href="?account=<?= (int) $id ?>&month=<?= e($m['month']) ?>">View<span class="sr-only"> <?= e(date('F Y', strtotime($m['month'] . '-01'))) ?> statement</span></a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endforeach; ?>
    </section>
    <?php endif; ?>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
