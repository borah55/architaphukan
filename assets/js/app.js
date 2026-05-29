/* Doge Faucet front-end helpers */
(function () {
    // Cookie notice
    var bar = document.getElementById('cookieBar');
    if (bar) {
        if (!localStorage.getItem('cookieAck')) {
            bar.style.display = 'block';
        }
        var btn = document.getElementById('cookieAccept');
        if (btn) {
            btn.addEventListener('click', function () {
                localStorage.setItem('cookieAck', '1');
                bar.style.display = 'none';
            });
        }
    }

    // Popup ad - show once per session
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

    // Copy-to-clipboard for any [data-copy] element
    document.querySelectorAll('[data-copy]').forEach(function (el) {
        el.addEventListener('click', function () {
            var text = el.getAttribute('data-copy');
            if (!text) return;
            navigator.clipboard.writeText(text).then(function () {
                var orig = el.innerHTML;
                el.innerHTML = '<i class="fa fa-check"></i> Copied!';
                setTimeout(function () { el.innerHTML = orig; }, 1500);
            });
        });
    });
})();
