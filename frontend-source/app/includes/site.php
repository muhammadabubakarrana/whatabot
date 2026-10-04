<?php

// Public-site content — the landing page and the privacy/terms/contact pages
// every deployment ships, so the instance's public surface is more than a bare
// login form. A host that is only a credential form is what gets flagged as
// phishing; a page that names its operator, says what the service does and
// links real policies is what a legitimate deployment looks like.
//
// Same "was an env var, now admin-editable" pattern as brandName() (see the
// comment block at the top of settings.php): these keys are deliberately NOT
// in appSettingDefaults(), so an absent or blank app_settings row falls
// through to the template default, and clearing the field in the admin console
// restores it rather than leaving the page empty.
//
// The defaults describe only what this application actually does — a shared
// WhatsApp inbox, an AI chatbot, appointment booking, multi-tenant plans and
// billing. They claim nothing else: no certifications, no named clients, no
// uptime guarantees. Invented filler is worse than plain text here, because
// the audience for these pages is an abuse reviewer checking whether the
// claims hold up.

// Default public copy deliberately contains no en/em dashes: commas, colons
// and periods carry the same pauses, and the seed validator below refuses
// model output that uses them, so the defaults set the pattern the gate
// enforces.
function siteHeadline(?mysqli $conn = null) {
    $value = trim((string)(overrideSetting($conn, 'site_headline') ?? ''));
    return $value !== '' ? $value : 'Your WhatsApp chats, in one place for your team.';
}

function siteIntro(?mysqli $conn = null) {
    $value = trim((string)(overrideSetting($conn, 'site_intro') ?? ''));
    return $value !== '' ? $value
        : 'Read and reply to customer messages together, without passing one phone around. If your plan includes them, add an AI assistant and appointment booking to the same conversations.';
}

// Stored as one line per card, "Title | Description"; a line with no pipe is a
// title-only card. Out: a list of ['title' =>, 'body' =>] pairs.
function siteFeatures(?mysqli $conn = null) {
    $value = trim((string)(overrideSetting($conn, 'site_features') ?? ''));
    $raw = $value !== '' ? $value : implode("\n", [
        'One inbox, shared by your team | Read incoming chats, pick up a conversation and reply without handing the phone around.',
        'Answers from your business information | Set up the AI assistant with your services and policies. It replies when you switch it on. Available on eligible plans.',
        'Bookings without a separate form | Let customers choose an appointment in chat. Set your hours and reminders first. Available when booking is enabled on your plan.',
        'Hand over to a person | When a chat needs your team, put the bot on hold and reply yourself. Available when handover is enabled on your plan.',
        'Contacts and past chats | Find a contact and read the conversation before you reply.',
        'Connect your WhatsApp number | Pair a number by QR code. Cloud API connection is available on eligible plans.',
    ]);
    $cards = [];
    foreach (preg_split('/\R/', $raw) as $line) {
        $line = trim($line);
        if ($line === '') continue;
        $parts = array_map('trim', explode('|', $line, 2));
        $cards[] = ['title' => $parts[0], 'body' => $parts[1] ?? ''];
    }
    return $cards;
}

// "How it works", the FAQ and the data-handling points are template-only by
// design: no settings, no admin fields. They describe how the software
// actually works and that is identical in every deployment, so an editable
// version mostly creates a way to make them untrue.
function siteSteps(?mysqli $conn = null) {
    return [
        ['title' => 'Get access',
         'body'  => 'The operator creates your account and sends a link to set your password. There is no public sign-up.'],
        ['title' => 'Connect a number',
         'body'  => 'Pair your WhatsApp number by QR code. Cloud API is an option on eligible plans.'],
        ['title' => 'Start replying',
         'body'  => 'Read and reply with your team. Turn on the AI assistant or appointments if your plan includes them.'],
    ];
}

function siteFaq(?mysqli $conn = null) {
    $business = businessName($conn);
    return [
        ['q' => 'Is this an official WhatsApp site?',
         'a' => 'No. ' . $business . ' is an independent workspace that connects to WhatsApp. It is not affiliated with, endorsed by, or sponsored by WhatsApp LLC or Meta Platforms, Inc.'],
        ['q' => 'Can I create an account here?',
         'a' => 'Not directly. The operator creates accounts for invited users. If you need access, use the contact details below.'],
        ['q' => 'Will the AI answer every message?',
         'a' => 'No. The assistant only replies when it is switched on. A team member can take over a conversation when handover is enabled.'],
    ];
}

// The short data-handling summary on the landing page. Each line is a claim
// the privacy template already makes in full; keep the two in step.
function siteDataPoints(?mysqli $conn = null) {
    return [
        'WhatsApp carries the messages. Its own terms and privacy policy also apply.',
        'Past chats stay in this workspace while the account is active. The account holder can delete conversations.',
        'If the AI assistant is on, message text may be sent to the chosen AI provider to draft a reply.',
    ];
}

// Screenshots uploaded under Admin → Website, as serving URLs — empty array
// when nothing is uploaded, which is the normal state: no screenshot of a live
// instance may be bundled, because the app's screens contain real customer
// conversations and phone numbers.
function siteScreenshotUrls(?mysqli $conn = null) {
    $urls = [];
    foreach (siteScreenshotKinds() as $kind) {
        $url = brandAssetUrl($conn, $kind);
        if ($url !== '') $urls[] = $url;
    }
    return $urls;
}

// The plans section is opt-in: publishing prices is the operator's call, and a
// surprise "Plans" block on a page that used not to have one is worse than a
// checkbox. Default off.
function siteShowPlans(?mysqli $conn = null) {
    return overrideSetting($conn, 'site_show_plans') === '1';
}

// Governing-law jurisdiction for the legal pages. No default: printing a guess
// would be worse than omitting the sentence, so callers must check for ''.
function siteJurisdiction(?mysqli $conn = null) {
    return trim((string)(overrideSetting($conn, 'site_jurisdiction') ?? ''));
}

// --- Legal page bodies -------------------------------------------------------
//
// Stored and rendered as plain text with blank-line paragraph breaks — never
// HTML. An admin-editable field rendered as raw markup on a public page would
// be stored XSS, and nothing here needs markup anyway.
//
// The defaults are honest boilerplate: they describe only what this
// application really does with data, and they say plainly at the end that the
// operator should replace them with reviewed text. A template that pretends to
// be bespoke is worse than one that admits it is a template.

function sitePrivacyBody(?mysqli $conn = null) {
    $value = trim((string)(overrideSetting($conn, 'site_privacy_body') ?? ''));
    return $value !== '' ? $value : sitePrivacyTemplate($conn);
}

function siteTermsBody(?mysqli $conn = null) {
    $value = trim((string)(overrideSetting($conn, 'site_terms_body') ?? ''));
    return $value !== '' ? $value : siteTermsTemplate($conn);
}

function sitePrivacyTemplate(?mysqli $conn = null) {
    $business = businessName($conn);
    $contact = contactEmail($conn);
    $url = APP_URL;
    $days = auditRetentionDays($conn);
    return <<<TEXT
{$business} ("we", "the operator") provides a WhatsApp business-messaging workspace at {$url}. This page explains what data passes through the service and why.

What we process. Account details for each user (name, email address, password hash); the WhatsApp messages and contact phone numbers that pass through the WhatsApp account linked to the workspace; appointment details customers provide when booking; and login and administrative audit records.

Why. Everything above is processed to operate the messaging service for the account holder: delivering messages, generating automated replies where the AI assistant is enabled, taking bookings, and keeping the service secure. We do not sell personal data.

Third parties. Messages are relayed through WhatsApp and are subject to WhatsApp's own terms and privacy policy. When the AI assistant is enabled, the text of a conversation may be sent to a third-party AI provider to generate a reply; the account holder chooses the provider and can switch the assistant off.

Cookies. The site uses a session cookie to keep signed-in users signed in, and a device cookie that recognises a browser that has signed in before. There is no advertising or tracking cookie.

Retention. Audit and security records are kept for about {$days} days and then removed. Message history is kept while the account is active; the account holder can delete conversations from the inbox.

Contact. Questions about this policy or requests about your data: {$contact}.

This privacy notice is a general template provided with the software. It has not been reviewed by a lawyer and the operator should replace it with text that reflects their own circumstances.
TEXT;
}

function siteTermsTemplate(?mysqli $conn = null) {
    $business = businessName($conn);
    $contact = contactEmail($conn);
    $jurisdiction = siteJurisdiction($conn);
    $law = $jurisdiction !== ''
        ? "\n\nThese terms are governed by the laws of {$jurisdiction}."
        : '';
    return <<<TEXT
These terms govern use of the messaging workspace operated by {$business} ("the operator").

Accounts. There is no public sign-up. Accounts are created by the operator, and the operator decides who may use the service and on what plan.

Acceptable use. You must not use the service to send unsolicited bulk messages, spam, or content that is unlawful, deceptive or harmful, and you must comply with WhatsApp's own Terms of Service when messaging through a linked WhatsApp account. Breaching this may also cost you your WhatsApp account: WhatsApp enforces its own rules independently of us.

The service. The workspace relays WhatsApp messages, can automate replies with an AI assistant, and lets customers book appointments in the chat. It is provided "as is", without warranty of any kind. We do not guarantee uninterrupted availability, and we are not responsible for messages WhatsApp does not deliver.

Liability. To the extent the law allows, the operator is not liable for indirect or consequential losses arising from use of the service.

Suspension. The operator may suspend or remove an account that breaches these terms or that is used in a way that risks the service or other users.

Contact. {$contact}.{$law}

These terms are a general template provided with the software. They have not been reviewed by a lawyer and the operator should replace them with text that reflects their own circumstances.
TEXT;
}

// Renders a stored/template body as paragraphs. Plain text in, escaped HTML
// out — see the comment above the templates for why HTML is never accepted.
function siteTextToHtml($text) {
    $html = '';
    foreach (preg_split('/\R{2,}/', trim((string)$text)) as $paragraph) {
        $paragraph = trim($paragraph);
        if ($paragraph === '') continue;
        $html .= '<p>' . nl2br(sanitize($paragraph)) . '</p>';
    }
    return $html;
}

// --- AI landing-copy seed validation -----------------------------------------
//
// docker/bootstrap.php asks the FenLLM trial account to draft the landing copy
// once, at first boot. The model's answer is never trusted: this is the gate it
// must pass before anything is written. Anything it rejects leaves the template
// defaults in place, which are already correct — so rejection is cheap and
// accepting a bad draft is the only real cost.
//
// The denylist is the visible, editable statement of "claims we will not make
// for the operator" — superlatives, fake credentials, invented urgency. Kept as
// one constant so a new pattern is a one-line change.
const SITE_SEED_DENYLIST = [
    'leading', 'world-class', 'best-in-class', 'award', 'certified',
    'accredited', 'endorsed', 'official partner', 'in partnership with',
    'guarantee', 'money-back', '24/7', 'ISO 9001', 'SOC 2', 'GDPR compliant',
    '#1', 'trusted by', 'revolutionary', 'cutting-edge',
];

// Returns ['headline' =>, 'intro' =>, 'features' => [4 strings]] or null.
// $defaults is the current template copy — passed so the caller's context is
// here and so a field the model echoed back verbatim can be told apart from a
// draft if this ever needs to log it.
function siteValidateSeed($json, array $defaults, $businessName) {
    $json = trim((string)$json);
    if ($json === '') return null;
    // Models add ```json fences despite instructions — strip one pair.
    $json = preg_replace('/^```(?:json)?\s*/', '', $json);
    $json = preg_replace('/\s*```$/', '', $json);

    $data = json_decode($json, true);
    if (!is_array($data)) return null;

    $headline = trim((string)($data['headline'] ?? ''));
    $intro = trim((string)($data['intro'] ?? ''));
    if ($headline === '' || mb_strlen($headline) > 120) return null;
    if ($intro === '' || mb_strlen($intro) > 600) return null;

    $features = $data['features'] ?? null;
    if (!is_array($features) || count($features) !== 4) return null;
    $features = array_map(fn($f) => is_string($f) ? trim($f) : '', $features);
    foreach ($features as $f) {
        if ($f === '' || mb_strlen($f) > 100) return null;
    }

    // Whole-response rejection, not per-field: half-invented copy is still
    // invented copy.
    foreach (array_merge([$headline, $intro], $features) as $field) {
        foreach (SITE_SEED_DENYLIST as $term) {
            if (mb_stripos($field, $term) !== false) return null;
        }
        // A digit next to % or a currency symbol is an invented statistic or
        // invented pricing — the failure mode that would most embarrass the
        // operator.
        if (preg_match('/\d\s*[%£$€₹]|[£$€₹]\s*\d/', $field)) return null;
        // En/em dashes are refused in public copy: the shipped defaults use
        // plain punctuation, and accepting a typographic habit the templates
        // avoid would let generated copy drift from the site's voice.
        if (preg_match('/\x{2013}|\x{2014}/u', $field)) return null;
    }

    // "Meta" or "WhatsApp Inc/LLC" in the pitch implies corporate identity.
    // Plain "WhatsApp" stays allowed — it is the factual description of what
    // the app connects to.
    if (preg_match('/\bMeta\b|WhatsApp\s+(?:Inc|LLC)/i', $headline . ' ' . $intro)) return null;

    // Substance: the denylist above catches copy that is untrue, this catches
    // copy that is empty — the observed failure is a headline that is just the
    // business name with the informative half thrown away. Both end the same
    // way: the whole response is rejected, because the template default is
    // always an acceptable answer and a half-model half-template result is
    // harder to reason about than either.
    if (mb_strlen($headline) < 20) return null;
    // preg_quote: the business name is an admin-set string, and an unescaped
    // "A+B (Pvt.) Ltd." in a pattern is both a wrong match and a way for that
    // string to break the validator.
    $rest = trim(preg_replace('/' . preg_quote($businessName, '/') . '/i', '', $headline));
    $rest = trim($rest, " \t\n\r\0\x0B-–—:.,|");
    if (mb_strlen($rest) < 12) return null;

    return ['headline' => $headline, 'intro' => $intro, 'features' => $features];
}

// The settings row's own updated_at, for a "last updated" line that tells the
// truth. Null when there is no row or no database — a policy that prints
// today's date claims to change daily, which is a tell, so callers omit the
// line rather than guess.
function siteSettingUpdatedAt(?mysqli $conn, $key) {
    $db = settingsConn($conn);
    if (!$db) return null;
    try {
        $stmt = $db->prepare(
            "SELECT updated_at FROM app_settings WHERE setting_key = ? AND setting_value IS NOT NULL AND setting_value <> ''"
        );
        $stmt->bind_param('s', $key);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ? $row['updated_at'] : null;
    } catch (mysqli_sql_exception $e) {
        error_log('siteSettingUpdatedAt failed: ' . $e->getMessage());
        return null;
    }
}
