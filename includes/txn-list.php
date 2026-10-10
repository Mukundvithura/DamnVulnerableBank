<?php
// Renders $transactions (rows with amount, description, created_at) as a table.
// Optional: rows with `type` also get an Account column; set $emptyText to change the empty message.
// On phones the CSS turns each row into a two-line entry (description + amount, then date · account).
$showAccount = isset($transactions[0]['type']);
?>
<?php if (!$transactions): ?>
    <p class="muted"><?= e($emptyText ?? 'No transactions yet.') ?></p>
<?php else: ?>
<table class="txn-table">
    <thead>
        <tr>
            <th scope="col">Date</th>
            <th scope="col">Description</th>
            <?php if ($showAccount): ?><th scope="col">Account</th><?php endif; ?>
            <th scope="col">Type</th>
            <th scope="col">Amount</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($transactions as $t): ?>
        <?php $credit = $t['amount'] >= 0; ?>
        <tr>
            <td class="txn-date">
                <time datetime="<?= e(date('c', strtotime($t['created_at']))) ?>"><?= e(date('d M Y', strtotime($t['created_at']))) ?></time>
                <?php if ($showAccount): // phones only: account folded into the date line ?><span class="txn-meta-extra"> · <?= e(ucfirst($t['type'])) ?></span><?php endif; ?>
            </td>
            <!-- VULN (stored XSS): a transfer note typed by the SENDER ends up in the
                 RECIPIENT's transaction description and is printed raw, without e().
                 So Arjun can run script in Priya's browser by sending her a payment. -->
            <td class="txn-desc"><?= $t['description'] ?></td>
            <?php if ($showAccount): ?><td class="txn-account"><?= e(ucfirst($t['type'])) ?></td><?php endif; ?>
            <td class="txn-type"><span class="badge <?= $credit ? 'credit' : 'debit' ?>"><?= $credit ? 'Credit' : 'Debit' ?></span></td>
            <td class="amount <?= $credit ? 'credit' : 'debit' ?>">
                <span class="sr-only"><?= $credit ? 'Credit ' : 'Debit ' ?></span><span aria-hidden="true"><?= $credit ? '+' : '−' ?></span><?= money($t['amount']) ?>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>
