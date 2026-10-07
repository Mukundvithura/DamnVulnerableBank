<?php
require_once __DIR__ . '/_lab.php';
$user = require_login();

// THE VULNERABILITY: this page (like the rest of the app) sends no
// X-Frame-Options or CSP frame-ancestors header, so an attacker can load it in an
// invisible iframe and trick you into clicking controls you cannot see.
lab_top('clickjacking');
?>
<section class="card">
  <h2>Sensitive action</h2>
  <p>Pretend this button closes your bank account for good.</p>
  <button class="btn" onclick="alert('Account closed! (demo only)')">Close my account</button>
</section>
<p class="card">While logged in, open
   <a href="<?= BASE_URL ?>/labs/attack-clickjack.html" target="_blank">attack-clickjack.html</a>.
   It loads this page in a near-invisible frame, lined up under a fake "Play"
   button — so clicking the game clicks the real button.</p>
<?php lab_fix('
    <p>Tell the browser who may frame your pages. Send one header on every
    response (ideally from a central place like <code>includes/header.php</code> or
    the web-server config):</p>
    <pre><code>// modern, preferred:
header("Content-Security-Policy: frame-ancestors \'none\'");
// legacy fallback for old browsers:
header("X-Frame-Options: DENY");</code></pre>
    <p>Use <code>frame-ancestors \'self\'</code> instead if your own pages need to
    frame each other.</p>
'); ?>
<?php lab_bottom(); ?>
