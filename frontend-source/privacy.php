<?php
require_once __DIR__ . '/config/public-init.php';

$pageTitle = 'Privacy';
require __DIR__ . '/includes/public-header.php';
?>

<main class="public-main">
    <article class="public-article">
        <h1>Privacy</h1>
        <?php // The settings row's own updated_at, or nothing: a "last updated"
              // line that printed today's date would claim the policy changes
              // daily, which is itself a tell. ?>
        <?php if ($updated = siteSettingUpdatedAt($conn, 'site_privacy_body')): ?>
            <div class="public-updated">Last updated <?= sanitize(date('j F Y', strtotime($updated))) ?></div>
        <?php endif; ?>
        <?= siteTextToHtml(sitePrivacyBody($conn)) ?>
    </article>
</main>

<?php require __DIR__ . '/includes/public-footer.php'; ?>
