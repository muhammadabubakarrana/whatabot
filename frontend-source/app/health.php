<?php
// Container healthcheck endpoint. Reveals liveness and the image version only —
// the version is already public information (it is the image tag) and the
// update script needs it to report "old → new". No database name, no backend
// address.
//
// app-version.php is included directly rather than via the admin bootstrap:
// this endpoint is unauthenticated and must stay cheap.
require_once __DIR__ . '/includes/app-version.php';
header('Content-Type: application/json');
echo json_encode(['ok' => true, 'version' => appVersion()]);
