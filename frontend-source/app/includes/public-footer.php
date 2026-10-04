<?php
// Operator identity and the legal links: the footer is where a reviewer looks
// for who runs the site. The non-affiliation line is verbatim on purpose: it is
// what separates "a tool that integrates with WhatsApp" from impersonating it.
?>
<footer class="public-footer">
    <div class="public-footer-inner">
        <div>&copy; <?= date('Y') ?> <?= sanitize(businessName($conn ?? null)) ?></div>
        <div class="public-footer-links">
            <a href="mailto:<?= sanitize(contactEmail($conn ?? null)) ?>"><?= sanitize(contactEmail($conn ?? null)) ?></a>
            <a href="<?= APP_URL ?>/privacy.php">Privacy</a>
            <a href="<?= APP_URL ?>/terms.php">Terms</a>
            <a href="<?= APP_URL ?>/contact.php">Contact</a>
        </div>
        <div class="public-disclaimer">
            Not affiliated with, endorsed by, or sponsored by WhatsApp LLC or Meta Platforms, Inc.
        </div>
    </div>
</footer>
</body>
</html>
