<?php
// Halaman hasil scan QR: index.php?id=ID1XR5V779
require __DIR__ . '/scraper.php';

function e($s)
{
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}

function val($s)
{
    return $s === '' ? '-' : e($s);
}

$id = isset($_GET['id']) ? trim($_GET['id']) : '';
if ($id === '') {
    header('Location: scan.php');
    exit;
}

$result = get_invitation($id);
$d      = $result['data'];
$file   = $result['file'];

$sudahMasuk = $result['found'] && stripos($d['stsdatang'], 'sudah') !== false;
$terunduh   = $result['found'] && stripos($d['stsdown'], 'terunduh') !== false;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title>EMS GIIAS MKS 2026 - <?= e($id) ?></title>
    <link rel="shortcut icon" href="assets/favicon.ico" type="image/x-icon">
    <!-- Bootstrap 3 + AdminLTE 2 (tema yang sama dengan sevenems.com) -->
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

        <?php if (!$result['ok']): ?>
            <div class="callout callout-danger">
                <h4><i class="fa fa-exclamation-triangle"></i> Terjadi Kesalahan</h4>
                <p><?= e($result['error']) ?></p>
            </div>
            <a href="index.php?id=<?= e($id) ?>" class="btn btn-default btn-block btn-lg"><i class="fa fa-refresh"></i> Coba Lagi</a>

        <?php elseif (!$result['found']): ?>
            <div class="callout callout-warning">
                <h4><i class="fa fa-search"></i> Invitation Tidak Ditemukan</h4>
                <p>ID <strong><?= e($id) ?></strong> tidak terdaftar di sistem EMS GIIAS MKS 2026.</p>
            </div>
            <a href="scan.php?mode=invitation" class="btn btn-danger btn-block btn-lg"><i class="fa fa-camera"></i> Scan Lagi</a>

        <?php else: ?>
            <!-- Status utama -->
            <div class="scan-hero <?= $sudahMasuk ? 'bg-green' : 'bg-yellow' ?>">
                <i class="fa <?= $sudahMasuk ? 'fa-check-circle' : 'fa-clock-o' ?> scan-hero-icon"></i>
                <div class="scan-hero-status"><?= val($d['stsdatang']) ?></div>
                <div class="scan-hero-sub">
                    <?php if ($sudahMasuk): ?>
                        <?= val($d['datadatang']) ?> &middot; <?= val($d['timedatang']) ?>
                    <?php else: ?>
                        Invitation belum digunakan
                    <?php endif; ?>
                </div>
            </div>

            <!-- Identitas invitation -->
            <div class="box box-danger scan-id-box">
                <div class="box-body">
                    <div class="scan-id-row">
                        <div>
                            <div class="scan-label">ID Invitation</div>
                            <div class="scan-id"><?= e($id) ?></div>
                        </div>
                        <span class="label bg-red scan-peruntukan"><?= val($d['peruntukan']) ?></span>
                    </div>
                    <?php if ($file['jenis'] !== ''): ?>
                        <div class="scan-chips">
                            <span class="scan-chip"><i class="fa fa-id-badge"></i> <?= e($file['jenis']) ?></span>
                            <span class="scan-chip"><i class="fa fa-ticket"></i> No. <?= e($file['nomor']) ?> dari <?= e($file['total']) ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Detail Utama -->
            <div class="box box-solid">
                <div class="box-header with-border bg-red">
                    <i class="fa fa-file"></i>
                    <h3 class="box-title">Detail Utama</h3>
                </div>
                <div class="box-body no-padding">
                    <ul class="scan-list">
                        <li>
                            <i class="fa fa-file-pdf-o"></i>
                            <div><span class="scan-label">Nama File</span><span class="scan-value scan-break"><?= val($d['namefile']) ?></span></div>
                        </li>
                        <li>
                            <i class="fa fa-building"></i>
                            <div><span class="scan-label">Peruntukan</span><span class="scan-value"><?= val($d['peruntukan']) ?></span></div>
                        </li>
                        <li>
                            <i class="fa fa-download"></i>
                            <div>
                                <span class="scan-label">Status Unduh</span>
                                <span class="scan-value"><span class="label <?= $terunduh ? 'label-success' : 'label-default' ?>"><?= val($d['stsdown']) ?></span></span>
                            </div>
                        </li>
                        <li>
                            <i class="fa fa-calendar"></i>
                            <div><span class="scan-label">Tanggal Unduh</span><span class="scan-value"><?= val($d['datedown']) ?></span></div>
                        </li>
                        <li>
                            <i class="fa fa-clock-o"></i>
                            <div><span class="scan-label">Waktu Unduh</span><span class="scan-value"><?= val($d['timedown']) ?></span></div>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Detail Penggunaan -->
            <div class="box box-solid">
                <div class="box-header with-border bg-red">
                    <i class="fa fa-sign-in"></i>
                    <h3 class="box-title">Detail Penggunaan</h3>
                </div>
                <div class="box-body no-padding">
                    <ul class="scan-list">
                        <li>
                            <i class="fa fa-check-square-o"></i>
                            <div>
                                <span class="scan-label">Status Datang</span>
                                <span class="scan-value"><span class="label <?= $sudahMasuk ? 'label-success' : 'label-warning' ?>"><?= val($d['stsdatang']) ?></span></span>
                            </div>
                        </li>
                        <li>
                            <i class="fa fa-map-marker"></i>
                            <div><span class="scan-label">Pintu Masuk/Penukaran</span><span class="scan-value"><?= val($d['datadatang']) ?></span></div>
                        </li>
                        <li>
                            <i class="fa fa-calendar-check-o"></i>
                            <div><span class="scan-label">Tanggal Datang</span><span class="scan-value"><?= val($d['datedatang']) ?></span></div>
                        </li>
                        <li>
                            <i class="fa fa-clock-o"></i>
                            <div><span class="scan-label">Waktu Datang</span><span class="scan-value"><?= val($d['timedatang']) ?></span></div>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="scan-actions">
                <a href="index.php?id=<?= e($id) ?>" class="btn btn-default btn-lg"><i class="fa fa-refresh"></i> Refresh</a>
                <a href="scan.php?mode=invitation" class="btn btn-danger btn-lg"><i class="fa fa-camera"></i> Scan Lagi</a>
            </div>
            <p class="scan-updated text-muted">Data diperbarui <?= date('d/m/Y H:i:s', $result['fetched_at']) ?></p>
        <?php endif; ?>

        </section>
    </div>

    <footer class="main-footer text-center scan-footer">
        <strong>Seven Event</strong> &ndash; PT. Amara Tujuh Perjuangan
    </footer>
</div>
</body>
</html>
