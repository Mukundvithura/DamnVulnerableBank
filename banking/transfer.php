<?php
require_once __DIR__ . '/../includes/auth.php';
$user = require_login();

const MAX_TRANSFER = 100000; // per-transfer limit in rupees

$stmt = db()->prepare('SELECT id, type, account_number, balance FROM accounts WHERE user_id = ? ORDER BY type');
$stmt->execute([$user['id']]);
$myAccounts = $stmt->fetchAll();

$errors = [];
$form = ['from' => '', 'to' => '', 'amount' => '', 'note' => ''];

// "Pay" button on the Beneficiaries page links here with ?to=<account number>.
// VULN NOTE: the recipient AND amount can be pre-filled from the URL, which is what
// makes the clickjacking attack one click — the framed form is already filled in.
if (preg_match('/^\d{12}$/', param($_GET, 'to'))) {
    $form['to'] = param($_GET, 'to');
}
if (preg_match('/^\d{1,6}(\.\d{1,2})?$/', param($_GET, 'amount'))) {
    $form['amount'] = param($_GET, 'amount');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($form as $field => $_) {
        $form[$field] = param($_POST, $field);
    }
    $fromId = filter_var($form['from'], FILTER_VALIDATE_INT) ?: 0;
    $amount = $form['amount'];   // stays a string: money is never a float

    // 1. Validate the input format.
    if (!preg_match('/^\d{12}$/', $form['to'])) {
        $errors[] = 'Enter the 12-digit account number to send to.';
    }
    // Digits, optional 1–2 decimals. No minus sign possible, so no negative transfers.
    if (!preg_match('/^\d{1,6}(\.\d{1,2})?$/', $amount) || (float) $amount <= 0) {
        $errors[] = 'Enter an amount like 500 or 1250.50.';
    } elseif ((float) $amount > MAX_TRANSFER) {
        $errors[] = 'The maximum per transfer is ' . money(MAX_TRANSFER) . '.';
    }
    if (!preg_match('/^.{0,100}$/u', $form['note'])) {
        $errors[] = 'Keep the note under 100 characters.';
    }

    // 2. Move the money: all steps succeed together or none do.
    if (!$errors) {
        $pdo = db();
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare('SELECT a.id, u.full_name FROM accounts a JOIN users u ON u.id = a.user_id WHERE a.account_number = ?');
            $stmt->execute([$form['to']]);
            $dest = $stmt->fetch();

            if (!$dest) {
                $errors[] = 'No account found with that number.';
            } elseif ($dest['id'] === $fromId) {
                $errors[] = "You can't transfer to the same account.";
            } else {
                // Lock both rows until COMMIT. Locking in id order means two opposite
                // transfers (A→B and B→A) wait for each other instead of deadlocking.
                $stmt = $pdo->prepare('SELECT id, user_id, type, account_number FROM accounts WHERE id IN (?, ?) ORDER BY id FOR UPDATE');
                $stmt->execute([$fromId, $dest['id']]);
                $source = array_column($stmt->fetchAll(), null, 'id')[$fromId] ?? null;

                // AUTHORIZATION: the "from" account must be yours. Never trust the dropdown value.
                if (!$source || $source['user_id'] !== $user['id']) {
                    $errors[] = 'Choose one of your own accounts to send from.';
                } else {
                    // Check and subtract in ONE statement: only succeeds if the money is there.
                    $stmt = $pdo->prepare('UPDATE accounts SET balance = balance - CAST(? AS DECIMAL(12,2))
                                            WHERE id = ? AND balance >= CAST(? AS DECIMAL(12,2))');
                    $stmt->execute([$amount, $fromId, $amount]);

                    if ($stmt->rowCount() !== 1) {
                        $errors[] = 'Insufficient funds.';
                    } else {
                        $pdo->prepare('UPDATE accounts SET balance = balance + CAST(? AS DECIMAL(12,2)) WHERE id = ?')
                            ->execute([$amount, $dest['id']]);

                        $note = $form['note'] !== '' ? ' - ' . $form['note'] : '';
                        $addTxn = $pdo->prepare('INSERT INTO transactions (account_id, amount, description) VALUES (?, ?, ?)');
                        $addTxn->execute([$fromId, '-' . $amount, 'Transfer to ' . $dest['full_name'] . $note]);
                        $addTxn->execute([$dest['id'], $amount, 'Transfer from ' . $user['full_name'] . $note]);

                        audit($user['id'], 'transfer', "$amount from {$source['account_number']} to {$form['to']}"); // same connection, so it's inside this DB transaction
                    }
                }
            }

            if ($errors) {
                $pdo->rollBack();
            } else {
                $pdo->commit();
                // Post/Redirect/Get: refreshing the next page can't resend the money.
                $_SESSION['flash'] = money((float) $amount) . ' sent to ' . $dest['full_name'] . '.';
                redirect('/banking/transfer.php');
            }
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }
}

// If the account number belongs to one of YOUR saved payees, show who you're paying,
// so the recipient is confirmed by name instead of trusted as a bare number.
$payee = false;
if (preg_match('/^\d{12}$/', $form['to'])) {
    $stmt = db()->prepare('SELECT nickname, name, account_number FROM beneficiaries WHERE user_id = ? AND account_number = ?');
    $stmt->execute([$user['id'], $form['to']]);
    $payee = $stmt->fetch();
}

$title = 'Transfer';
require __DIR__ . '/../includes/header.php';
?>
<h1>Transfer Money</h1>

<form class="card stack narrow" method="post">
    <?php foreach ($errors as $error): ?>
        <div class="alert" role="alert"><?= e($error) ?></div>
    <?php endforeach; ?>

    <?php if ($payee): ?>
        <div class="payee-confirm">
            <span class="muted">Paying</span>
            <span class="payee-name"><?= e($payee['nickname']) ?></span>
            <small class="muted"><?= e($payee['name']) ?> · <?= masked($payee['account_number']) ?></small>
        </div>
    <?php endif; ?>

    <label>From
        <select name="from" required>
            <?php foreach ($myAccounts as $a): ?>
                <option value="<?= (int) $a['id'] ?>" <?= $form['from'] === (string) $a['id'] ? 'selected' : '' ?>>
                    <?= e(ucfirst($a['type'])) ?> •••• <?= e(substr($a['account_number'], -4)) ?> (<?= money($a['balance']) ?>)
                </option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>To account number
        <input name="to" value="<?= e($form['to']) ?>" inputmode="numeric" pattern="\d{12}" maxlength="12" placeholder="12 digits" required>
    </label>
    <label>Amount (₹)
        <input name="amount" value="<?= e($form['amount']) ?>" inputmode="decimal" placeholder="0.00" required<?= $payee && !$errors ? ' autofocus' : '' ?>>
    </label>
    <label>Note <span class="muted">(optional)</span>
        <input name="note" value="<?= e($form['note']) ?>" maxlength="100" placeholder="e.g. Rent for October">
    </label>
    <button class="btn" type="submit">Send money</button>
    <p class="muted hint">Limit <?= money(MAX_TRANSFER) ?> per transfer.</p>
</form>
<?php require __DIR__ . '/../includes/footer.php'; ?>
