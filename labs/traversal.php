<?php
require_once __DIR__ . '/_lab.php';
$user = require_login();

// THE VULNERABILITY: the filename from the URL is joined onto a directory and
// opened directly, so "../" sequences climb out of the intended folder.
//   e.g. ?file=../../config/config.php  leaks the database credentials.
$file = $_GET['file'] ?? '';
$content = '';
if ($file !== '') {
    $path = __DIR__ . '/files/' . $file;   // VULN: $file is never sanitised
    $content = is_file($path) ? file_get_contents($path) : "File not found: $path";
}
lab_top('traversal');
?>
<form method="get" class="card stack narrow">
    <label>View a statement file
        <input name="file" value="<?= e($file) ?>" placeholder="statement-arjun.txt">
    </label>
    <button class="btn" type="submit">Open</button>
</form>
<?php if ($content !== ''): ?><pre class="card"><?= e($content) ?></pre><?php endif; ?>
<?php lab_fix('
    <p>Strip the path and confirm the final, resolved path really sits inside the
    folder you meant:</p>
    <pre><code>$dir  = realpath(__DIR__ . "/files");
$path = realpath($dir . "/" . basename($file));   // basename() drops any ../
if ($path === false || !str_starts_with($path, $dir . DIRECTORY_SEPARATOR)) {
    http_response_code(404); exit("Not found");
}</code></pre>
    <p>An explicit allowlist of permitted filenames is even safer when the set is known.</p>
'); ?>
<?php lab_bottom(); ?>
