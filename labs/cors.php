<?php
require_once __DIR__ . '/_lab.php';
$user = require_login();
lab_top('cors');
$api = APP_URL . '/labs/api/whoami.php';
?>
<p class="card">This bank exposes a JSON profile API at <code><?= e($api) ?></code>.
   It reflects whatever <code>Origin</code> header calls it and sets
   <code>Access-Control-Allow-Credentials: true</code> — so <em>any</em> website you
   visit while logged in can read your name, email and balance.</p>

<section class="card">
  <h2>Proof of concept</h2>
  <p>On any other origin's console (e.g. open the dev console on example.com) run:</p>
  <pre><code>fetch("<?= e($api) ?>", { credentials: "include" })
  .then(r =&gt; r.json()).then(console.log)</code></pre>
  <p>The button below does the same request from this page:</p>
  <button class="btn" onclick='fetch("<?= e($api) ?>",{credentials:"include"}).then(r=>r.json()).then(d=>{document.getElementById("out").textContent=JSON.stringify(d,null,2)})'>Call the API</button>
  <pre id="out" class="muted">(response appears here)</pre>
</section>

<?php lab_fix('
    <p>Never reflect the <code>Origin</code> header back. Keep an allowlist, and only
    pair credentials with a specific trusted origin — never with
    <code>*</code> or a reflected value:</p>
    <pre><code>$allowed = [\'https://app.yourbank.example\'];
$origin  = $_SERVER[\'HTTP_ORIGIN\'] ?? \'\';
if (in_array($origin, $allowed, true)) {
    header("Access-Control-Allow-Origin: $origin");
    header("Access-Control-Allow-Credentials: true");
    header("Vary: Origin");
}</code></pre>
    <p>If the API does not need cookies, use token auth and omit
    <code>Allow-Credentials</code> entirely.</p>
'); ?>
<?php lab_bottom(); ?>
