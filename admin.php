<?php
// Halaman admin: history gambar scan (lihat, saring, hapus manual)
require_once __DIR__ . '/inc/version.php';
require __DIR__ . '/inc/admin-auth.php';

define('PER_PAGE', 24);

function e($s)
{
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}

function redirect_self($query = [])
{
    header('Location: admin.php' . ($query ? '?' . http_build_query($query) : ''));
    exit;
}

function flash($type = null, $text = null)
{
    if ($type !== null) {
        $_SESSION['flash'] = [$type, $text];
        return null;
    }
    $f = isset($_SESSION['flash']) ? $_SESSION['flash'] : null;
    unset($_SESSION['flash']);
    return $f;
}

admin_session_start();
header('Cache-Control: no-store');

$error = '';
$action = isset($_POST['action']) ? $_POST['action'] : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid()) {
        $error = isset($_COOKIE[session_name()])
            ? 'Sesi kedaluwarsa, silakan coba lagi.'
            : 'Cookie sesi tidak diterima server. Pastikan cookie tidak diblokir browser, lalu muat ulang halaman ini.';
    } elseif ($action === 'setup' && !admin_is_setup()) {
        $pw = isset($_POST['password']) ? (string) $_POST['password'] : '';
        $pw2 = isset($_POST['password2']) ? (string) $_POST['password2'] : '';
        if (strlen($pw) < 8) {
            $error = 'Password minimal 8 karakter.';
        } elseif ($pw !== $pw2) {
            $error = 'Konfirmasi password tidak sama.';
        } elseif (!admin_set_password($pw)) {
            $error = 'Gagal menyimpan password. Periksa izin tulis folder storage/.';
        } else {
            admin_login();
            flash('success', 'Password admin berhasil dibuat.');
            redirect_self();
        }
    } elseif ($action === 'login') {
        if (admin_check_password(isset($_POST['password']) ? (string) $_POST['password'] : '')) {
            admin_login();
            redirect_self();
        }
        sleep(1); // perlambat tebak-tebakan password
        $error = 'Password salah.';
    } elseif ($action === 'logout') {
        admin_logout();
        redirect_self();
    } elseif ($action === 'delete' && admin_logged_in()) {
        $ids = isset($_POST['ids']) && is_array($_POST['ids']) ? array_map('intval', $_POST['ids']) : [];
        $n = $ids ? history_delete($ids) : 0;
        flash($n ? 'success' : 'warning', $n ? "$n history dihapus." : 'Tidak ada history yang dipilih.');
        $back = isset($_POST['back']) ? json_decode((string) $_POST['back'], true) : [];
        redirect_self(is_array($back) ? $back : []);
    }
}

$loggedIn = admin_logged_in();
$filters = [
    'mode' => isset($_GET['mode'], $HISTORY_MODES[$_GET['mode']]) ? $_GET['mode'] : '',
    'q'    => isset($_GET['q']) ? trim((string) $_GET['q']) : '',
    'date' => isset($_GET['date']) ? (string) $_GET['date'] : '',
];
$page = max(1, isset($_GET['page']) ? (int) $_GET['page'] : 1);

if ($loggedIn) {
    $total = history_count($filters);
    $pages = max(1, (int) ceil($total / PER_PAGE));
    $page  = min($page, $pages);
    $rows  = history_list($filters, PER_PAGE, ($page - 1) * PER_PAGE);
    $flash = flash();
}

$query = array_filter($filters + ['page' => $page > 1 ? $page : '']);
function page_url($p)
{
    global $filters;
    return 'admin.php?' . http_build_query(array_filter($filters + ['page' => $p > 1 ? $p : '']));
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Admin History Scan - EMS GIIAS MKS 2026</title>
    <link rel="shortcut icon" href="assets/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.3.7/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/admin-lte/2.4.18/css/AdminLTE.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/admin-lte/2.4.18/css/skins/skin-red.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:400,600,700">
    <link rel="stylesheet" href="<?= asset('assets/scan.css') ?>">
</head>
<body class="hold-transition skin-red layout-top-nav">
<div class="wrapper">
    <header class="main-header">
        <nav class="navbar navbar-static-top">
            <div class="scan-navbar">
                <img src="assets/7-seven.png" alt="Seven Event" class="scan-navbar-logo">
                <span class="scan-navbar-title">Admin History Scan</span>
            </div>
        </nav>
    </header>

    <div class="content-wrapper">
        <section class="content admin-content">

        <?php if (!$loggedIn): ?>
            <div class="box box-danger admin-login">
                <div class="box-header with-border">
                    <i class="fa fa-lock"></i>
                    <h3 class="box-title"><?= admin_is_setup() ? 'Login Admin' : 'Buat Password Admin' ?></h3>
                </div>
                <form method="post" class="box-body">
                    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= e($error) ?></div>
                    <?php endif; ?>

                    <?php if (admin_is_setup()): ?>
                        <input type="hidden" name="action" value="login">
                        <div class="form-group">
                            <label for="password">Password</label>
                            <input type="password" id="password" name="password" class="form-control" required autofocus>
                        </div>
                        <button class="btn btn-danger btn-block btn-lg"><i class="fa fa-sign-in"></i> Masuk</button>
                    <?php else: ?>
                        <input type="hidden" name="action" value="setup">
                        <p class="text-muted">Belum ada password admin. Buat sekarang (minimal 8 karakter).</p>
                        <div class="form-group">
                            <label for="password">Password baru</label>
                            <input type="password" id="password" name="password" class="form-control" minlength="8" required autofocus>
                        </div>
                        <div class="form-group">
                            <label for="password2">Ulangi password</label>
                            <input type="password" id="password2" name="password2" class="form-control" minlength="8" required>
                        </div>
                        <button class="btn btn-danger btn-block btn-lg"><i class="fa fa-check"></i> Simpan Password</button>
                    <?php endif; ?>
                </form>
            </div>

        <?php else: ?>
            <?php if ($flash): ?>
                <div class="alert alert-<?= e($flash[0]) ?> alert-dismissible">
                    <button type="button" class="close" onclick="this.parentNode.remove()">&times;</button>
                    <?= e($flash[1]) ?>
                </div>
            <?php endif; ?>

            <!-- Filter -->
            <div class="box box-danger">
                <div class="box-header with-border">
                    <i class="fa fa-history"></i>
                    <h3 class="box-title">History Scan <small>(<?= number_format($total) ?> data)</small></h3>
                    <div class="box-tools pull-right">
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="action" value="logout">
                            <button class="btn btn-box-tool"><i class="fa fa-sign-out"></i> Logout</button>
                        </form>
                    </div>
                </div>
                <form method="get" class="box-body admin-filter">
                    <select name="mode" class="form-control">
                        <option value="">Semua jenis</option>
                        <?php foreach ($HISTORY_MODES as $key => $label): ?>
                            <option value="<?= $key ?>" <?= $filters['mode'] === $key ? 'selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="date" name="date" class="form-control" value="<?= e($filters['date']) ?>">
                    <input type="search" name="q" class="form-control" placeholder="Cari kode / ID" value="<?= e($filters['q']) ?>">
                    <button class="btn btn-danger"><i class="fa fa-search"></i> Saring</button>
                    <?php if (array_filter($filters)): ?>
                        <a href="admin.php" class="btn btn-default">Reset</a>
                    <?php endif; ?>
                </form>
            </div>

            <?php if (!$rows): ?>
                <div class="callout callout-info">
                    <p><i class="fa fa-info-circle"></i> Belum ada history scan<?= array_filter($filters) ? ' untuk filter ini' : '' ?>.</p>
                </div>
            <?php else: ?>
                <form method="post" id="delete-form">
                    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="back" value="<?= e(json_encode($query)) ?>">

                    <div class="admin-toolbar">
                        <label class="admin-check-all"><input type="checkbox" id="check-all"> Pilih semua di halaman ini</label>
                        <button type="submit" class="btn btn-danger btn-sm" id="btn-delete-selected" disabled>
                            <i class="fa fa-trash"></i> Hapus terpilih (<span id="selected-count">0</span>)
                        </button>
                    </div>

                    <div class="history-grid">
                        <?php foreach ($rows as $r): ?>
                            <div class="history-card">
                                <label class="history-check"><input type="checkbox" name="ids[]" value="<?= (int) $r['id'] ?>"></label>
                                <a href="history_image.php?id=<?= (int) $r['id'] ?>" target="_blank" class="history-thumb">
                                    <img src="history_image.php?id=<?= (int) $r['id'] ?>&amp;thumb=1" alt="Scan <?= e($r['code']) ?>" loading="lazy">
                                </a>
                                <div class="history-body">
                                    <span class="label <?= $r['mode'] === 'idcard' ? 'bg-purple' : 'bg-red' ?>"><?= e($HISTORY_MODES[$r['mode']]) ?></span>
                                    <span class="label label-default"><i class="fa <?= $r['source'] === 'file' ? 'fa-picture-o' : 'fa-camera' ?>"></i></span>
                                    <a class="history-code" target="_blank"
                                       href="<?= $r['mode'] === 'idcard' ? 'idcard.php?code=' : 'index.php?id=' ?><?= e(rawurlencode($r['code'])) ?>"><?= e($r['code']) ?></a>
                                    <div class="history-time"><i class="fa fa-clock-o"></i> <?= e(date('d/m/Y H:i:s', strtotime($r['created_at']))) ?></div>
                                    <div class="history-meta" title="<?= e($r['user_agent']) ?>"><i class="fa fa-mobile"></i> <?= e($r['ip']) ?></div>
                                    <div class="history-actions">
                                        <a href="history_image.php?id=<?= (int) $r['id'] ?>&amp;download=1" class="btn btn-default btn-xs"><i class="fa fa-download"></i></a>
                                        <button type="submit" name="ids[]" value="<?= (int) $r['id'] ?>" class="btn btn-danger btn-xs btn-delete-one"
                                                formnovalidate><i class="fa fa-trash"></i> Hapus</button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </form>

                <?php if ($pages > 1): ?>
                    <ul class="pagination admin-pagination">
                        <li class="<?= $page <= 1 ? 'disabled' : '' ?>"><a href="<?= $page <= 1 ? '#' : e(page_url($page - 1)) ?>">&laquo;</a></li>
                        <li class="active"><a href="#"><?= $page ?> / <?= $pages ?></a></li>
                        <li class="<?= $page >= $pages ? 'disabled' : '' ?>"><a href="<?= $page >= $pages ? '#' : e(page_url($page + 1)) ?>">&raquo;</a></li>
                    </ul>
                <?php endif; ?>
            <?php endif; ?>
        <?php endif; ?>

        </section>
    </div>

    <?php include __DIR__ . '/inc/footer.php'; ?>
</div>

<?php if ($loggedIn && $rows): ?>
<script>
(function () {
    var form = document.getElementById('delete-form');
    var boxes = form.querySelectorAll('.history-check input');
    var all = document.getElementById('check-all');
    var btn = document.getElementById('btn-delete-selected');
    var submitter = null;

    function refresh() {
        var n = form.querySelectorAll('.history-check input:checked').length;
        document.getElementById('selected-count').textContent = n;
        btn.disabled = n === 0;
        all.checked = n === boxes.length;
    }

    all.addEventListener('change', function () {
        boxes.forEach(function (b) { b.checked = all.checked; });
        refresh();
    });
    boxes.forEach(function (b) {
        b.addEventListener('change', function () {
            b.closest('.history-card').classList.toggle('selected', b.checked);
            refresh();
        });
    });

    form.addEventListener('click', function (e) {
        submitter = e.target.closest('button[type=submit]');
    });

    // Hapus satu: hanya kirim id tombol itu (abaikan centang lain)
    form.addEventListener('submit', function (e) {
        var single = submitter && submitter.classList.contains('btn-delete-one');
        var count = single ? 1 : form.querySelectorAll('.history-check input:checked').length;
        if (!confirm('Hapus ' + count + ' history beserta gambarnya? Tindakan ini tidak bisa dibatalkan.')) {
            e.preventDefault();
            return;
        }
        if (single) {
            boxes.forEach(function (b) { b.checked = false; });
        }
    });
})();
</script>
<?php endif; ?>
</body>
</html>
