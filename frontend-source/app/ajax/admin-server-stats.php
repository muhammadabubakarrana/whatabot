<?php
// Live host stats for the overview's Server resources card.
//
// Same guard shape as ajax/leads-search.php: this file sits outside admin/,
// so it does the admin check itself and answers JSON rather than redirecting.
// Read-only and cheap — the only cost is /proc plus disk statfs — but the
// check stays explicit because "harmless" is not a reason to open a route.
require_once dirname(__DIR__) . '/config/init.php';

header('Content-Type: application/json');

$user = requireActiveUserJson();
if ((int)$user['is_admin'] !== 1) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Forbidden']);
    exit;
}

// Loaded by hand: config/init.php deliberately does not carry the admin-only
// layer (see admin-init.php).
require_once dirname(__DIR__) . '/includes/server-stats.php';

echo json_encode(serverResourceStats());
