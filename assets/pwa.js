// Registrasi service worker, notifikasi & tombol update, dan tombol "Install Aplikasi"
(function () {
    var meta = document.querySelector('meta[name="app-version"]');
    var CURRENT_VERSION = meta ? meta.content : '';
    var CHECK_INTERVAL = 30 * 60 * 1000; // cek update otomatis tiap 30 menit

    function el(className, html) {
        var div = document.createElement('div');
        div.className = className;
        div.style.display = 'none';
        div.innerHTML = html;
        return div;
    }

    function onReady(fn) {
        if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', fn);
        else fn();
    }

    var toastTimer;
    function toast(text) {
        var t = document.querySelector('.pwa-toast');
        if (!t) {
            t = el('pwa-toast', '');
            document.body.appendChild(t);
        }
        t.innerHTML = text;
        t.style.display = 'block';
        clearTimeout(toastTimer);
        toastTimer = setTimeout(function () { t.style.display = 'none'; }, 3000);
    }

    // ------------------------------------------------------------ Update

    var updateBar = el('pwa-bar pwa-update',
        '<i class="fa fa-cloud-download pwa-bar-icon"></i>' +
        '<div class="pwa-bar-text"><strong>Versi baru tersedia</strong><span></span></div>' +
        '<button type="button" class="btn btn-success btn-sm pwa-update-btn"><i class="fa fa-refresh"></i> Update</button>');

    function askVersion(worker) {
        return new Promise(function (resolve) {
            var channel = new MessageChannel();
            channel.port1.onmessage = function (e) { resolve(e.data && e.data.version); };
            worker.postMessage({ type: 'GET_VERSION' }, [channel.port2]);
            setTimeout(function () { resolve(null); }, 1000);
        });
    }

    function showUpdate(worker) {
        askVersion(worker).then(function (version) {
            updateBar.querySelector('span').textContent = version
                ? 'v' + CURRENT_VERSION + ' → v' + version
                : 'Tekan Update untuk memuat versi terbaru';
            updateBar.style.display = 'flex';
        });
        updateBar.querySelector('.pwa-update-btn').onclick = function () {
            this.disabled = true;
            this.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Memperbarui…';
            worker.postMessage({ type: 'SKIP_WAITING' });
        };
    }

    // Versi baru yang selesai terpasang & menunggu aktif
    function waitForInstalled(worker) {
        return new Promise(function (resolve) {
            if (worker.state === 'installed') return resolve(worker);
            worker.addEventListener('statechange', function () {
                if (worker.state === 'installed') resolve(worker);
                if (worker.state === 'redundant') resolve(null);
            });
        });
    }

    function watchRegistration(reg) {
        if (reg.waiting && navigator.serviceWorker.controller) showUpdate(reg.waiting);

        reg.addEventListener('updatefound', function () {
            waitForInstalled(reg.installing).then(function (worker) {
                // Tanpa controller = instalasi pertama, bukan update
                if (worker && navigator.serviceWorker.controller) showUpdate(worker);
            });
        });

        setInterval(function () { reg.update().catch(function () {}); }, CHECK_INTERVAL);
        document.addEventListener('visibilitychange', function () {
            if (document.visibilityState === 'visible') reg.update().catch(function () {});
        });

        var btn = document.getElementById('app-update-check');
        if (btn) {
            btn.hidden = false;
            btn.addEventListener('click', function () {
                btn.disabled = true;
                btn.innerHTML = '<i class="fa fa-refresh fa-spin"></i> Mengecek…';
                reg.update().then(function () {
                    var pending = reg.installing || reg.waiting;
                    if (!pending) {
                        toast('<i class="fa fa-check"></i> Sudah versi terbaru (v' + CURRENT_VERSION + ')');
                        return;
                    }
                    return waitForInstalled(pending).then(function (worker) {
                        if (worker) showUpdate(worker);
                    });
                }).catch(function () {
                    toast('<i class="fa fa-wifi"></i> Gagal cek update, periksa koneksi');
                }).then(function () {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fa fa-refresh"></i> Cek update';
                });
            });
        }
    }

    if ('serviceWorker' in navigator && window.isSecureContext) {
        // Versi baru aktif -> muat ulang sekali agar halaman memakai aset terbaru
        var reloading = false;
        navigator.serviceWorker.addEventListener('controllerchange', function () {
            if (reloading) return;
            reloading = true;
            location.reload();
        });

        window.addEventListener('load', function () {
            navigator.serviceWorker.register('sw.php', { scope: './', updateViaCache: 'none' })
                .then(watchRegistration)
                .catch(function (err) { console.warn('Service worker gagal didaftarkan:', err); });
        });
    }

    onReady(function () { document.body.appendChild(updateBar); });

    // ------------------------------------------------------------ Install

    var standalone = window.matchMedia('(display-mode: standalone)').matches || navigator.standalone === true;
    if (standalone) return;

    var deferred = null;
    var installBar = el('pwa-bar pwa-install',
        '<img src="assets/icons/icon-192.png" alt="">' +
        '<div class="pwa-bar-text"><strong>Install EMS Scan</strong><span></span></div>' +
        '<button type="button" class="btn btn-danger btn-sm pwa-install-btn">Install</button>' +
        '<button type="button" class="pwa-bar-close" aria-label="Tutup">&times;</button>');

    function dismissed() {
        try { return localStorage.getItem('pwa-dismissed') === '1'; } catch (e) { return false; }
    }

    function showInstall(text, withButton) {
        if (dismissed()) return;
        installBar.querySelector('span').innerHTML = text;
        installBar.querySelector('.pwa-install-btn').style.display = withButton ? '' : 'none';
        installBar.style.display = 'flex';
    }

    installBar.querySelector('.pwa-bar-close').addEventListener('click', function () {
        installBar.style.display = 'none';
        try { localStorage.setItem('pwa-dismissed', '1'); } catch (e) { /* abaikan */ }
    });

    installBar.querySelector('.pwa-install-btn').addEventListener('click', function () {
        if (!deferred) return;
        deferred.prompt();
        deferred.userChoice.then(function () {
            deferred = null;
            installBar.style.display = 'none';
        });
    });

    window.addEventListener('beforeinstallprompt', function (e) {
        e.preventDefault();
        deferred = e;
        showInstall('Pasang di layar utama agar scan lebih cepat', true);
    });

    window.addEventListener('appinstalled', function () {
        installBar.style.display = 'none';
    });

    onReady(function () {
        document.body.appendChild(installBar);
        // iOS Safari tidak punya beforeinstallprompt: tampilkan petunjuk manual
        if (/iphone|ipad|ipod/i.test(navigator.userAgent)) {
            showInstall('Ketuk <i class="fa fa-share-square-o"></i> Bagikan &rarr; <em>Tambah ke Layar Utama</em>', false);
        }
    });
})();
