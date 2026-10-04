<?php
// Configuration comes from the process environment, never from a file inside
// the DocumentRoot. Under Docker these are supplied by compose; the old
// hardcoded credentials in config/database.php are gone.
//
// An optional .env file is read for local development only. It is looked for
// one level ABOVE the application root so it can never be served over HTTP,
// even if every other protection fails.

function loadDotEnvOnce() {
    static $loaded = false;
    if ($loaded) return;
    $loaded = true;

    $path = dirname(__DIR__, 2) . '/.env';
    if (!is_readable($path)) return;

    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        if (!str_contains($line, '=')) continue;

        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);

        // Strip one layer of matching quotes.
        $len = strlen($value);
        if ($len >= 2 && (($value[0] === '"' && $value[$len - 1] === '"') || ($value[0] === "'" && $value[$len - 1] === "'"))) {
            $value = substr($value, 1, -1);
        }

        // Real environment variables always win over the file.
        if (getenv($key) === false) {
            putenv("$key=$value");
            $_ENV[$key] = $value;
        }
    }
}

function env($key, $default = null) {
    $value = getenv($key);
    if ($value === false || $value === '') {
        return $default;
    }
    return $value;
}

function envBool($key, $default = false) {
    $value = env($key);
    if ($value === null) return $default;
    return in_array(strtolower($value), ['1', 'true', 'yes', 'on'], true);
}

// A neutral product name derived from the app host, so a fresh deployment is
// never branded "WhatsApp" anything out of the box — a bare login page under
// that name, with the WhatsApp glyph, is indistinguishable from a credential
// phish and is what has gotten these deployments suspended by their hosts.
// Mirrored by derive_app_name() in deploy/install.sh and deploy/update.sh;
// the three copies must stay in step (the deploy scripts are fetched
// standalone and cannot share a file with this one).
//
// The first remaining label is taken rather than the registrable domain —
// correct for example.com and example.co.uk alike, which is why there is no
// public-suffix list here: the value typed at install is the apex the
// deployer owns. A label that reads as machine-generated (Hostinger's
// srv123456.hstgr.cloud) would make a terrible name, so the next label along
// is used instead — but only while it is not itself the last label, a bare
// TLD being nobody's name.
function defaultAppNameFromHost($host) {
    $host = strtolower(trim((string)$host));
    // A leading `app` label is our own subdomain convention, not part of the
    // name. Only one is stripped: in app.app.com the second `app` is the name.
    if (str_starts_with($host, 'app.')) $host = substr($host, 4);
    $label = explode('.', $host)[0];
    if (preg_match('/^(srv|vps|vmi|node|host|server)?[0-9]{3,}$/', $label)) {
        $rest = substr($host, strlen($label) + 1);
        if (str_contains($rest, '.')) $label = explode('.', $rest)[0];
    }
    $words = array_filter(explode('-', $label), fn($w) => $w !== '');
    $name = implode(' ', array_map('ucfirst', $words));
    // "Messaging Hub", never anything containing "WhatsApp".
    if ($name === '') $name = 'Messaging Hub';
    // The cap matches the brand_name validation in admin/branding.php.
    return mb_substr($name, 0, 100);
}

// Secrets have no safe default. Guessing one produces an app that boots and is
// silently insecure, which is worse than one that refuses to start.
function envRequired($key) {
    $value = env($key);
    if ($value === null) {
        http_response_code(500);
        error_log("FATAL: required environment variable $key is not set");
        exit('Server misconfigured.');
    }
    return $value;
}

loadDotEnvOnce();
