<?php
require_once __DIR__ . '/_lab.php';
$user = require_login();

// THE VULNERABILITY: the server fetches whatever URL the user gives it ("link
// preview"), so it can be aimed at internal services and addresses the attacker's
// own browser could never reach — the DB container, cloud metadata, file://, etc.
$url = $_GET['url'] ?? '';
$body = '';
if ($url !== '') {
    $ctx = stream_context_create(['http' => ['timeout' => 4, 'ignore_errors' => true]]);
    $fetched = @file_get_contents($url, false, $ctx);   // VULN: no scheme or host allowlist
    $body = $fetched === false ? '(could not fetch that URL)' : substr($fetched, 0, 2000);
}
lab_top('ssrf');
?>
<form method="get" class="card stack narrow">
    <label>Preview a link (server fetches it for you)
        <input name="url" value="<?= e($url) ?>" placeholder="https://example.com">
    </label>
    <button class="btn" type="submit">Fetch</button>
</form>
<p class="muted">Try an internal target your browser can't open directly, e.g.
   <code>http://127.0.0.1:3307</code> (the DB) or a cloud metadata IP on a server.</p>
<?php if ($body !== ''): ?><pre class="card"><?= e($body) ?></pre><?php endif; ?>
<?php lab_fix('
    <p>Validate the URL before fetching, and re-check after DNS resolves:</p>
    <ul>
      <li><strong>Allowlist the scheme</strong> (<code>https</code> only — no
      <code>file://</code>, <code>gopher://</code>, etc.).</li>
      <li><strong>Resolve the host and block private ranges</strong> before
      connecting — 127.0.0.0/8, 10/8, 172.16/12, 192.168/16, 169.254/16 (cloud
      metadata), and <code>::1</code>.</li>
      <li><strong>Disable redirects</strong>, or re-validate the target on each hop
      so a public URL can\'t 302 to an internal one.</li>
    </ul>
    <p>An egress allowlist / proxy at the network layer is a strong backstop.</p>
'); ?>
<?php lab_bottom(); ?>
