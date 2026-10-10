<?php
require_once __DIR__ . '/../includes/auth.php';
$user = require_admin();   // admins only (the vuln is still real for a logged-in admin)

// THE VULNERABILITY (OS command injection): the hostname typed by the admin is
// concatenated straight into a shell command, so shell metacharacters let the
// attacker run extra commands on the server:
//   Windows:  127.0.0.1 & whoami        (also | , && )
//   Linux:    127.0.0.1; whoami         (also | , && )
$host   = param($_GET, 'host');
$output = '';
if ($host !== '') {
    $flag   = stripos(PHP_OS, 'WIN') === 0 ? '-n 2' : '-c 2';
    $output = shell_exec("ping $flag $host 2>&1");   // VULN: no escapeshellarg(), no validation
    if ($output === null) {
        $output = '(shell_exec is disabled on this server — the vulnerability pattern still stands)';
    }
}

$title = 'Server Diagnostics';
require __DIR__ . '/../includes/header.php';
?>
<a class="back no-print" href="<?= BASE_URL ?>/admin/dashboard.php">← Admin dashboard</a>
<h1>Server Diagnostics</h1>

<form class="card stack narrow" method="get">
    <h2>Ping a host</h2>
    <label>Host or IP
        <input name="host" value="<?= e($host) ?>" placeholder="e.g. 127.0.0.1" required>
    </label>
    <button class="btn" type="submit">Ping</button>
</form>

<?php if ($output !== ''): ?>
    <pre class="card"><?= e($output) ?></pre>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
