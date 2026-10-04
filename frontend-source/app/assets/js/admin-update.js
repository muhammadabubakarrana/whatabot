// Copy-to-clipboard for the update command blocks (admin overview banner and
// admin/system.php#version). No inline handlers — CSP forbids them — so the
// button is wired here by its data-copy-target attribute.
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-copy-target]').forEach((btn) => {
        btn.addEventListener('click', async () => {
            const target = document.querySelector(btn.dataset.copyTarget);
            if (!target) return;
            try {
                await navigator.clipboard.writeText(target.textContent.trim());
                const original = btn.textContent;
                btn.textContent = 'Copied';
                setTimeout(() => { btn.textContent = original; }, 1500);
            } catch (e) {
                // Clipboard API needs a secure context or a user gesture; when
                // it is denied, select the text so Ctrl-C is one keystroke.
                const range = document.createRange();
                range.selectNodeContents(target);
                const sel = window.getSelection();
                sel.removeAllRanges();
                sel.addRange(range);
            }
        });
    });
});
