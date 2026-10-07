<?php
require_once __DIR__ . '/../includes/auth.php';
$user = require_admin();

$users = db()->query(
    'SELECT u.username, u.full_name, u.email, u.role, u.created_at,
            COUNT(a.id) AS account_count, COALESCE(SUM(a.balance), 0) AS total_balance
       FROM users u
       LEFT JOIN accounts a ON a.user_id = u.id
      GROUP BY u.id
      ORDER BY u.created_at, u.id'
)->fetchAll();

$title = 'Users';
require __DIR__ . '/../includes/header.php';
?>
<a class="back" href="<?= BASE_URL ?>/admin/dashboard.php">← Admin dashboard</a>
<h1>Users</h1>

<section class="card">
    <div class="table-wrap">
        <table class="data">
            <thead>
                <tr><th>Username</th><th>Name</th><th>Email</th><th>Role</th><th class="num">Accounts</th><th class="num">Total balance</th><th>Joined</th></tr>
            </thead>
            <tbody>
            <?php foreach ($users as $u): ?>
                <tr>
                    <td><?= e($u['username']) ?></td>
                    <td><?= e($u['full_name']) ?></td>
                    <td><?= e($u['email']) ?></td>
                    <td><span class="badge <?= $u['role'] === 'admin' ? 'admin' : '' ?>"><?= e($u['role']) ?></span></td>
                    <td class="num"><?= (int) $u['account_count'] ?></td>
                    <td class="num"><?= money($u['total_balance']) ?></td>
                    <td><?= e(date('d M Y', strtotime($u['created_at']))) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
