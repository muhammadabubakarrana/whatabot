<?php
// The landing page, the one public page that has to exist. A deployment whose
// entire surface is a 302 to a credential form is the pattern hosts flag as
// phishing; this page names the operator, says what the service is, and links
// the policies. Layout follows the approved public-site design demo; the inbox
// frame is a CSS illustration, never a screenshot.
require_once __DIR__ . '/config/public-init.php';

if (isLoggedIn()) {
    redirect(APP_URL . '/dashboard.php');
}

$pageTitle = 'Welcome';
require __DIR__ . '/includes/public-header.php';
?>

<main class="public-main">

    <div class="public-hero">
        <div class="public-hero-copy">
            <p class="public-eyebrow">A private WhatsApp workspace</p>
            <h1><?= sanitize(siteHeadline($conn)) ?></h1>
            <p class="public-lede"><?= sanitize(siteIntro($conn)) ?></p>
            <div class="public-cta">
                <a class="btn btn-primary" href="#contact">Request access</a>
                <span class="public-signin-line">Already invited?
                    <a href="<?= APP_URL ?>/login.php">Sign in</a>
                </span>
            </div>
            <p class="public-note">
                Accounts are created by the operator. There is no public sign-up.
            </p>
        </div>

        <div class="public-hero-art">
            <?php // Pure CSS shapes: a layout illustration of the shared inbox.
                  // No real capture and no customer-looking text: the app's
                  // screens contain real conversations and are never shown to
                  // a stranger. ?>
            <div class="public-mock" aria-hidden="true">
                <div class="pm-bar">
                    <span class="pm-dot"></span><span class="pm-dot"></span><span class="pm-dot"></span>
                    <span class="pm-title">inbox, schematic</span>
                </div>
                <div class="pm-body">
                    <div class="pm-side">
                        <div class="pm-row on"><span class="pm-avatar"></span><span class="pm-line"></span></div>
                        <div class="pm-row"><span class="pm-avatar"></span><span class="pm-line w-52"></span></div>
                        <div class="pm-row"><span class="pm-avatar"></span><span class="pm-line w-70"></span></div>
                        <div class="pm-row"><span class="pm-avatar"></span><span class="pm-line w-46"></span></div>
                        <div class="pm-row"><span class="pm-avatar"></span><span class="pm-line w-60"></span></div>
                    </div>
                    <div class="pm-chat">
                        <div class="pm-bubble"><span class="pm-line w-140"></span><span class="pm-line w-96"></span></div>
                        <div class="pm-bubble me"><span class="pm-line w-110"></span></div>
                        <div class="pm-bubble"><span class="pm-line w-124"></span><span class="pm-line w-60"></span></div>
                    </div>
                </div>
                <div class="pm-compose"><span class="pm-line"></span><span class="pm-send"></span></div>
            </div>
            <p class="public-caption">Layout illustration, not a screenshot.</p>
        </div>
    </div>

    <?php $features = siteFeatures($conn); ?>
    <?php if ($features): ?>
        <section class="public-section" id="features">
            <div class="public-section-head">
                <h2>What you can do here</h2>
                <p>Choose the tools that fit your team. Some features depend on the plan and how the operator sets up the workspace.</p>
            </div>
            <ul class="public-cards">
                <?php foreach ($features as $feature): ?>
                    <li>
                        <strong><?= sanitize($feature['title']) ?></strong>
                        <?php if ($feature['body'] !== ''): ?>
                            <p><?= sanitize($feature['body']) ?></p>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>
    <?php endif; ?>

    <section class="public-section" id="how">
        <div class="public-section-head">
            <h2>How it works</h2>
        </div>
        <ol class="public-steps">
            <?php foreach (siteSteps($conn) as $step): ?>
                <li>
                    <strong><?= sanitize($step['title']) ?></strong>
                    <p><?= sanitize($step['body']) ?></p>
                </li>
            <?php endforeach; ?>
        </ol>
    </section>

    <?php $shots = siteScreenshotUrls($conn); ?>
    <?php if ($shots): ?>
        <section class="public-section">
            <div class="public-section-head">
                <h2>A closer look</h2>
            </div>
            <div class="public-shots">
                <?php foreach ($shots as $i => $url): ?>
                    <img src="<?= sanitize($url) ?>" alt="<?= sanitize(businessName($conn)) ?> screenshot <?= $i + 1 ?>" loading="lazy">
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <section class="public-section">
        <div class="public-section-head">
            <h2>What happens to your messages?</h2>
        </div>
        <ul class="public-data-list">
            <?php foreach (siteDataPoints($conn) as $point): ?>
                <li><?= sanitize($point) ?></li>
            <?php endforeach; ?>
        </ul>
        <p class="public-fineprint">
            <a href="<?= APP_URL ?>/privacy.php">Read the full privacy notice</a>
        </p>
    </section>

    <?php // Plans render only when the operator opted in AND there is something
          // to show: an empty "Plans" section is worse than none. The query can
          // also run before the table exists; a failure hides the section. ?>
    <?php if (siteShowPlans($conn) && $conn): ?>
        <?php try { $plans = getActivePlans($conn); } catch (mysqli_sql_exception $e) { $plans = []; } ?>
        <?php if ($plans): ?>
            <section class="public-section">
                <div class="public-section-head">
                    <h2>Plans</h2>
                </div>
                <ul class="public-plans">
                    <?php foreach ($plans as $plan): ?>
                        <li>
                            <div class="plan-name"><?= sanitize($plan['name'] ?? '') ?></div>
                            <div class="plan-price"><?= sanitize(formatPrice($plan)) ?></div>
                            <?php if (trim((string)($plan['description'] ?? '')) !== ''): ?>
                                <div class="plan-desc"><?= sanitize($plan['description']) ?></div>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </section>
        <?php endif; ?>
    <?php endif; ?>

    <section class="public-section" id="faq">
        <div class="public-section-head">
            <h2>Questions people ask</h2>
        </div>
        <div class="public-faq">
            <?php foreach (siteFaq($conn) as $item): ?>
                <h3><?= sanitize($item['q']) ?></h3>
                <p><?= sanitize($item['a']) ?></p>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="public-section" id="contact">
        <div class="public-section-head">
            <h2>Who runs this workspace?</h2>
            <p>For an invitation or questions about your data, contact the operator directly.</p>
        </div>
        <div class="public-operator">
            <?php $opLogo = brandLogoUrl($conn); ?>
            <?php if ($opLogo !== ''): ?>
                <img class="public-operator-logo" src="<?= sanitize($opLogo) ?>" alt="<?= sanitize(businessName($conn)) ?>">
            <?php else: ?>
                <span class="public-operator-monogram" style="background-color: <?= sanitize(brandMonogramColor($conn)) ?>"><?= sanitize(brandMonogram($conn)) ?></span>
            <?php endif; ?>
            <div class="public-operator-who">
                <strong><?= sanitize(businessName($conn)) ?></strong>
                <p><?= sanitize(businessName($conn)) ?> manages this private workspace and creates accounts for invited users.</p>
            </div>
            <a class="public-operator-mail" href="mailto:<?= sanitize(contactEmail($conn)) ?>"><?= sanitize(contactEmail($conn)) ?></a>
            <?php // Optional operator details render only when set: an empty row
                  // would look like a missing field, not an absent one. ?>
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
        </div>
    </section>

</main>

<?php require __DIR__ . '/includes/public-footer.php'; ?>
