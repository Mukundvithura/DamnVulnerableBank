<?php
require_once __DIR__ . '/../includes/auth.php';
$user = require_admin();   // server-side role check: hiding the menu link is not enough

$stats = db()->query(
    "SELECT (SELECT COUNT(*) FROM users WHERE role = 'customer')                    AS customers,
            (SELECT COUNT(*) FROM accounts)                                         AS accounts,
            (SELECT COALESCE(SUM(balance), 0) FROM accounts)                        AS deposits,
            (SELECT COUNT(*) FROM audit_logs WHERE action = 'transfer'
                                               AND created_at >= CURDATE())         AS transfers_today"
)->fetch();

// ponytail: latest 50 only; add filters/paging when the log gets big
$events = db()->query(
    'SELECT l.created_at, l.action, l.details, l.ip_address, u.username
       FROM audit_logs l
       LEFT JOIN users u ON u.id = l.user_id
      ORDER BY l.id DESC
      LIMIT 50'
)->fetchAll();

$title = 'Admin';
require __DIR__ . '/../includes/header.php';
?>
<h1>Admin Dashboard</h1>

<section class="grid stats">
    <div class="card stat"><span class="muted">Customers</span><strong><?= (int) $stats['customers'] ?></strong></div>
    <div class="card stat"><span class="muted">Accounts</span><strong><?= (int) $stats['accounts'] ?></strong></div>
    <div class="card stat"><span class="muted">Total deposits</span><strong><?= money($stats['deposits']) ?></strong></div>
    <div class="card stat"><span class="muted">Transfers today</span><strong><?= (int) $stats['transfers_today'] ?></strong></div>
</section>

<section class="card">
    <div class="toolbar">
        <h2>Audit Log</h2>
        <a class="btn-small ghost-blue" href="<?= BASE_URL ?>/admin/users.php">Manage users →</a>
        <a class="btn-small ghost-blue" href="<?= BASE_URL ?>/admin/diagnostics.php">Server diagnostics →</a>
    </div>
    <?php if (!$events): ?>
        <p class="muted">No events yet.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Time</th><th>User</th><th>Action</th><th>Details</th><th>IP</th></tr></thead>
                <tbody>
                <?php foreach ($events as $ev): ?>
                    <tr>
                        <td><?= e(date('d M H:i', strtotime($ev['created_at']))) ?></td>
                        <td><?= e($ev['username'] ?? '-') ?></td>
                        <td><span class="badge"><?= e($ev['action']) ?></span></td>
                        <!-- Details can contain text customers typed, so it's escaped like everything else. -->
                        <td><?= e($ev['details'] ?? '') ?></td>
                        <td><?= e($ev['ip_address'] ?? '') ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
