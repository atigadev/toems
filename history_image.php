<?php
// Tampilkan gambar history (khusus admin): history_image.php?id=1[&thumb=1]
require __DIR__ . '/inc/admin-auth.php';

admin_session_start();
if (!admin_logged_in()) {
    http_response_code(403);
    exit('Forbidden');
}

$row = history_get(isset($_GET['id']) ? $_GET['id'] : 0);
$file = $row ? HISTORY_DIR . '/' . (empty($_GET['thumb']) ? $row['image'] : $row['thumb']) : '';
if (!$row || !is_file($file)) {
    http_response_code(404);
    exit('Not found');
}

header('Content-Type: image/jpeg');
header('Content-Length: ' . filesize($file));
header('Cache-Control: private, max-age=86400');
if (!empty($_GET['download'])) {
    header('Content-Disposition: attachment; filename="scan-' . $row['mode'] . '-' . $row['code'] . '-' . $row['id'] . '.jpg"');
}
readfile($file);
