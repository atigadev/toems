// Registrasi service worker + tombol "Install Aplikasi"
(function () {
    if ('serviceWorker' in navigator && window.isSecureContext) {
        window.addEventListener('load', function () {
            navigator.serviceWorker.register('sw.js').catch(function (err) {
                console.warn('Service worker gagal didaftarkan:', err);
            });
        });
    }

    var standalone = window.matchMedia('(display-mode: standalone)').matches || navigator.standalone === true;
    if (standalone) return;

    var deferred = null;
    var bar = document.createElement('div');
    bar.className = 'pwa-install';
    bar.style.display = 'none';
    bar.innerHTML =
        '<img src="assets/icons/icon-192.png" alt="">' +
        '<div class="pwa-install-text"><strong>Install EMS Scan</strong><span></span></div>' +
        '<button type="button" class="btn btn-danger btn-sm pwa-install-btn">Install</button>' +
        '<button type="button" class="pwa-install-close" aria-label="Tutup">&times;</button>';

    function dismissed() {
        try { return localStorage.getItem('pwa-dismissed') === '1'; } catch (e) { return false; }
    }

    function show(text, withButton) {
        if (dismissed()) return;
        bar.querySelector('span').innerHTML = text;
        bar.querySelector('.pwa-install-btn').style.display = withButton ? '' : 'none';
        bar.style.display = 'flex';
    }

    bar.querySelector('.pwa-install-close').addEventListener('click', function () {
        bar.style.display = 'none';
        try { localStorage.setItem('pwa-dismissed', '1'); } catch (e) { /* abaikan */ }
    });

    bar.querySelector('.pwa-install-btn').addEventListener('click', function () {
        if (!deferred) return;
        deferred.prompt();
        deferred.userChoice.then(function () {
            deferred = null;
            bar.style.display = 'none';
        });
    });

    window.addEventListener('beforeinstallprompt', function (e) {
        e.preventDefault();
        deferred = e;
        show('Pasang di layar utama agar scan lebih cepat', true);
    });

    window.addEventListener('appinstalled', function () {
        bar.style.display = 'none';
    });

    document.addEventListener('DOMContentLoaded', function () {
        document.body.appendChild(bar);
        // iOS Safari tidak punya beforeinstallprompt: tampilkan petunjuk manual
        if (/iphone|ipad|ipod/i.test(navigator.userAgent)) {
            show('Ketuk <i class="fa fa-share-square-o"></i> Bagikan &rarr; <em>Tambah ke Layar Utama</em>', false);
        }
    });
})();
