<?php
require_once __DIR__ . '/config/public-init.php';

$pageTitle = 'Terms';
require __DIR__ . '/includes/public-header.php';
?>

<main class="public-main">
    <article class="public-article">
        <h1>Terms of service</h1>
        <?php // Same rule as privacy.php: the stored row's updated_at or no
              // line at all — never today's date. ?>
        <?php if ($updated = siteSettingUpdatedAt($conn, 'site_terms_body')): ?>
            <div class="public-updated">Last updated <?= sanitize(date('j F Y', strtotime($updated))) ?></div>
        <?php endif; ?>
        <?= siteTextToHtml(siteTermsBody($conn)) ?>
    </article>
</main>

<?php require __DIR__ . '/includes/public-footer.php'; ?>
