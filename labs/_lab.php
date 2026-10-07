<?php
// Shared wrapper for every security lab. Each lab file does:
//   require __DIR__ . '/_lab.php';  $user = require_login();
//   ... vulnerable logic ...  lab_top('slug'); ?> HTML <?php lab_fix('<p>..</p>'); lab_bottom();
//
// The labs here are DELIBERATELY insecure for teaching. They live apart from the
// real banking code (includes/, banking/) so the secure app is never weakened.
require_once __DIR__ . '/../includes/auth.php';

// slug => [Title, one-line blurb, "try this" exploit hint (HTML allowed)]
$LAB_GROUPS = [
    'Client-Side' => [
        'xss-reflected' => ['Reflected XSS', 'Your input is echoed into the page without escaping.',
            'Put <code>&lt;script&gt;alert(document.cookie)&lt;/script&gt;</code> in the box and submit.'],
        'xss-stored'    => ['Stored XSS', 'Saved input runs in every visitor\'s browser.',
            'Post a comment of <code>&lt;script&gt;alert(1)&lt;/script&gt;</code>, then reload.'],
        'csrf'          => ['CSRF', 'A state-changing request with no anti-forgery token.',
            'Open the attacker page; it changes your email with no click.'],
        'cors'          => ['CORS Misconfiguration', 'An API that reflects any Origin and allows credentials.',
            'From another origin, <code>fetch(url,{credentials:"include"})</code> reads your data.'],
        'clickjacking'  => ['Clickjacking', 'The page sets no frame-busting headers.',
            'Open the attacker page that frames this one under a fake button.'],
    ],
    'Server-Side' => [
        'sqli'      => ['SQL Injection', 'User input is concatenated into a SQL query.',
            "Search for <code>' OR '1'='1</code> to dump every user."],
        'command'   => ['OS Command Injection', 'User input is passed to a shell command.',
            'Append <code>; whoami</code> (or <code>&amp; whoami</code> on Windows) to the host.'],
        'traversal' => ['Directory Traversal', 'A filename from the user is opened directly.',
            'Request <code>../../config/config.php</code> to read outside the folder.'],
        'auth'      => ['Broken Authentication', 'Weak login / guessable identifiers.',
            'Try the account lookup with another user\'s id.'],
        'ssrf'      => ['SSRF', 'The server fetches any URL the user gives it.',
            'Point it at an internal address the browser cannot reach.'],
    ],
];

// Flat lookup: slug => [group, title, blurb, hint]
$LABS = [];
foreach ($LAB_GROUPS as $group => $items) {
    foreach ($items as $slug => $meta) {
        $LABS[$slug] = [$group, $meta[0], $meta[1], $meta[2]];
    }
}

function lab_top(string $slug): void
{
    global $LABS, $LAB_GROUPS, $user, $title;
    [$group, $labTitle, $blurb, $hint] = $LABS[$slug];
    $title = $labTitle . ' Lab';
    require __DIR__ . '/../includes/header.php';
    ?>
    <style>
    .lab-banner{background:#fdecec;border:1px solid #e9a3a3;color:#7a1f1f;border-radius:10px;padding:.7rem 1rem;margin-bottom:1rem;font-size:.9rem}
    .lab-layout{display:grid;grid-template-columns:200px 1fr;gap:1.5rem;align-items:start}
    .lab-sub{font-size:.9rem}
    .lab-sub h3{font-size:.75rem;text-transform:uppercase;letter-spacing:.05em;color:#667;margin:1rem 0 .4rem}
    .lab-sub a{display:block;padding:.35rem .5rem;border-radius:7px;color:inherit;text-decoration:none}
    .lab-sub a[aria-current]{background:#1f5fd6;color:#fff}
    .lab-sub a.soon{color:#99a;pointer-events:none}
    .lab-hint{background:#eef4ff;border:1px solid #bcd0f5;border-radius:10px;padding:.7rem 1rem;margin:1rem 0}
    .lab-fix{margin-top:1.5rem;border:1px solid #cbd5e1;border-radius:10px;padding:.5rem 1rem;background:#f8fafc}
    .lab-fix summary{cursor:pointer;font-weight:600;color:#166534}
    .lab-fix pre{overflow:auto;background:#0f172a;color:#e2e8f0;padding:.8rem;border-radius:8px;font-size:.82rem}
    @media(max-width:720px){.lab-layout{grid-template-columns:1fr}}
    </style>
    <div class="lab-banner"><strong>⚠ Intentionally vulnerable.</strong> This page is broken on purpose to demonstrate <?= e($labTitle) ?>. Never copy this code into a real app.</div>
    <div class="lab-layout">
      <nav class="lab-sub" aria-label="Labs">
        <?php foreach ($LAB_GROUPS as $g => $items): ?>
          <h3><?= e($g) ?></h3>
          <?php foreach ($items as $s => $m):
              $built = is_file(__DIR__ . "/$s.php");
              $cur = $s === $slug ? ' aria-current="page"' : ''; ?>
            <?php if ($built): ?>
              <a href="<?= BASE_URL ?>/labs/<?= $s ?>.php"<?= $cur ?>><?= e($m[0]) ?></a>
            <?php else: ?>
              <a class="soon" title="coming soon"><?= e($m[0]) ?> ·</a>
            <?php endif; ?>
          <?php endforeach; ?>
        <?php endforeach; ?>
      </nav>
      <div class="lab-main">
        <h1><?= e($labTitle) ?></h1>
        <p class="muted"><?= $blurb ?></p>
        <div class="lab-hint"><strong>Try this:</strong> <?= $hint ?></div>
    <?php
}

// Collapsible "How to fix" block. Pass the secure version as an HTML string
// (use <pre><code> for code). Shown after the vulnerable demo on each lab.
function lab_fix(string $html): void
{
    ?>
        <details class="lab-fix">
          <summary>How to fix it ✓</summary>
          <?= $html ?>
        </details>
    <?php
}

function lab_bottom(): void
{
    global $user;
    echo '</div></div>'; // .lab-main .lab-layout
    require __DIR__ . '/../includes/footer.php';
}
