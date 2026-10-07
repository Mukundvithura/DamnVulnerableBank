<?php
require_once __DIR__ . '/_lab.php';
$user = require_login();

// THE VULNERABILITY: comments are saved as-typed and printed as-typed. A <script>
// posted once runs in every visitor's browser from then on. (The INSERT is a
// prepared statement, so this is NOT SQL injection — the bug is unescaped output.)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $comment = $_POST['comment'] ?? '';
    if (trim($comment) !== '') {
        db()->prepare('INSERT INTO lab_guestbook (name, comment) VALUES (?, ?)')
            ->execute([$user['full_name'], $comment]);
    }
    redirect('/labs/xss-stored.php');
}
$rows = db()->query('SELECT name, comment, created_at FROM lab_guestbook ORDER BY id DESC')->fetchAll();

lab_top('xss-stored');
?>
<form method="post" class="card stack narrow">
    <label>Leave a comment
        <input name="comment" placeholder="What do you think of the new app?">
    </label>
    <button class="btn" type="submit">Post</button>
</form>
<section class="card">
  <h2>Comments</h2>
  <?php foreach ($rows as $r): ?>
    <!-- VULN: name is escaped, but comment is printed raw -->
    <p><strong><?= e($r['name']) ?>:</strong> <?= $r['comment'] ?>
       <small class="muted"><?= e($r['created_at']) ?></small></p>
  <?php endforeach; ?>
</section>
<?php lab_fix('
    <p>The fix is the same as reflected XSS: escape on output. Change the comment
    line to run it through <code>e()</code>:</p>
    <pre><code>&lt;?= e($r[\'comment\']) ?&gt;</code></pre>
    <p>If you want to allow <em>some</em> formatting (bold, links), don\'t hand-roll
    it — run the input through a sanitiser like HTML Purifier and store the cleaned
    HTML. Add a Content-Security-Policy header as defence in depth.</p>
'); ?>
<?php lab_bottom(); ?>
