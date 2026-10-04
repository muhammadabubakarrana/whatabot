<?php
$pageTitle = $pageTitle ?? 'Welcome';
// #23. These pages are unauthenticated, which is exactly why the branding has to
// reach them: the sign-in page is the first thing anyone sees of the instance.
$brandName = brandName($conn ?? null);
$brandFavicon = brandFaviconUrl($conn ?? null);
require __DIR__ . '/page-head.php';
?>
<body class="auth-body">
