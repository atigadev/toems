<?php
// Generator QR: setiap QR mengarah ke index.php?id=XXXX
require __DIR__ . '/scraper.php';

// Smartphone tidak bisa membuka "localhost", jadi pakai IP LAN komputer ini sebagai default
$host = $_SERVER['HTTP_HOST'];
if (preg_match('/^(localhost|127\.0\.0\.1|\[::1\])(:\d+)?$/', $host, $m)) {
    $host = gethostbyname(gethostname()) . (isset($m[2]) ? $m[2] : '');
}
// HTTPS agar halaman scan.php (kamera) juga bisa dipakai setelah membuka hasil scan
$defaultBase = 'https://' . $host . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\') . '/index.php?id=';

$base = isset($_POST['base']) && trim($_POST['base']) !== '' ? trim($_POST['base']) : $defaultBase;
$raw  = isset($_POST['ids']) ? $_POST['ids'] : 'ID1XR5V779';

$ids = [];
foreach (preg_split('/[\s,;]+/', $raw, -1, PREG_SPLIT_NO_EMPTY) as $id) {
    if (is_valid_invitation_id($id)) {
        $ids[$id] = $base . rawurlencode($id);
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Generator QR Invitation</title>
    <link rel="shortcut icon" href="assets/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.3.7/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/admin-lte/2.4.18/css/AdminLTE.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/admin-lte/2.4.18/css/skins/skin-red.min.css">
    <link rel="stylesheet" href="assets/scan.css">
</head>
<body class="hold-transition skin-red layout-top-nav">
<div class="wrapper">
    <header class="main-header">
        <nav class="navbar navbar-static-top">
            <div class="scan-navbar">
                <img src="assets/7-seven.png" alt="Seven Event" class="scan-navbar-logo">
                <span class="scan-navbar-title">Generator QR Invitation</span>
            </div>
        </nav>
    </header>

    <div class="content-wrapper">
        <section class="content" style="max-width:960px;margin:0 auto;">
            <div class="box box-danger no-print">
                <div class="box-body">
                    <form method="post">
                        <div class="form-group">
                            <label>URL Halaman Scan</label>
                            <input type="text" name="base" class="form-control" value="<?= htmlspecialchars($base) ?>">
                            <p class="help-block">ID ditambahkan di akhir URL. Gunakan IP LAN / domain yang bisa diakses dari smartphone.</p>
                        </div>
                        <div class="form-group">
                            <label>ID Invitation (pisahkan dengan baris baru / koma)</label>
                            <textarea name="ids" rows="4" class="form-control"><?= htmlspecialchars($raw) ?></textarea>
                        </div>
                        <button type="submit" class="btn btn-danger"><i class="fa fa-qrcode"></i> Buat QR</button>
                        <button type="button" class="btn btn-default" onclick="window.print()"><i class="fa fa-print"></i> Cetak</button>
                    </form>
                </div>
            </div>

            <div class="row">
                <?php foreach ($ids as $id => $url): ?>
                    <div class="col-xs-6 col-sm-4 col-md-3">
                        <div class="box box-solid qr-card">
                            <div class="box-body">
                                <div class="qr-code" data-url="<?= htmlspecialchars($url) ?>"></div>
                                <h4 style="margin:8px 0 0;font-weight:700;"><?= htmlspecialchars($id) ?></h4>
                                <div class="qr-url"><a href="<?= htmlspecialchars($url) ?>" target="_blank"><?= htmlspecialchars($url) ?></a></div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    </div>

    <footer class="main-footer text-center scan-footer">
        <strong>Seven Event</strong> &ndash; PT. Amara Tujuh Perjuangan
    </footer>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
    document.querySelectorAll('.qr-code').forEach(function (el) {
        new QRCode(el, { text: el.getAttribute('data-url'), width: 160, height: 160, correctLevel: QRCode.CorrectLevel.M });
    });
</script>
</body>
</html>
