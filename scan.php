<?php
// Menu awal (tanpa mode) dan scanner QR: scan.php?mode=invitation | scan.php?mode=idcard
$MODES = [
    'invitation' => [
        'title'       => 'Scan Invitation',
        'icon'        => 'fa-ticket',
        'desc'        => 'Cek status unduh & kedatangan invitation',
        'action'      => 'index.php',
        'param'       => 'id',
        'placeholder' => 'Atau ketik ID: ID1XR5V779',
    ],
    'idcard' => [
        'title'       => 'Scan ID Card',
        'icon'        => 'fa-id-card-o',
        'desc'        => 'Lihat nama, perusahaan & last check (tanpa mencatat)',
        'action'      => 'idcard.php',
        'param'       => 'code',
        'placeholder' => 'Atau ketik kode ID card',
    ],
];

$mode = isset($_GET['mode'], $MODES[$_GET['mode']]) ? $_GET['mode'] : '';
$m    = $mode !== '' ? $MODES[$mode] : null;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title><?= $m ? $m['title'] . ' - ' : '' ?>EMS GIIAS MKS 2026</title>
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

        <?php if (!$m): ?>
            <!-- Menu pilihan jenis scan -->
            <h4 class="menu-heading">Pilih jenis scan</h4>
            <?php foreach ($MODES as $key => $item): ?>
                <a href="scan.php?mode=<?= $key ?>" class="menu-card">
                    <span class="menu-card-icon bg-red"><i class="fa <?= $item['icon'] ?>"></i></span>
                    <span class="menu-card-text">
                        <strong><?= $item['title'] ?></strong>
                        <small><?= $item['desc'] ?></small>
                    </span>
                    <i class="fa fa-chevron-right menu-card-arrow"></i>
                </a>
            <?php endforeach; ?>

        <?php else: ?>
            <div class="box box-danger">
                <div class="box-header with-border">
                    <i class="fa <?= $m['icon'] ?>"></i>
                    <h3 class="box-title"><?= $m['title'] ?></h3>
                    <div class="box-tools pull-right">
                        <a href="scan.php" class="btn btn-box-tool scan-back"><i class="fa fa-th-large"></i> Menu</a>
                    </div>
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

                    <form action="<?= $m['action'] ?>" method="get" class="scan-search">
                        <div class="input-group">
                            <input type="text" name="<?= $m['param'] ?>" class="form-control" placeholder="<?= $m['placeholder'] ?>" autocapitalize="characters" required>
                            <span class="input-group-btn">
                                <button class="btn btn-danger" type="submit"><i class="fa fa-search"></i> Cek</button>
                            </span>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>

        </section>
    </div>

    <footer class="main-footer text-center scan-footer">
        <strong>Seven Event</strong> &ndash; PT. Amara Tujuh Perjuangan
    </footer>
</div>

<?php if ($m): ?>
<script src="https://cdn.jsdelivr.net/npm/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
(function () {
    var MODE = <?= json_encode($mode) ?>;
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

    // Isi QR bisa berupa URL (…?id= / ?code= / ?barcode=) atau kodenya saja
    function extractCode(text) {
        text = (text || '').trim();
        try {
            var params = new URL(text).searchParams;
            var v = params.get('id') || params.get('code') || params.get('barcode');
            if (v) return v.trim();
        } catch (e) { /* bukan URL */ }
        var pattern = MODE === 'invitation' ? /^[A-Za-z0-9]{4,32}$/ : /^[A-Za-z0-9._-]{3,64}$/;
        return pattern.test(text) ? text : null;
    }

    function targetUrl(code) {
        return MODE === 'invitation'
            ? 'index.php?id=' + encodeURIComponent(code)
            : 'idcard.php?code=' + encodeURIComponent(code);
    }

    function onDecoded(text) {
        if (done) return;
        var code = extractCode(text);
        if (!code) {
            msg('warning', '<i class="fa fa-exclamation-triangle"></i> QR tidak dikenali: <code>' + esc(text) + '</code>');
            return;
        }
        done = true;
        if (navigator.vibrate) navigator.vibrate(150);
        msg('success', '<i class="fa fa-check"></i> QR terbaca: <strong>' + esc(code) + '</strong>, membuka data…');
        var go = function () { location.href = targetUrl(code); };
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
            var https = 'https://' + location.host + location.pathname + location.search;
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
<?php endif; ?>
</body>
</html>
