<?php
// Halaman hasil scan ID card (mode baca): idcard.php?code=XXXX
require_once __DIR__ . '/inc/version.php';
require __DIR__ . '/scraper.php';

function e($s)
{
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}

function val($s)
{
    return $s === '' ? '-' : e($s);
}

$code = isset($_GET['code']) ? trim($_GET['code']) : '';
if ($code === '') {
    header('Location: scan.php?mode=idcard');
    exit;
}

$result = get_idcard($code);
$d      = $result['data'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title>ID Card <?= e($code) ?> - EMS GIIAS MKS 2026</title>
    <link rel="shortcut icon" href="assets/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.3.7/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/admin-lte/2.4.18/css/AdminLTE.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/admin-lte/2.4.18/css/skins/skin-red.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:400,600,700">
    <link rel="stylesheet" href="<?= asset('assets/scan.css') ?>">
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

        <?php if (!$result['enabled']): ?>
            <div class="callout callout-info">
                <h4><i class="fa fa-info-circle"></i> Cek ID Card Belum Tersedia</h4>
                <p>Kode <strong><?= e($code) ?></strong> berhasil dibaca, tetapi sumber data ID card mode baca-saja belum tersedia dari sistem EMS.</p>
            </div>

        <?php elseif (!$result['ok']): ?>
            <div class="callout callout-danger">
                <h4><i class="fa fa-exclamation-triangle"></i> Terjadi Kesalahan</h4>
                <p><?= e($result['error']) ?></p>
            </div>
            <a href="idcard.php?code=<?= e($code) ?>" class="btn btn-default btn-block btn-lg"><i class="fa fa-refresh"></i> Coba Lagi</a>

        <?php elseif (!$result['found']): ?>
            <div class="callout callout-warning">
                <h4><i class="fa fa-search"></i> ID Card Tidak Ditemukan</h4>
                <p>Kode <strong><?= e($code) ?></strong> tidak terdaftar di sistem EMS GIIAS MKS 2026.</p>
            </div>

        <?php else: ?>
            <div class="scan-hero bg-green">
                <i class="fa fa-id-card-o scan-hero-icon"></i>
                <div class="scan-hero-status">ID Card Valid</div>
                <div class="scan-hero-sub"><?= val($d['name']) ?></div>
            </div>

            <div class="box box-solid">
                <div class="box-header with-border bg-red">
                    <i class="fa fa-id-card"></i>
                    <h3 class="box-title">Detail ID Card</h3>
                </div>
                <div class="box-body no-padding">
                    <ul class="scan-list">
                        <li>
                            <i class="fa fa-barcode"></i>
                            <div><span class="scan-label">Kode</span><span class="scan-value scan-break"><?= e($code) ?></span></div>
                        </li>
                        <li>
                            <i class="fa fa-user"></i>
                            <div><span class="scan-label">Nama</span><span class="scan-value"><?= val($d['name']) ?></span></div>
                        </li>
                        <li>
                            <i class="fa fa-building"></i>
                            <div><span class="scan-label">Perusahaan</span><span class="scan-value"><?= val($d['company']) ?></span></div>
                        </li>
                        <li>
                            <i class="fa fa-history"></i>
                            <div><span class="scan-label">Last Check</span><span class="scan-value"><?= val($d['lastcheck']) ?></span></div>
                        </li>
                    </ul>
                </div>
            </div>
            <p class="scan-updated text-muted">Data diperbarui <?= date('d/m/Y H:i:s', $result['fetched_at']) ?></p>
        <?php endif; ?>

            <div class="scan-actions">
                <a href="scan.php" class="btn btn-default btn-lg"><i class="fa fa-th-large"></i> Menu</a>
                <a href="scan.php?mode=idcard" class="btn btn-danger btn-lg"><i class="fa fa-camera"></i> Scan Lagi</a>
            </div>

        </section>
    </div>

<?php include __DIR__ . '/inc/footer.php'; ?>
</div>
</body>
</html>
