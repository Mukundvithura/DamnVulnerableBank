<?php
require_once __DIR__ . '/_lab.php';
$user = require_login();
$title = 'Security Labs';
require __DIR__ . '/../includes/header.php';
?>
<h1>Security Labs</h1>
<p class="muted">Nine deliberately vulnerable features, each with a live demo, an
   exploit hint, and the fix. For learning on this local lab only — never deploy.</p>
<?php foreach ($LAB_GROUPS as $group => $items): ?>
<h2><?= e($group) ?></h2>
<ul class="payees">
  <?php foreach ($items as $slug => $m):
      $built = is_file(__DIR__ . "/$slug.php"); ?>
    <li><span class="who">
      <?php if ($built): ?>
        <a href="<?= BASE_URL ?>/labs/<?= $slug ?>.php"><?= e($m[0]) ?></a>
      <?php else: ?>
        <?= e($m[0]) ?> <small class="muted">(soon)</small>
      <?php endif; ?>
      <small class="muted"><?= $m[1] ?></small>
    </span></li>
  <?php endforeach; ?>
</ul>
<?php endforeach; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
