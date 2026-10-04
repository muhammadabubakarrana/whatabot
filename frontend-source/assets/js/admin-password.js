// Admin password reset on admin/tenants.php (#48).
//
// Enhancement only, like forms.js: the markup posts fine without it. What it
// adds is the meter, the Generate button, and the "shown once" result panel —
// none of which a no-JS admin is missing anything essential by.
//
// The meter is a *mirror* of passwordProblem() in includes/auth.php, not the
// authority: the server still rejects what it does not like. Its job here is
// only to stop the admin submitting something that will bounce.
(function () {
    'use strict';

    var ALPHABET = 'ABCDEFGHJKMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789';
    var WORDS = ['Too short', 'Weak', 'Fair', 'Good', 'Strong'];
    var COLOURS = ['bg-danger', 'bg-danger', 'bg-warning', 'bg-info', 'bg-success'];

    // Which customer the open modal is acting on — stashed from the row's
    // data-reset-* attributes, because the meter needs name/email that are
    // never form fields.
    var owner = { email: '', name: '' };

    function form() { return document.getElementById('resetPasswordForm'); }

    function pieces() {
        var out = [];
        var local = owner.email.split('@')[0].toLowerCase();
        if (local.length >= 4) out.push(local);
        owner.name.toLowerCase().split(/\s+/).forEach(function (w) {
            if (w.length >= 4) out.push(w);
        });
        return out;
    }

    // 0..4. Mirrors the server's veto list first (too short, identity-derived),
    // then scores length plus character-class variety.
    function score(pw) {
        if (pw.length < 10) return 0;
        var lower = pw.toLowerCase();
        for (var i = 0; i < pieces().length; i++) {
            if (lower.indexOf(pieces()[i]) !== -1) return 1;
        }
        var s = pw.length >= 14 ? 3 : 2;
        var classes = 0;
        if (/[a-z]/.test(pw)) classes++;
        if (/[A-Z]/.test(pw)) classes++;
        if (/[0-9]/.test(pw)) classes++;
        if (/[^a-zA-Z0-9]/.test(pw)) classes++;
        if (classes >= 3) s++;
        return Math.min(s, 4);
    }

    function paint() {
        var f = form();
        if (!f) return;
        var pw = f.querySelector('[data-role="reset-password"]').value;
        var s = score(pw);
        for (var i = 0; i < 4; i++) {
            var seg = f.querySelector('[data-role="meter-' + 'abcd'[i] + '"]');
            if (!seg) continue;
            seg.style.width = i < s ? '25%' : '0';
            seg.className = 'progress-bar' + (i < s ? ' ' + COLOURS[s] : '');
        }
        f.querySelector('[data-role="meter-text"]').textContent = WORDS[s];
        f.querySelector('[data-role="submit"]').disabled = s < 2;
    }

    function reset() {
        var f = form();
        if (!f) return;
        f.querySelector('[data-role="reset-inputs"]').classList.remove('d-none');
        f.querySelector('[data-role="reset-result"]').classList.add('d-none');
        paint();
    }

    document.addEventListener('click', function (e) {
        var trigger = e.target.closest('[data-reset-user-id]');
        if (!trigger) return;
        var f = form();
        if (!f) return;
        owner = {
            email: trigger.dataset.resetEmail || '',
            name: trigger.dataset.resetName || ''
        };
        f.querySelector('[name="user_id"]').value = trigger.dataset.resetUserId;
        f.querySelector('[data-role="reset-email"]').value = owner.email;
        var pw = f.querySelector('[data-role="reset-password"]');
        pw.value = '';
        pw.type = 'password';
        f.querySelector('[data-role="result-password"]').value = '';
        // Same cleanup forms.js's clearErrors() does between submits: errors
        // left over from a previous rejected attempt must not survive into the
        // next customer's modal. Inline because clearErrors is not exported.
        f.querySelectorAll('.is-invalid').forEach(function (el) { el.classList.remove('is-invalid'); });
        f.querySelectorAll('[data-ajax-error]').forEach(function (el) { el.remove(); });
        reset();
    });

    document.addEventListener('click', function (e) {
        var f = form();
        if (!f || !f.contains(e.target)) return;

        if (e.target.closest('[data-role="toggle-visible"]')) {
            var pw = f.querySelector('[data-role="reset-password"]');
            pw.type = pw.type === 'password' ? 'text' : 'password';
            return;
        }

        if (e.target.closest('[data-role="generate"]')) {
            var input = f.querySelector('[data-role="reset-password"]');
            var rand = new Uint32Array(14);
            crypto.getRandomValues(rand);
            var out = '';
            for (var i = 0; i < rand.length; i++) {
                out += ALPHABET[rand[i] % ALPHABET.length];
            }
            input.value = out;
            // Generated passwords are the ones most worth seeing — the admin
            // has to read them to pass them on.
            input.type = 'text';
            paint();
            return;
        }

        if (e.target.closest('[data-role="copy"]')) {
            var out2 = f.querySelector('[data-role="result-password"]');
            out2.select();
            if (navigator.clipboard) navigator.clipboard.writeText(out2.value);
        }
    });

    document.addEventListener('input', function (e) {
        var f = form();
        if (f && e.target === f.querySelector('[data-role="reset-password"]')) paint();
    });

    // forms.js fires this before hiding the modal; cancelable so the result
    // panel — the only place the new password exists — stays on screen.
    document.addEventListener('wa:ajax-success', function (e) {
        var f = form();
        if (!f || e.target !== f) return;
        e.preventDefault();
        f.querySelector('[data-role="result-password"]').value =
            f.querySelector('[data-role="reset-password"]').value;
        f.querySelector('[data-role="reset-inputs"]').classList.add('d-none');
        f.querySelector('[data-role="reset-result"]').classList.remove('d-none');
    });
})();
