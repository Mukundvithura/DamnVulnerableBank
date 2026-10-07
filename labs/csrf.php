<?php
require_once __DIR__ . '/_lab.php';
$user = require_login();

// THE VULNERABILITY: this changes your account email but (1) accepts GET and
// (2) carries no anti-CSRF token. Any page you visit while logged in can fire it.
$newEmail = $_REQUEST['email'] ?? '';   // VULN: $_REQUEST = GET or POST, and no token check
$changed = false;
if ($newEmail !== '' && filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
    db()->prepare('UPDATE users SET email = ? WHERE id = ?')->execute([$newEmail, $user['id']]);
    $changed = true;
}
$stmt = db()->prepare('SELECT email FROM users WHERE id = ?');
$stmt->execute([$user['id']]);
$email = $stmt->fetchColumn();

lab_top('csrf');
?>
<?php if ($changed): ?><div class="alert success">Email changed to <?= e($email) ?>.</div><?php endif; ?>
<form method="get" class="card stack narrow">
    <label>Account email
        <input name="email" value="<?= e($email) ?>">
    </label>
    <button class="btn" type="submit">Update email</button>
</form>
<p class="card">While logged in, open
   <a href="<?= BASE_URL ?>/labs/attack-csrf.html" target="_blank">attack-csrf.html</a>
   in a new tab. It is a decoy "prize" page that silently changes your email —
   no click on the bank needed.</p>
<?php lab_fix('
    <p>Three layers, strongest first:</p>
    <ol>
      <li><strong>Use POST for state changes, never GET.</strong> A change must not
          happen just by loading a URL in an <code>&lt;img&gt;</code> or a link.</li>
      <li><strong>Add a per-session CSRF token</strong> to every form and verify it:
      <pre><code>// render:  &lt;input type="hidden" name="csrf" value="&lt;?= e($_SESSION[\'csrf\']) ?&gt;"&gt;
// check:   if (!hash_equals($_SESSION[\'csrf\'], $_POST[\'csrf\'] ?? \'\')) { http_response_code(403); exit; }</code></pre></li>
      <li><strong>SameSite cookies.</strong> The app already sets
          <code>SameSite=Lax</code> (see <code>includes/auth.php</code>), which blocks
          the cookie on cross-site POSTs — the main reason the real transfer form is safe.</li>
    </ol>
'); ?>
<?php lab_bottom(); ?>
