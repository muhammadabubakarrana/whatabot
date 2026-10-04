<?php

// Instance version + "is there an update" check.
//
// The image's APP_VERSION is baked in at build time (ARG in the Dockerfiles):
// vX.Y.Z on tag builds, sha-<short> on pushes to main, dev on a local build.
// That string decides which channel the instance is on — only release-shaped
// versions track the stable feed; a sha- build updates on every `latest` push
// and asking a development build whether a tagged release is newer is noise.
//
// The feed is latest.json at the deploy repo root, written by the release job
// in build.yml. It is public by design: it carries a version string and
// release notes, nothing instance-specific.

const APP_UPDATE_FEED_URL = 'https://raw.githubusercontent.com/shahzad11/whatsapp-saas-deploy/main/latest.json';
const APP_UPDATE_COMMAND = 'curl -fsSL https://raw.githubusercontent.com/shahzad11/whatsapp-saas-deploy/main/deploy/update.sh | bash';
// Six hours: frequent enough that an admin sees a new release the same day,
// rare enough that every sidebar render is not a network call (see the 'cached'
// mode — the sidebar never fetches at all).
const APP_UPDATE_CACHE_SECONDS = 21600;

function appVersion(): string {
    $v = trim((string)getenv('APP_VERSION'));
    return $v === '' ? 'dev' : $v;
}

// What a release looks like. 'v1.2.3' and '1.2.3' both count — the tag and the
// version_compare input differ only in the prefix.
function appVersionIsRelease(string $v): bool {
    return (bool)preg_match('/^v?\d+\.\d+\.\d+$/', $v);
}

function appVersionNewer(string $latest, string $current): bool {
    if (!appVersionIsRelease($latest) || !appVersionIsRelease($current)) {
        return false;
    }
    return version_compare(ltrim($latest, 'v'), ltrim($current, 'v'), '>');
}

// Fetch the feed once. Pure-ish: no database, no cache — caching decisions are
// appUpdateStatus()'s job, so this is also what a forced check calls.
//
// Timeouts are deliberately short: this runs on an admin page render, and a
// GitHub that is down must cost the admin five seconds, not thirty.
function appUpdateFetchFeed(): array {
    $ch = curl_init(APP_UPDATE_FEED_URL);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 3,
        CURLOPT_TIMEOUT => 5,
        CURLOPT_FOLLOWLOCATION => false,
        // GitHub's raw endpoint 403s some requests with no UA at all.
        CURLOPT_USERAGENT => 'whatsapp-saas-update-check/' . appVersion(),
    ]);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);

    if ($body === false || $code !== 200) {
        return ['ok' => false, 'version' => null, 'published_at' => null, 'notes' => '',
                'error' => $err !== '' ? $err : 'HTTP ' . $code];
    }
    $json = json_decode((string)$body, true);
    if (!is_array($json) || empty($json['version'])) {
        return ['ok' => false, 'version' => null, 'published_at' => null, 'notes' => '',
                'error' => 'unrecognised feed'];
    }
    return [
        'ok'           => true,
        'version'      => (string)$json['version'],
        'published_at' => (string)($json['published_at'] ?? ''),
        'notes'        => mb_substr((string)($json['notes'] ?? ''), 0, 600),
        'error'        => '',
    ];
}

// The cached/auto/force resolution. The cache lives in app_settings — three
// rows, written with setAppSetting() like every other instance setting.
//
// Modes:
//   'cached' — read the cache only, never touch the network. The sidebar calls
//     this on every admin page; a blocked outbound connection cannot be allowed
//     to hang every page load.
//   'auto'   — refresh when the cache is older than APP_UPDATE_CACHE_SECONDS or
//     absent. What overview/system pages use.
//   'force'  — refresh now ("Check now" button).
//
// A failed fetch keeps the previous feed rather than blanking it — one GitHub
// outage must not make a genuinely-available update vanish — but the error is
// still recorded so the card can say "last check failed".
function appUpdateStatus(mysqli $conn, string $mode = 'auto'): array {
    $current = appVersion();

    $cachedJson = (string)appSetting($conn, 'update_feed_json', '');
    $feed = $cachedJson !== '' ? json_decode($cachedJson, true) : null;
    if (!is_array($feed)) $feed = null;

    $checkedAt = (string)appSetting($conn, 'update_checked_at', '');
    $lastError = (string)appSetting($conn, 'update_check_error', '');

    $stale = $checkedAt === ''
        || (time() - strtotime($checkedAt . ' UTC')) > APP_UPDATE_CACHE_SECONDS;

    if ($mode === 'force' || ($mode === 'auto' && $stale)) {
        $fresh = appUpdateFetchFeed();
        if ($fresh['ok']) {
            $feed = $fresh;
            $lastError = '';
            try {
                setAppSetting($conn, 'update_feed_json', json_encode([
                    'version'      => $fresh['version'],
                    'published_at' => $fresh['published_at'],
                    'notes'        => $fresh['notes'],
                ]));
                setAppSetting($conn, 'update_check_error', '');
            } catch (Throwable $e) {
                error_log('update feed cache write failed: ' . $e->getMessage());
            }
        } else {
            $lastError = $fresh['error'];
            try {
                setAppSetting($conn, 'update_check_error', $fresh['error']);
            } catch (Throwable $e) {
                error_log('update check error write failed: ' . $e->getMessage());
            }
        }
        $checkedAt = gmdate('Y-m-d H:i:s');
        try {
            setAppSetting($conn, 'update_checked_at', $checkedAt);
        } catch (Throwable $e) {
            error_log('update check timestamp write failed: ' . $e->getMessage());
        }
    }

    $isStable = appVersionIsRelease($current);
    $latest = is_array($feed) && !empty($feed['version']) ? (string)$feed['version'] : null;

    return [
        'current'      => $current,
        // Dev builds (sha-…, dev, local) are the 'development' channel: they
        // update on every push to main and are never compared against releases.
        'channel'      => $isStable ? 'stable' : 'development',
        'latest'       => $latest,
        'published_at' => is_array($feed) ? (string)($feed['published_at'] ?? '') : '',
        'notes'        => is_array($feed) ? (string)($feed['notes'] ?? '') : '',
        'available'    => $isStable && $latest !== null && appVersionNewer($latest, $current),
        'checked_at'   => $checkedAt,
        'error'        => $lastError,
        'command'      => APP_UPDATE_COMMAND,
    ];
}
