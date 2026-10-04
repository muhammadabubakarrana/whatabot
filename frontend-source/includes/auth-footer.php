<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?php // Operator identity on the pre-login pages: the business name, a public
      // contact address and the legal links, shown to exactly the visitors (and
      // host abuse reviewers) who never log in. The non-affiliation line is
      // verbatim on purpose — it is what separates "a tool that integrates with
      // WhatsApp" from impersonating it. $conn ?? null like auth-header.php:
      // these pages are unauthenticated and must not fatal without a database. ?>
<footer class="auth-footer">
    <div>
        &copy; <?= date('Y') ?> <?= sanitize(businessName($conn ?? null)) ?> &middot;
        <a href="mailto:<?= sanitize(contactEmail($conn ?? null)) ?>"><?= sanitize(contactEmail($conn ?? null)) ?></a>
    </div>
    <div>
        <a href="<?= APP_URL ?>/privacy.php">Privacy</a> &middot;
        <a href="<?= APP_URL ?>/terms.php">Terms</a> &middot;
        <a href="<?= APP_URL ?>/contact.php">Contact</a>
    </div>
    <div class="auth-disclaimer">
        Not affiliated with, endorsed by, or sponsored by WhatsApp LLC or Meta Platforms, Inc.
    </div>
</footer>
</body>
</html>
