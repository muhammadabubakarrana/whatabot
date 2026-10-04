<?php
// Public-site content — the landing page copy and the privacy/terms bodies.
//
// Same semantics as admin/branding.php: a saved value overrides the built-in
// default the accessor falls back to, and a blank field restores it. Blank is
// the reset mechanism — there are deliberately no separate reset buttons.
//
// Validation is length only. These values are rendered by siteTextToHtml() as
// escaped plain text — HTML is never accepted or rendered — so there is no tag
// allowlist and no HTML sanitising here; escaping happens at output.
require_once dirname(__DIR__) . '/includes/admin-init.php';

$errors = [];
$self = APP_URL . '/admin/website.php';

$stored = [
    'site_headline'     => (string)(overrideSetting($conn, 'site_headline') ?? ''),
    'site_intro'        => (string)(overrideSetting($conn, 'site_intro') ?? ''),
    'site_features'     => (string)(overrideSetting($conn, 'site_features') ?? ''),
    'site_jurisdiction' => (string)(overrideSetting($conn, 'site_jurisdiction') ?? ''),
    'site_privacy_body' => (string)(overrideSetting($conn, 'site_privacy_body') ?? ''),
    'site_terms_body'   => (string)(overrideSetting($conn, 'site_terms_body') ?? ''),
    'site_show_plans'   => (string)(overrideSetting($conn, 'site_show_plans') ?? ''),
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    formRequireCsrf($self);

    // Screenshot removal is its own action on its own form — the same reason
    // admin/branding.php does it: a submit button inside the main form becomes
    // the default, so Enter in a text field would have deleted a shot.
    if (($_POST['action'] ?? 'save') === 'remove_shot') {
        $kind = (string)($_POST['kind'] ?? '');
        if (!in_array($kind, siteScreenshotKinds(), true)) {
            formRespond(false, 'Unknown image.', $self);
        }
        brandDeleteAsset($conn, $kind);
        logAudit($conn, 'admin.website.shot_removed', 'brand_assets', $kind);
        formRespond(true, 'Screenshot removed.', $self);
    }

    $headline     = trim((string)($_POST['site_headline'] ?? ''));
    $intro        = trim((string)($_POST['site_intro'] ?? ''));
    $features     = trim((string)($_POST['site_features'] ?? ''));
    $jurisdiction = trim((string)($_POST['site_jurisdiction'] ?? ''));
    $privacy      = trim((string)($_POST['site_privacy_body'] ?? ''));
    $terms        = trim((string)($_POST['site_terms_body'] ?? ''));
    $showPlans    = isset($_POST['site_show_plans']);

    if (mb_strlen($headline) > 120)     $errors['site_headline'] = 'Max 120 characters.';
    if (mb_strlen($intro) > 600)        $errors['site_intro'] = 'Max 600 characters.';
    if (mb_strlen($jurisdiction) > 80)  $errors['site_jurisdiction'] = 'Max 80 characters.';
    if (mb_strlen($privacy) > 20000)    $errors['site_privacy_body'] = 'Max 20,000 characters.';
    if (mb_strlen($terms) > 20000)      $errors['site_terms_body'] = 'Max 20,000 characters.';

    // One feature card per line, "Title | Description", at most 8 lines of
    // 120 characters — and the error names the offending line rather than just
    // "too long".
    if ($features !== '') {
        $lines = array_values(array_filter(array_map('trim', preg_split('/\R/', $features)), fn($l) => $l !== ''));
        if (count($lines) > 8) {
            $errors['site_features'] = 'At most 8 lines — you have ' . count($lines) . '.';
        } else {
            foreach ($lines as $i => $line) {
                if (mb_strlen($line) > 120) {
                    $errors['site_features'] = 'Line ' . ($i + 1) . ' is over 120 characters.';
                    break;
                }
            }
        }
        // Normalise to the trimmed, non-empty lines, so blank lines an admin
        // left in the middle don't get stored.
        $features = implode("\n", $lines);
    }

    // Read all uploads before writing anything: a valid text edit should not
    // be saved and then reported as a failure because a "screenshot" was a PDF.
    $uploads = [];
    foreach (siteScreenshotKinds() as $kind) {
        $field = $kind . '_file';
        if (!isset($_FILES[$field])) continue;
        [$asset, $err] = brandReadUpload($_FILES[$field]);
        if ($err !== null) { $errors[$field] = $err; continue; }
        if ($asset !== null) $uploads[$kind] = $asset;
    }

    if (!$errors) {
        setAppSetting($conn, 'site_headline', $headline);
        setAppSetting($conn, 'site_intro', $intro);
        setAppSetting($conn, 'site_features', $features);
        setAppSetting($conn, 'site_jurisdiction', $jurisdiction);
        setAppSetting($conn, 'site_privacy_body', $privacy);
        setAppSetting($conn, 'site_terms_body', $terms);
        setAppSetting($conn, 'site_show_plans', $showPlans ? '1' : '0');

        foreach ($uploads as $kind => $asset) {
            brandStoreAsset($conn, $kind, $asset['bytes'], $asset['mime']);
        }

        logAudit($conn, 'admin.website.update', 'app_settings', null, [
            'headline' => $headline !== '' ? $headline : '(default)',
            'features_lines' => $features === '' ? 0 : count(explode("\n", $features)),
            'privacy' => $privacy !== '' ? 'custom' : '(template)',
            'terms' => $terms !== '' ? 'custom' : '(template)',
            'show_plans' => $showPlans,
            'uploaded' => array_keys($uploads),
        ]);

        formRespond(true, 'Site content saved.', $self);
    }

    formErrors('Please correct the highlighted fields.', $errors);

    $stored = [
        'site_headline' => $headline, 'site_intro' => $intro,
        'site_features' => $features, 'site_jurisdiction' => $jurisdiction,
        'site_privacy_body' => $privacy, 'site_terms_body' => $terms,
        'site_show_plans' => $showPlans ? '1' : '0',
    ];
}

$shotAssets = brandAssets($conn, true);

function wErr($k) { global $errors; return empty($errors[$k]) ? '' : '<div class="invalid-feedback d-block">' . sanitize($errors[$k]) . '</div>'; }
function wCls($k) { global $errors; return empty($errors[$k]) ? '' : ' is-invalid'; }

$pageTitle = 'Website';
require_once dirname(__DIR__) . '/includes/admin-header.php';
?>

<?php // enctype is required for the screenshot file inputs; the AJAX path
      // sends the same FormData, so one form serves both. ?>
<form method="POST" enctype="multipart/form-data" data-ajax>
    <?= csrfField() ?>

    <div class="card mb-4">
        <div class="card-header">Landing page</div>
        <div class="card-body">
            <?php // site_seed_status 'done' means the copy below was drafted by
                  // the FenLLM trial account at install — good enough to ship,
                  // not good enough to leave unreviewed. ?>
            <?php if (appSetting($conn, 'site_seed_status', '') === 'done'): ?>
                <div class="alert alert-info">
                    The copy on this card was drafted automatically at install.
                    Review it and edit anything that does not fit your business.
                </div>
            <?php endif; ?>
            <div class="mb-3">
                <label class="form-label">Headline</label>
                <input type="text" name="site_headline" class="form-control<?= wCls('site_headline') ?>"
                       value="<?= sanitize($stored['site_headline']) ?>" maxlength="120"
                       placeholder="<?= sanitize(siteHeadline($conn)) ?>">
                <div class="form-text">
                    The large heading on the public home page. Leave blank to use the default.
                </div>
                <?= wErr('site_headline') ?>
            </div>
            <div class="mb-3">
                <label class="form-label">Introduction</label>
                <textarea name="site_intro" rows="3" class="form-control<?= wCls('site_intro') ?>"
                          maxlength="600" placeholder="<?= sanitize(siteIntro($conn)) ?>"><?= sanitize($stored['site_intro']) ?></textarea>
                <div class="form-text">
                    One or two sentences under the headline describing what the service does.
                    Leave blank to use the default.
                </div>
                <?= wErr('site_intro') ?>
            </div>
            <div class="mb-0">
                <label class="form-label">Features</label>
                <?php // siteFeatures() returns card pairs; the stored format and
                      // this placeholder show the raw "Title | Description"
                      // lines those cards come from. ?>
                <textarea name="site_features" rows="8" class="form-control<?= wCls('site_features') ?>"
                          placeholder="<?= sanitize(implode("\n", array_map(fn($f) => $f['body'] !== '' ? $f['title'] . ' | ' . $f['body'] : $f['title'], siteFeatures($conn)))) ?>"><?= sanitize($stored['site_features']) ?></textarea>
                <div class="form-text">
                    One card per line as "Title | Description" (the pipe is optional, a line
                    without one is a title-only card). At most 8 lines of 120 characters.
                    Only list things this installation actually does. Leave blank to use the defaults.
                </div>
                <?= wErr('site_features') ?>
            </div>
            <div class="form-check mt-3">
                <input type="checkbox" name="site_show_plans" value="1" id="siteShowPlans"
                       class="form-check-input" <?= $stored['site_show_plans'] === '1' ? 'checked' : '' ?>>
                <label class="form-check-label" for="siteShowPlans">
                    Show plans on the landing page
                </label>
                <div class="form-text">
                    Off by default. Turning this on publishes the active plans and their
                    prices from <a href="<?= APP_URL ?>/admin/plans.php">Admin → Plans</a>
                    to anyone who visits the site — the section is hidden again the moment
                    you switch it off.
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">Screenshots</div>
        <div class="card-body">
            <div class="alert alert-info">
                Only upload screenshots that are safe to publish — no customer names,
                phone numbers or real conversations. Nothing shows on the landing page
                until you upload at least one image.
            </div>
            <div class="row g-3">
                <?php foreach (siteScreenshotKinds() as $i => $kind): ?>
                    <div class="col-md-4">
                        <label class="form-label">Screenshot <?= $i + 1 ?></label>
                        <?php if (isset($shotAssets[$kind])): ?>
                            <div class="border rounded p-2 mb-2 text-center">
                                <img src="<?= sanitize(brandAssetUrl($conn, $kind)) ?>"
                                     alt="Current screenshot <?= $i + 1 ?>"
                                     style="max-height:120px;max-width:100%;">
                            </div>
                        <?php endif; ?>
                        <input type="file" name="<?= $kind ?>_file"
                               class="form-control<?= wCls($kind . '_file') ?>"
                               accept="image/png,image/jpeg,image/gif,image/webp">
                        <div class="form-text">
                            PNG, JPEG, GIF or WebP, up to <?= round(BRAND_MAX_ASSET_BYTES / 1024) ?> KB.
                        </div>
                        <?= wErr($kind . '_file') ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php foreach (siteScreenshotKinds() as $i => $kind): ?>
            <?php if (isset($shotAssets[$kind])): ?>
                <div class="card-footer bg-white">
                    <?php // Its own form, same as admin/branding.php: a submit
                          // button inside the main form becomes the default, so
                          // Enter in a text field would delete the screenshot. ?>
                    <button class="btn btn-sm btn-outline-danger" type="submit"
                            form="removeShot<?= $i + 1 ?>Form"
                            data-confirm="Remove screenshot <?= $i + 1 ?>?">
                        Remove screenshot <?= $i + 1 ?>
                    </button>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>

    <div class="card mb-4">
        <div class="card-header">Legal pages</div>
        <div class="card-body">
            <div class="alert alert-warning">
                The shipped Privacy and Terms text is a general template. It has not been
                reviewed by a lawyer — replace it with text that fits your own business
                and your country's law before you rely on it.
            </div>
            <div class="mb-3">
                <label class="form-label">Jurisdiction</label>
                <input type="text" name="site_jurisdiction" class="form-control<?= wCls('site_jurisdiction') ?>"
                       value="<?= sanitize($stored['site_jurisdiction']) ?>" maxlength="80"
                       placeholder="e.g. England and Wales">
                <div class="form-text">
                    Supplies the governing-law sentence in the terms ("governed by the laws of …").
                    Leave blank and that sentence is omitted entirely.
                </div>
                <?= wErr('site_jurisdiction') ?>
            </div>
            <div class="mb-3">
                <label class="form-label">Privacy page body</label>
                <textarea name="site_privacy_body" rows="12" class="form-control<?= wCls('site_privacy_body') ?>"
                          maxlength="20000" placeholder="<?= sanitize(sitePrivacyBody($conn)) ?>"><?= sanitize($stored['site_privacy_body']) ?></textarea>
                <div class="form-text">
                    Plain text; a blank line starts a new paragraph — HTML is not rendered.
                    Shown at <?= sanitize(APP_URL) ?>/privacy.php. Leave blank to use the template.
                </div>
                <?= wErr('site_privacy_body') ?>
            </div>
            <div class="mb-0">
                <label class="form-label">Terms page body</label>
                <textarea name="site_terms_body" rows="12" class="form-control<?= wCls('site_terms_body') ?>"
                          maxlength="20000" placeholder="<?= sanitize(siteTermsBody($conn)) ?>"><?= sanitize($stored['site_terms_body']) ?></textarea>
                <div class="form-text">
                    Plain text; a blank line starts a new paragraph — HTML is not rendered.
                    Shown at <?= sanitize(APP_URL) ?>/terms.php. Leave blank to use the template.
                </div>
                <?= wErr('site_terms_body') ?>
            </div>
        </div>
    </div>

    <button type="submit" class="btn btn-primary">Save site content</button>
</form>

<p class="text-muted small mt-3 mb-0">
    Business name, contact email, logo and favicon live under
    <a href="<?= APP_URL ?>/admin/branding.php">Admin → Branding</a>.
</p>

<?php // Outside the form above — nested forms are invalid HTML and the browser
      // drops the inner one's fields entirely. ?>
<?php foreach (siteScreenshotKinds() as $i => $kind): ?>
    <?php if (isset($shotAssets[$kind])): ?>
        <form method="POST" id="removeShot<?= $i + 1 ?>Form" class="d-none" data-ajax>
            <?= csrfField() ?>
            <input type="hidden" name="action" value="remove_shot">
            <input type="hidden" name="kind" value="<?= $kind ?>">
        </form>
    <?php endif; ?>
<?php endforeach; ?>

<?php require_once dirname(__DIR__) . '/includes/admin-footer.php'; ?>
