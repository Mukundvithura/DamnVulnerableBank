<?php
require_once __DIR__ . '/_lab.php';
$user = require_login();

// THE VULNERABILITY: the host string is put straight into a shell command, so
// shell metacharacters let the attacker run extra commands.
//   Linux:   127.0.0.1; whoami      Windows: 127.0.0.1 & whoami
$host = $_GET['host'] ?? '';
$output = '';
if ($host !== '') {
    $flag = stripos(PHP_OS, 'WIN') === 0 ? '-n 2' : '-c 2';
    $output = shell_exec("ping $flag $host 2>&1");   // VULN: no escaping of $host
    if ($output === null) {
        $output = '(shell_exec is disabled on this server — the fix is still worth reading)';
    }
}
lab_top('command');
?>
<form method="get" class="card stack narrow">
    <label>Ping a server
        <input name="host" value="<?= e($host) ?>" placeholder="e.g. 127.0.0.1">
    </label>
    <button class="btn" type="submit">Ping</button>
</form>
<?php if ($output !== ''): ?><pre class="card"><?= e($output) ?></pre><?php endif; ?>
<?php lab_fix('
    <p>Two independent defences — do both:</p>
    <ol>
      <li><strong>Validate the input</strong> against a strict allowlist shape
      before it goes anywhere near a shell:
      <pre><code>if (!filter_var($host, FILTER_VALIDATE_IP)) { exit("Enter a valid IP."); }</code></pre></li>
      <li><strong>Escape every argument</strong> so metacharacters are inert, and
      pass arguments as a list rather than one string:
      <pre><code>$out = shell_exec("ping -c 2 " . escapeshellarg($host));
// better still: proc_open([\'ping\',\'-c\',\'2\',$host], ...) with no shell at all</code></pre></li>
    </ol>
'); ?>
<?php lab_bottom(); ?>
