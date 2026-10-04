// Polls ajax/admin-server-stats.php and repaints the Server resources card on
// admin/index.php (#35). Enhancement only: the first paint is server-rendered,
// so without this file the card is a still picture instead of a live one.
(function () {
    'use strict';

    var card = document.getElementById('serverResources');
    if (!card) return;
    var INTERVAL = 15000;

    function fmtBytes(b) {
        if (b === null || b === undefined || isNaN(b)) return '—';
        if (b >= 1073741824) return (b / 1073741824).toFixed(1) + ' GB';
        if (b >= 1048576) return (b / 1048576).toFixed(1) + ' MB';
        return Math.round(b / 1024) + ' KB';
    }

    function colour(pct) {
        if (pct === null || isNaN(pct)) return 'bg-secondary';
        return pct < 70 ? 'bg-success' : (pct < 90 ? 'bg-warning' : 'bg-danger');
    }

    function paint(row, pct, text) {
        if (!row) return;
        var bar = row.querySelector('[data-role="bar"]');
        if (bar) {
            var p = pct === null ? 0 : pct;
            bar.style.width = p + '%';
            bar.setAttribute('aria-valuenow', p);
            bar.className = 'progress-bar ' + colour(pct);
        }
        var label = row.querySelector('[data-role="text"]');
        if (label && text !== null) label.textContent = text;
    }

    function apply(res) {
        if (!res || !res.ok) return;

        var memPct = res.mem.total > 0 ? res.mem.used / res.mem.total * 100 : null;
        paint(card.querySelector('[data-role="row-mem"]'), memPct,
            fmtBytes(res.mem.used) + ' of ' + fmtBytes(res.mem.total));

        var cpuText = (res.cpu.percent !== null ? res.cpu.percent + '%' : '—')
            + ' · ' + res.cpu.cores + ' ' + (res.cpu.cores === 1 ? 'core' : 'cores')
            + (res.cpu.load1 !== null ? ' · load ' + Number(res.cpu.load1).toFixed(2) : '');
        paint(card.querySelector('[data-role="row-cpu"]'), res.cpu.percent, cpuText);

        var diskPct = res.disk.total > 0 ? res.disk.used / res.disk.total * 100 : null;
        paint(card.querySelector('[data-role="row-disk"]'), diskPct,
            fmtBytes(res.disk.used) + ' of ' + fmtBytes(res.disk.total));

        var updated = card.querySelector('[data-role="updated"]');
        if (updated) {
            updated.textContent = 'Updated ' + new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
        }
    }

    function tick() {
        // A hidden tab has no viewer for fresh numbers — pause rather than
        // accumulate pointless /proc samples and wakeups for every open tab.
        if (document.hidden) return;
        // The endpoint URL is rendered onto the card: a static script file has
        // no APP_URL of its own (the app may be deployed under a subpath).
        fetch(card.dataset.statsUrl || '../ajax/admin-server-stats.php', {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin'
        })
            .then(function (r) { return r.json(); })
            .then(apply)
            .catch(function () { /* a missed sample is not news — keep polling */ });
    }

    setInterval(tick, INTERVAL);
})();
