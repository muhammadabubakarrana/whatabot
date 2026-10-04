<?php
// One place for the session cookie hardening, used by both bootstraps.
// Duplicated security configuration is the worst kind to let drift — the next
// change to the cookie policy must apply to public pages and the app alike.
//
// $resumeOnly: the public pages (landing, privacy, terms, contact) pass true.
// They only read the session to recognise an already-signed-in visitor, and
// such a visitor always arrives carrying the cookie — a request without one is
// a guest who should see the landing page, not a session cookie issued for no
// functional purpose (and crawlers would each get one). init.php passes false:
// the app still starts a session unconditionally, as before.
function startAppSession($resumeOnly = false) {
    if (session_status() !== PHP_SESSION_NONE) return;
    if ($resumeOnly && !isset($_COOKIE[session_name()])) return;

    // Secure so the cookie never travels over plain HTTP; HttpOnly so
    // JavaScript cannot read it; Lax so cross-site requests do not send it.
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => SESSION_SECURE,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}
