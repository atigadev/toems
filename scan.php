<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title>Scan QR - EMS GIIAS MKS 2026</title>
    <link rel="shortcut icon" href="assets/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.3.7/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/admin-lte/2.4.18/css/AdminLTE.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/admin-lte/2.4.18/css/skins/skin-red.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:400,600,700">
    <link rel="stylesheet" href="assets/scan.css">
<?php include __DIR__ . '/inc/pwa-head.php'; ?>
</head>
<body class="hold-transition skin-red layout-top-nav">
<div class="wrapper">
    <header class="main-header">
        <nav class="navbar navbar-static-top">
            <div class="scan-navbar">
                <img src="assets/7-seven.png" alt="Seven Event" class="scan-navbar-logo">
                <span class="scan-navbar-title">EMS GIIAS MKS 2026</span>
            </div>
        </nav>
    </header>

    <div class="content-wrapper">
        <section class="content scan-content">
            <div class="box box-danger">
                <div class="box-header with-border">
                    <i class="fa fa-camera"></i>
                    <h3 class="box-title">Scan QR Invitation</h3>
                </div>
                <div class="box-body">
                    <!-- Pilihan kamera yang terpasang di perangkat -->
                    <div class="form-group cam-select-group">
                        <label for="cam-select"><i class="fa fa-video-camera"></i> Kamera</label>
                        <select id="cam-select" class="form-control" disabled>
                            <option value="">Tekan "Mulai Scan" untuk memuat daftar kamera</option>
                        </select>
                    </div>

                    <div class="cam-frame">
                        <div id="reader"></div>
                        <div id="cam-placeholder" class="cam-placeholder">
                            <i class="fa fa-qrcode"></i>
                            <p>Tekan <strong>Mulai Scan</strong> lalu arahkan kamera ke QR code</p>
                        </div>
                    </div>

                    <div id="cam-msg" class="cam-msg" style="display:none;"></div>

                    <div class="scan-actions" style="margin-top:12px;">
                        <button id="btn-start" class="btn btn-danger btn-lg"><i class="fa fa-camera"></i> Mulai Scan</button>
                        <button id="btn-stop" class="btn btn-default btn-lg" style="display:none;"><i class="fa fa-stop"></i> Stop</button>
                    </div>

                    <!-- Cadangan: ambil foto QR (tetap jalan walau tanpa HTTPS) -->
                    <label class="btn btn-default btn-block btn-lg cam-file">
                        <i class="fa fa-picture-o"></i> Ambil Foto / Pilih Gambar QR
                        <input type="file" id="qr-file" accept="image/*" capture="environment" hidden>
                    </label>

                    <form action="index.php" method="get" class="scan-search">
                        <div class="input-group">
                            <input type="text" name="id" class="form-control" placeholder="Atau ketik ID: ID1XR5V779" autocapitalize="characters" required>
                            <span class="input-group-btn">
                                <button class="btn btn-danger" type="submit"><i class="fa fa-search"></i> Cek</button>
                            </span>
                        </div>
                    </form>
                </div>
            </div>
        </section>
    </div>

    <footer class="main-footer text-center scan-footer">
        <strong>Seven Event</strong> &ndash; PT. Amara Tujuh Perjuangan
    </footer>
</div>

<script src="https://cdn.jsdelivr.net/npm/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
(function () {
    var CAM_KEY = 'ems-scan-camera';
    var scanner = new Html5Qrcode('reader', { formatsToSupport: [Html5QrcodeSupportedFormats.QR_CODE], verbose: false });
    var running = false, done = false;

    var $ = function (id) { return document.getElementById(id); };
    var btnStart = $('btn-start'), btnStop = $('btn-stop'), camSelect = $('cam-select');

    function esc(s) { return String(s).replace(/[<>&"]/g, ''); }

    function saveCamera(id) {
        try { localStorage.setItem(CAM_KEY, id); } catch (e) { /* abaikan */ }
    }
    function savedCamera() {
        try { return localStorage.getItem(CAM_KEY); } catch (e) { return null; }
    }

    function msg(type, html) {
        var el = $('cam-msg');
        el.className = 'cam-msg callout callout-' + type;
        el.innerHTML = html;
        el.style.display = html ? 'block' : 'none';
    }

    // Isi QR bisa berupa URL halaman ini, URL sevenems.com (…?id=XXX), atau ID saja
    function extractId(text) {
        text = (text || '').trim();
        try {
            var id = new URL(text).searchParams.get('id');
            if (id) return id.trim();
        } catch (e) { /* bukan URL */ }
        return /^[A-Za-z0-9]{4,32}$/.test(text) ? text : null;
    }

    function onDecoded(text) {
        if (done) return;
        var id = extractId(text);
        if (!id) {
            msg('warning', '<i class="fa fa-exclamation-triangle"></i> QR tidak dikenali: <code>' + esc(text) + '</code>');
            return;
        }
        done = true;
        if (navigator.vibrate) navigator.vibrate(150);
        msg('success', '<i class="fa fa-check"></i> QR terbaca: <strong>' + esc(id) + '</strong>, membuka data…');
        var go = function () { location.href = 'index.php?id=' + encodeURIComponent(id); };
        running ? scanner.stop().then(go, go) : go();
    }

    function setRunning(state) {
        running = state;
        btnStart.style.display = state ? 'none' : '';
        btnStop.style.display = state ? '' : 'none';
        $('cam-placeholder').style.display = state ? 'none' : '';
    }

    // Isi dropdown dengan semua kamera; pilihan terakhir diingat, default kamera belakang
    function loadCameras() {
        return Html5Qrcode.getCameras().then(function (list) {
            var saved = savedCamera(), selected = '';
            camSelect.innerHTML = '';
            list.forEach(function (cam, i) {
                var opt = document.createElement('option');
                opt.value = cam.id;
                opt.textContent = cam.label || ('Kamera ' + (i + 1));
                camSelect.appendChild(opt);
                if (cam.id === saved) selected = cam.id;
                else if (!selected && /back|rear|environment|belakang/i.test(cam.label)) selected = cam.id;
            });
            if (!list.length) {
                camSelect.innerHTML = '<option value="">Tidak ada kamera terdeteksi</option>';
                throw new Error('Tidak ada kamera terdeteksi di perangkat ini.');
            }
            camSelect.value = selected || list[list.length - 1].id;
            camSelect.disabled = list.length < 2;
        });
    }

    function cameraError(err) {
        setRunning(false);
        if (!window.isSecureContext) {
            var https = 'https://' + location.host + location.pathname;
            msg('danger', '<i class="fa fa-lock"></i> Kamera hanya bisa dipakai lewat <strong>HTTPS</strong>. ' +
                'Buka <a href="' + https + '">' + https + '</a> atau gunakan tombol <em>Ambil Foto</em> di bawah.');
        } else {
            msg('danger', '<i class="fa fa-ban"></i> Tidak bisa membuka kamera. Pastikan izin kamera diberikan dan kamera tidak dipakai aplikasi lain.' +
                '<br><small>' + esc(err) + '</small>');
        }
    }

    function startCamera() {
        msg('', '');
        var cam = camSelect.value ? { deviceId: { exact: camSelect.value } } : { facingMode: 'environment' };
        var config = {
            fps: 10,
            qrbox: function (w, h) { var s = Math.floor(Math.min(w, h) * 0.7); return { width: s, height: s }; }
        };
        return scanner.start(cam, config, onDecoded, function () { /* belum ada QR di frame */ })
            .then(function () { setRunning(true); })
            .catch(cameraError);
    }

    function stopCamera() {
        return running ? scanner.stop().then(function () { setRunning(false); }) : Promise.resolve();
    }

    btnStart.addEventListener('click', function () {
        btnStart.disabled = true;
        loadCameras().then(startCamera, cameraError).then(function () { btnStart.disabled = false; });
    });

    btnStop.addEventListener('click', stopCamera);

    camSelect.addEventListener('change', function () {
        saveCamera(camSelect.value);
        if (running) stopCamera().then(startCamera);
    });

    // Kamera dicolok/dicabut (mis. webcam USB): muat ulang daftar
    if (navigator.mediaDevices && navigator.mediaDevices.addEventListener) {
        navigator.mediaDevices.addEventListener('devicechange', function () {
            if (camSelect.options.length && camSelect.value) loadCameras().catch(function () {});
        });
    }

    $('qr-file').addEventListener('change', function (e) {
        var file = e.target.files[0];
        if (!file) return;
        stopCamera().then(function () {
            return scanner.scanFile(file, false);
        }).then(onDecoded).catch(function () {
            msg('warning', '<i class="fa fa-exclamation-triangle"></i> QR tidak ditemukan di gambar. Coba foto lebih dekat dan jelas.');
        });
        e.target.value = '';
    });

    // Langsung nyalakan kamera bila izin sudah pernah diberikan
    if (window.isSecureContext && navigator.permissions && navigator.permissions.query) {
        navigator.permissions.query({ name: 'camera' }).then(function (p) {
            if (p.state === 'granted') btnStart.click();
        }).catch(function () {});
    }
})();
</script>
</body>
</html>
