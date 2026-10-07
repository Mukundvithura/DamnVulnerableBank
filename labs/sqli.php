<?php
require_once __DIR__ . '/_lab.php';
$user = require_login();

// THE VULNERABILITY: the search term is concatenated straight into the SQL string,
// so an attacker can change what the query means (' OR '1'='1, UNION SELECT, ...).
$q = $_GET['q'] ?? '';
$rows = [];
$error = '';
$sql = '';
if ($q !== '') {
    $sql = "SELECT username, email, role FROM users WHERE username = '$q'"; // VULN: not a prepared statement
    try {
        $rows = db()->query($sql)->fetchAll();
    } catch (PDOException $e) {
        $error = $e->getMessage(); // verbose DB errors are themselves an information leak
    }
}
lab_top('sqli');
?>
<form method="get" class="card stack narrow">
    <label>Find a user by username
        <input name="q" value="<?= e($q) ?>" placeholder="e.g. arjun">
    </label>
    <button class="btn" type="submit">Search</button>
</form>
<?php if ($sql): ?><p class="muted">Query run: <code><?= e($sql) ?></code></p><?php endif; ?>
<?php if ($error): ?><div class="alert"><?= e($error) ?></div><?php endif; ?>
<?php if ($rows): ?>
<table class="card" style="width:100%">
  <tr><th>Username</th><th>Email</th><th>Role</th></tr>
  <?php foreach ($rows as $r): ?>
    <tr><td><?= e($r['username']) ?></td><td><?= e($r['email']) ?></td><td><?= e($r['role']) ?></td></tr>
  <?php endforeach; ?>
</table>
<?php elseif ($q !== '' && !$error): ?><p class="card">No match.</p><?php endif; ?>
<?php lab_fix('
    <p>Never build SQL by string-joining user input. Use a prepared statement with
    a bound parameter — exactly how the real app does it everywhere:</p>
    <pre><code>$stmt = db()-&gt;prepare("SELECT username, email, role FROM users WHERE username = ?");
$stmt-&gt;execute([$q]);
$rows = $stmt-&gt;fetchAll();</code></pre>
    <p>The input is now treated as data, not SQL, so
    <code>\' OR \'1\'=\'1</code> just searches for that literal string. Also turn
    off verbose error output in production.</p>
'); ?>
<?php lab_bottom(); ?>
