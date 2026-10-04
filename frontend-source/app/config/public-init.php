<?php
// Bootstrap for the public, unauthenticated pages: the landing page, privacy,
// terms and contact.
//
// Deliberately NOT config/init.php. That one loads the whole application and
// hard-exits when MySQL is unreachable — correct for pages that can do nothing
// without it, but these pages must render anyway: a first-boot or mid-upgrade
// request to / that 503s is indistinguishable from a broken install, and the
// only thing worse than a bare login page is an error page. The database is
// therefore optional here: with no connection every accessor falls back to
// its env constant or template default, which is exactly what the page should
// show.

$basePath = dirname(__DIR__);

require_once $basePath . '/config/app.php';
require_once $basePath . '/includes/functions.php';
require_once $basePath . '/includes/settings.php';
require_once $basePath . '/includes/branding.php';
require_once $basePath . '/includes/site.php';
// plan.php is function definitions only — its two public-page callers
// (getActivePlans, formatPrice) need nothing beyond mysqli and settings.php's
// appCurrency/formatMoney, so loading it costs this bootstrap no queries.
require_once $basePath . '/includes/plan.php';
require_once $basePath . '/includes/auth.php';

// Resume an existing session only: the landing page reads it to recognise a
// signed-in visitor, who always arrives carrying the cookie. A request without
// one is a guest, and a guest gets no Set-Cookie — a cookie issued to every
// crawler for no functional purpose would also contradict the privacy page,
// which says the session cookie exists to keep signed-in users signed in.
require_once $basePath . '/config/session.php';
startAppSession(true);

// Throwing mode — the same mysqli_report database.php sets. Without it the
// try/catch (mysqli_sql_exception) blocks in appSettings() and brandAssets()
// that tolerate a missing table become dead code: the query returns false and
// the warning lands in the page (or the log), which is exactly the first-boot
// scenario this file exists to handle.
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Best-effort connection. Enough for the admin overrides to win when the
// database is up; null when it is not, which every accessor tolerates.
// database.php's retry loop is the wrong trade-off here — a public page
// waiting 20s for MySQL is no better than a 503 — but a bare `new mysqli`
// still blocks on the default connect timeout, so the timeout is set
// explicitly instead. 2s is low on purpose: the fallback content is already
// correct, so waiting longer buys nothing.
$conn = null;
if (env('MYSQL_USER') !== null && env('MYSQL_PASSWORD') !== null) {
    try {
        $db = mysqli_init();
        mysqli_options($db, MYSQLI_OPT_CONNECT_TIMEOUT, 2);
        $db->real_connect(
            env('MYSQL_HOST', 'mysql'), env('MYSQL_USER'), env('MYSQL_PASSWORD'),
            env('MYSQL_DATABASE', 'whatsapp_saas'), (int)env('MYSQL_PORT', 3306)
        );
        $db->set_charset('utf8mb4');
        $conn = $db;
    } catch (Throwable $e) {
        // Logged, not swallowed: falling back to the defaults is correct, but
        // silently is not — an operator whose saved content has stopped
        // appearing needs to find the reason somewhere.
        error_log('public page: database unavailable, using defaults: ' . $e->getMessage());
        $conn = null;
    }
}
