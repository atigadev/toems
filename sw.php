<?php
// Service worker PWA EMS Scan. Versi cache mengikuti APP_VERSION (inc/version.php).
require __DIR__ . '/inc/version.php';

header('Content-Type: application/javascript; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate'); // agar cek update selalu ke server
header('Service-Worker-Allowed: ./');
?>
// Service worker PWA EMS Scan
var APP_VERSION = <?= json_encode(APP_VERSION) ?>;
var VERSION = 'ems-scan-' + APP_VERSION;
var STATIC_CACHE = VERSION + '-static';
var DATA_CACHE = VERSION + '-data';

var PRECACHE = [
    'scan.php',
    'scan.php?mode=invitation',
    'scan.php?mode=idcard',
    'offline.html',
    'assets/scan.css', // dipakai offline.html (statis, tanpa ?v=)
    <?= json_encode(asset('manifest.json')) ?>,
    <?= json_encode(asset('assets/scan.css')) ?>,
    <?= json_encode(asset('assets/pwa.js')) ?>,
    'assets/7-seven.png',
    'assets/favicon.ico',
    'assets/icons/icon-192.png',
    'assets/icons/apple-touch-icon.png'
];

// File CDN di-cache sebisanya; gangguan CDN tidak boleh menggagalkan instalasi
var PRECACHE_CDN = [
    'https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.3.7/css/bootstrap.min.css',
    'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css',
    'https://cdnjs.cloudflare.com/ajax/libs/admin-lte/2.4.18/css/AdminLTE.min.css',
    'https://cdnjs.cloudflare.com/ajax/libs/admin-lte/2.4.18/css/skins/skin-red.min.css',
    'https://cdn.jsdelivr.net/npm/html5-qrcode@2.3.8/html5-qrcode.min.js'
];

// Tanpa skipWaiting otomatis: versi baru menunggu sampai pengguna menekan "Update"
self.addEventListener('install', function (event) {
    event.waitUntil(
        caches.open(STATIC_CACHE).then(function (cache) {
            return cache.addAll(PRECACHE.map(function (url) { return new Request(url, { cache: 'reload' }); }))
                .then(function () {
                    return Promise.all(PRECACHE_CDN.map(function (url) {
                        return cache.add(url).catch(function () { /* diambil lagi saat online */ });
                    }));
                });
        })
    );
});

self.addEventListener('activate', function (event) {
    event.waitUntil(
        caches.keys().then(function (keys) {
            return Promise.all(keys.filter(function (k) { return k.indexOf(VERSION + '-') !== 0; })
                .map(function (k) { return caches.delete(k); }));
        }).then(function () { return self.clients.claim(); })
    );
});

// Perintah dari halaman (assets/pwa.js)
self.addEventListener('message', function (event) {
    var data = event.data || {};
    if (data.type === 'SKIP_WAITING') {
        self.skipWaiting();
    } else if (data.type === 'GET_VERSION' && event.ports[0]) {
        event.ports[0].postMessage({ version: APP_VERSION });
    }
});

// ?source=pwa / ?source=shortcut hanya penanda, bukan halaman berbeda
function cacheKey(request) {
    var url = new URL(request.url);
    if (!url.searchParams.has('source')) return request;
    url.searchParams.delete('source');
    return url.toString();
}

// Data invitation / ID card: utamakan jaringan (status bisa berubah), simpan salinan untuk dibuka saat offline
function networkFirst(request) {
    return fetch(request).then(function (response) {
        if (response.ok) {
            var copy = response.clone();
            caches.open(DATA_CACHE).then(function (cache) { cache.put(request, copy); });
        }
        return response;
    }).catch(function () {
        return caches.match(request).then(function (cached) {
            return cached || caches.match('offline.html');
        });
    });
}

// Halaman scan, aset statis & CDN: tampilkan dari cache, perbarui di belakang
function staleWhileRevalidate(request) {
    var key = cacheKey(request);
    return caches.open(STATIC_CACHE).then(function (cache) {
        return cache.match(key).then(function (cached) {
            var network = fetch(request).then(function (response) {
                if (response.ok || response.type === 'opaque') cache.put(key, response.clone());
                return response;
            }).catch(function () { return cached || caches.match('offline.html'); });
            return cached || network;
        });
    });
}

self.addEventListener('fetch', function (event) {
    var request = event.request;
    if (request.method !== 'GET') return;

    var url = new URL(request.url);
    var sameOrigin = url.origin === self.location.origin;
    var path = url.pathname;

    if (sameOrigin && (/\/(index|idcard|api)\.php$/.test(path) || /\/$/.test(path))) {
        event.respondWith(networkFirst(request));
    } else if (sameOrigin && /\/(qr|sw)\.php$/.test(path)) {
        return; // halaman admin & service worker sendiri tidak di-cache
    } else {
        event.respondWith(staleWhileRevalidate(request));
    }
});
