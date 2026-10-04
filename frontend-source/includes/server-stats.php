<?php

// Host-level resources for the admin overview card.
//
// The numbers are deliberately host-wide, not container-scoped: /proc inside
// the frontend container reflects the student's whole VPS, which is the box
// the admin is actually worried about. On anything without /proc (macOS dev,
// Windows) every read fails closed and the card says "Not available".
//
// fmtBytes() also lives here so admin/system.php and the card share one
// formatter — it used to be declared inside system.php, which meant any other
// page that needed it would have had to copy it.

function fmtBytes($bytes) {
    if ($bytes === null || !is_numeric($bytes)) return '—';
    $b = (float)$bytes;
    if ($b >= 1073741824) return round($b / 1073741824, 1) . ' GB';
    if ($b >= 1048576) return round($b / 1048576, 1) . ' MB';
    return round($b / 1024) . ' KB';
}

// One sample of the aggregate cpu line from /proc/stat: [idle, total] jiffies.
// idle counts iowait too — a box waiting on disk is not doing work.
function procStatSample() {
    $line = @file_get_contents('/proc/stat', false, null, 0, 4096);
    if ($line === false) return null;
    $line = strtok($line, "\n");
    if ($line === false || !preg_match('/^cpu\s+(.*)$/', trim($line), $m)) return null;
    $parts = preg_split('/\s+/', trim($m[1]));
    if (!$parts || count($parts) < 5) return null;
    $idle = (float)$parts[3] + (float)($parts[4] ?? 0);
    $total = 0;
    foreach ($parts as $p) $total += (float)$p;
    return [$idle, $total];
}

function serverResourceStats(): array {
    $out = [
        'ok'   => false,
        'mem'  => ['total' => null, 'used' => null],
        'cpu'  => ['cores' => 0, 'percent' => null, 'load1' => null],
        'disk' => ['total' => null, 'used' => null],
        'at'   => gmdate('c'),
    ];

    $meminfo = @file_get_contents('/proc/meminfo');
    if ($meminfo === false) return $out;   // not Linux: nothing below applies
    $out['ok'] = true;

    if (preg_match('/^MemTotal:\s+(\d+)\s*kB/m', $meminfo, $m)) {
        $out['mem']['total'] = (int)$m[1] * 1024;
    }
    // MemAvailable, not MemFree: the kernel's page cache is reclaimable, and
    // MemFree would report a Linux box as permanently near-full.
    if (preg_match('/^MemAvailable:\s+(\d+)\s*kB/m', $meminfo, $m) && $out['mem']['total']) {
        $out['mem']['used'] = $out['mem']['total'] - (int)$m[1] * 1024;
    }

    $cpuinfo = @file_get_contents('/proc/cpuinfo');
    if ($cpuinfo !== false) {
        $out['cpu']['cores'] = max(1, preg_match_all('/^processor\s*:/m', $cpuinfo));
    } else {
        $out['cpu']['cores'] = 1;
    }

    // CPU% is only meaningful between two reads — a single /proc/stat snapshot
    // is the cumulative average since boot, which flattens a busy minute into
    // noise. The 200ms sleep is the cost of a real answer.
    $a = procStatSample();
    if ($a !== null) {
        usleep(200000);
        $b = procStatSample();
        if ($b !== null && $b[1] > $a[1]) {
            $out['cpu']['percent'] = round(100 * (1 - ($b[0] - $a[0]) / ($b[1] - $a[1])), 1);
        }
    }

    $load = @file_get_contents('/proc/loadavg');
    if ($load !== false) {
        $out['cpu']['load1'] = (float)strtok($load, ' ');
    }

    $dt = @disk_total_space('/');
    $df = @disk_free_space('/');
    if ($dt !== false && $df !== false) {
        $out['disk']['total'] = (int)$dt;
        $out['disk']['used'] = (int)($dt - $df);
    }

    return $out;
}
