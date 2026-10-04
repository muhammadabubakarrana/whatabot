<?php
require_once __DIR__ . '/config/public-init.php';

$pageTitle = 'Contact';
require __DIR__ . '/includes/public-header.php';
?>

<main class="public-main">
    <article class="public-article">
        <h1>Contact</h1>
        <p>
            This site is operated by <?= sanitize(businessName($conn)) ?>.
            For questions about the service, your account or your data, email
            <a href="mailto:<?= sanitize(contactEmail($conn)) ?>"><?= sanitize(contactEmail($conn)) ?></a>.
        </p>
        <p>
            The workspace itself is at
            <a href="<?= sanitize(APP_URL) ?>"><?= sanitize(APP_URL) ?></a>.
        </p>
        <?php // Optional operator details, rendered only when set: an empty
              // row would read as a missing field rather than an absent one. ?>
        <?php $opDetails = [
            'Address'      => businessAddress($conn),
            'Phone'        => businessPhone($conn),
            'Registration' => businessRegistration($conn),
        ]; ?>
        <?php if (implode('', $opDetails) !== ''): ?>
            <dl class="public-operator-details">
                <?php foreach ($opDetails as $label => $value): ?>
                    <?php if ($value !== ''): ?>
                        <div><dt><?= $label ?></dt><dd><?= sanitize($value) ?></dd></div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </dl>
        <?php endif; ?>
        <?php // No contact form, deliberately: sending mail needs the SMTP
              // settings a deployment may not have configured yet, and an
              // unauthenticated form is a spam relay. A mailto is honest about
              // what exists. ?>
    </article>
</main>

<?php require __DIR__ . '/includes/public-footer.php'; ?>
