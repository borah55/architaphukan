/* ========================================================================
   Doge Faucet - Front-end helpers (v2)
   ======================================================================== */
(function () {
    'use strict';

    // ----- Cookie notice -------------------------------------------------
    var bar = document.getElementById('cookieBar');
    if (bar) {
        if (!localStorage.getItem('cookieAck')) bar.style.display = 'block';
        var btn = document.getElementById('cookieAccept');
        if (btn) btn.addEventListener('click', function () {
            localStorage.setItem('cookieAck', '1');
            bar.style.display = 'none';
        });
    }

    // ----- Popup ad once per session ------------------------------------
    var popup = document.getElementById('popupAd');
    if (popup && !sessionStorage.getItem('popupShown')) {
        setTimeout(function () {
            try {
                var modal = new bootstrap.Modal(popup);
                modal.show();
                sessionStorage.setItem('popupShown', '1');
            } catch (e) { /* ignore */ }
        }, 4000);
    }

    // ----- Copy-to-clipboard --------------------------------------------
    document.querySelectorAll('[data-copy]').forEach(function (el) {
        el.addEventListener('click', function (e) {
            e.preventDefault();
            var text = el.getAttribute('data-copy');
            if (!text) return;
            var orig = el.innerHTML;
            (navigator.clipboard ?
                navigator.clipboard.writeText(text)
                : new Promise(function (resolve) {
                    var ta = document.createElement('textarea');
                    ta.value = text; document.body.appendChild(ta);
                    ta.select(); document.execCommand('copy');
                    document.body.removeChild(ta); resolve();
                  })
            ).then(function () {
                el.innerHTML = '<i class="fa fa-check me-1"></i> Copied!';
                setTimeout(function () { el.innerHTML = orig; }, 1500);
            });
        });
    });

    // ----- Auto-dismiss flash toasts ------------------------------------
    document.querySelectorAll('.toast-msg').forEach(function (t) {
        setTimeout(function () {
            t.style.transition = 'opacity .3s, transform .3s';
            t.style.opacity = '0';
            t.style.transform = 'translateY(8px)';
            setTimeout(function () { t.remove(); }, 350);
        }, 5000);
    });

    // ----- PIN input behaviour (auto-advance, paste-distribute) ---------
    document.querySelectorAll('.pin-group').forEach(function (group) {
        var inputs = group.querySelectorAll('input.pin-cell');
        var hidden = group.querySelector('input[type="hidden"].pin-value');

        function syncHidden() {
            if (!hidden) return;
            var v = '';
            inputs.forEach(function (i) { v += i.value || ''; });
            hidden.value = v;
        }

        inputs.forEach(function (input, idx) {
            input.addEventListener('input', function (e) {
                input.value = input.value.replace(/\D/g, '').slice(0, 1);
                input.classList.toggle('filled', input.value.length === 1);
                if (input.value && idx < inputs.length - 1) inputs[idx + 1].focus();
                syncHidden();
            });
            input.addEventListener('keydown', function (e) {
                if (e.key === 'Backspace' && !input.value && idx > 0) {
                    inputs[idx - 1].focus();
                }
                if (e.key === 'ArrowLeft' && idx > 0) inputs[idx - 1].focus();
                if (e.key === 'ArrowRight' && idx < inputs.length - 1) inputs[idx + 1].focus();
            });
            input.addEventListener('paste', function (e) {
                e.preventDefault();
                var data = (e.clipboardData || window.clipboardData).getData('text');
                var digits = (data.match(/\d/g) || []).slice(0, inputs.length);
                inputs.forEach(function (cell, i) {
                    cell.value = digits[i] || '';
                    cell.classList.toggle('filled', !!digits[i]);
                });
                syncHidden();
                var nextIdx = Math.min(digits.length, inputs.length - 1);
                inputs[nextIdx].focus();
            });
        });

        // form submit -> ensure hidden has the PIN
        var form = group.closest('form');
        if (form) form.addEventListener('submit', syncHidden);
    });

    // ----- Admin sidebar toggle (mobile) --------------------------------
    var sb = document.querySelector('.admin-sidebar');
    var sbBtn = document.querySelector('[data-admin-sidebar-toggle]');
    var sbBackdrop = document.querySelector('.admin-backdrop');
    if (sb && sbBtn) {
        sbBtn.addEventListener('click', function () {
            sb.classList.toggle('open');
            if (sbBackdrop) sbBackdrop.classList.toggle('open');
        });
    }
    if (sbBackdrop) sbBackdrop.addEventListener('click', function () {
        sb.classList.remove('open');
        sbBackdrop.classList.remove('open');
    });
})();
