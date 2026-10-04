<?php
// The brand mark on every pre-login page: the uploaded logo when one exists,
// otherwise a monogram tile derived from the brand name.
//
// One partial, included by every auth page — six near-identical copies of this
// branch are how three of the pages ended up showing a hard-coded third-party
// glyph and never rendering an uploaded logo at all. The heading and subtitle
// stay in each page; only the mark is shared.
$authBrandLogo = brandLogoUrl($conn ?? null);
?>
<?php if ($authBrandLogo !== ''): ?>
    <img src="<?= sanitize($authBrandLogo) ?>" alt="<?= sanitize($brandName) ?>" class="auth-brand-logo">
<?php else: ?>
    <span class="auth-monogram" style="background-color: <?= sanitize(brandMonogramColor($conn ?? null)) ?>">
        <?= sanitize(brandMonogram($conn ?? null)) ?>
    </span>
<?php endif; ?>
