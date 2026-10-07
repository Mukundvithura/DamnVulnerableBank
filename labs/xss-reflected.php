<?php
require_once __DIR__ . '/_lab.php';
$user = require_login();

// THE VULNERABILITY: the search term from the URL is echoed straight back into
// the page. Because it is NOT passed through e()/htmlspecialchars(), any HTML or
// <script> the attacker puts in ?name=... runs in the victim's browser.
$name = $_GET['name'] ?? '';

lab_top('xss-reflected');
?>
<form method="get" class="card stack narrow">
    <label>Search payees by name
        <input name="name" value="<?= $name /* VULN: not escaped on the way out */ ?>" placeholder="e.g. Priya">
    </label>
    <button class="btn" type="submit">Search</button>
</form>

<?php if ($name !== ''): ?>
    <!-- VULN: raw user input printed directly into HTML -->
    <p class="card">No payees matching <strong><?= $name ?></strong>.</p>
<?php endif; ?>

<?php lab_fix('
    <p>Escape every piece of user input on the way <em>out</em>, so the browser
    treats it as text, never as markup. The app already has a helper for this
    (<code>e()</code> in <code>includes/auth.php</code>):</p>
    <pre><code>// value attribute
&lt;input name="name" value="&lt;?= e($name) ?&gt;"&gt;

// and in the message
No payees matching &lt;strong&gt;&lt;?= e($name) ?&gt;&lt;/strong&gt;.</code></pre>
    <p>Now <code>&lt;script&gt;</code> arrives as the literal text
    <code>&amp;lt;script&amp;gt;</code> and does nothing. A Content-Security-Policy
    header is a good second layer of defence.</p>
'); ?>

<?php lab_bottom(); ?>
