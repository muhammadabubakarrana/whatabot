<?php
// The shared <head> for the unauthenticated pages: auth-header.php (centred
// card layout) and public-header.php (full-width landing/legal pages) both end
// here so the title pattern, favicon and CDN links cannot drift apart.
// Expects $pageTitle, $brandName and $brandFavicon to be set by the includer.
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= sanitize($pageTitle) ?> - <?= sanitize($brandName) ?></title>
    <?php if ($brandFavicon !== ''): ?>
        <link rel="icon" href="<?= sanitize($brandFavicon) ?>">
    <?php endif; ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="<?= APP_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
