<?php
// Layout for the public pages (landing, privacy, terms, contact): full-width,
// not the centred auth card. Unauthenticated, so $conn ?? null throughout:
// the page must render on a first boot before the database answers.
$pageTitle = $pageTitle ?? 'Welcome';
$brandName = brandName($conn ?? null);
$brandFavicon = brandFaviconUrl($conn ?? null);
$publicLogo = brandLogoUrl($conn ?? null);
require __DIR__ . '/page-head.php';
?>
<body class="public-body">
<header class="public-topbar">
    <a class="public-brand" href="<?= APP_URL ?>/">
        <?php if ($publicLogo !== ''): ?>
            <img src="<?= sanitize($publicLogo) ?>" alt="<?= sanitize($brandName) ?>">
        <?php else: ?>
            <span class="public-monogram" style="background-color: <?= sanitize(brandMonogramColor($conn ?? null)) ?>"><?= sanitize(brandMonogram($conn ?? null)) ?></span>
            <?= sanitize($brandName) ?>
        <?php endif; ?>
    </a>
    <nav class="public-nav">
        <a href="<?= APP_URL ?>/#features">Features</a>
        <a href="<?= APP_URL ?>/#how">How it works</a>
        <a href="<?= APP_URL ?>/#faq">Questions</a>
        <a href="<?= APP_URL ?>/#contact">Contact</a>
    </nav>
    <a class="btn btn-primary btn-sm" href="<?= APP_URL ?>/login.php">Sign in</a>
</header>
