<?php
// Expects $title, and $user when logged in.
$brandMark = '<svg class="brand-mark" viewBox="0 0 28 28" aria-hidden="true"><rect width="28" height="28" rx="7" fill="#1f5fd6"/>'
           . '<path d="M7 11.5h14L14 7zM8.5 12.5v6M12 12.5v6M16 12.5v6M19.5 12.5v6M7 20.5h14" fill="none" stroke="#fff" '
           . 'stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?> · <?= APP_NAME ?></title>
    <link rel="preload" href="<?= BASE_URL ?>/assets/fonts/geist-latin-wght-normal.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <script>document.documentElement.classList.add('js');</script>
    <script src="<?= BASE_URL ?>/assets/js/app.js" defer></script>
</head>
<body class="<?= !empty($user) ? 'app' : 'public' ?>">
<a class="skip-link" href="#main">Skip to content</a>
<?php if (!empty($user)):
    // Mark the page you're on, for sighted users (style) and screen readers (aria-current).
    $here = $_SERVER['SCRIPT_NAME'] ?? '';
    $current = fn(string $path) => $here === BASE_URL . $path ? ' aria-current="page"' : '';
?>
<header class="topbar">
    <a class="brand" href="<?= BASE_URL ?>/dashboard/"><?= $brandMark ?><?= APP_NAME ?></a>
    <button class="menu-toggle" type="button" aria-controls="site-nav" aria-expanded="false">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
        Menu
    </button>
    <nav class="nav" id="site-nav" aria-label="Main">
        <button class="menu-toggle nav-close" type="button" aria-controls="site-nav" aria-expanded="true">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
            Close
        </button>
        <div class="nav-links">
            <a href="<?= BASE_URL ?>/dashboard/"<?= $current('/dashboard/index.php') ?>>Dashboard</a>
            <a href="<?= BASE_URL ?>/banking/accounts.php"<?= $current('/banking/accounts.php') ?>>Accounts</a>
            <a href="<?= BASE_URL ?>/banking/transfer.php"<?= $current('/banking/transfer.php') ?>>Transfer</a>
            <a href="<?= BASE_URL ?>/banking/transactions.php"<?= $current('/banking/transactions.php') ?>>Transactions</a>
            <a href="<?= BASE_URL ?>/banking/beneficiaries.php"<?= $current('/banking/beneficiaries.php') ?>>Beneficiaries</a>
            <a href="<?= BASE_URL ?>/banking/statements.php"<?= $current('/banking/statements.php') ?>>Statements</a>
            <?php if ($user['role'] === 'admin'): // convenience only: the real check is require_admin() ?>
                <a class="nav-admin" href="<?= BASE_URL ?>/admin/dashboard.php"<?= str_starts_with($here, BASE_URL . '/admin/') ? ' aria-current="page"' : '' ?>>Admin</a>
            <?php endif; ?>
        </div>
        <form class="nav-account" method="post" action="<?= BASE_URL ?>/auth/logout.php">
            <a class="nav-user" href="<?= BASE_URL ?>/banking/profile.php"<?= $current('/banking/profile.php') ?>>
                <span class="sr-only">Profile: </span><?= e($user['full_name']) ?>
            </a>
            <button class="btn-link" type="submit">Logout</button>
        </form>
    </nav>
</header>
<div class="nav-scrim"></div>
<main class="container" id="main" tabindex="-1">
<?php else: ?>
<div class="auth-layout">
    <aside class="auth-aside">
        <a class="brand" href="<?= BASE_URL ?>/auth/login.php"><?= $brandMark ?><?= APP_NAME ?></a>
        <div class="auth-pitch">
            <h2>Secure Banking. Vulnerable by Design.</h2>
            <p>A realistic online bank for learning how web applications are attacked and defended.</p>
            <ul class="auth-facts">
                <li><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="5" y="11" width="14" height="10" rx="2" fill="none" stroke="currentColor" stroke-width="1.6"/><path d="M8 11V7a4 4 0 0 1 8 0v4" fill="none" stroke="currentColor" stroke-width="1.6"/></svg>
                    <span>Passwords are stored as one-way hashes, never in plain text.</span></li>
                <li><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 12a9 9 0 1 0 3-6.7L3 8M3 3v5h5" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    <span>Your session ID is renewed every time you sign in.</span></li>
                <li><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8zM14 3v5h5" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg>
                    <span>Every sign-in and transfer is written to an audit log.</span></li>
            </ul>
        </div>
        <p class="lab-note">Training lab · fictional customers and data · run locally only</p>
    </aside>
<main class="auth-main" id="main" tabindex="-1">
<?php endif; ?>
<?php if (!empty($_SESSION['flash'])): ?>
    <div class="alert success" role="status"><?= e($_SESSION['flash']) ?></div>
    <?php unset($_SESSION['flash']); // show once, then forget ?>
<?php endif; ?>
