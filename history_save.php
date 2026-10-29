<?php
// Simpan gambar hasil scan ke history. POST multipart: mode, code, raw, source, image (file)
require __DIR__ . '/inc/history.php';

header('Content-Type: application/json; charset=utf-8');

function respond($status, $data)
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, ['ok' => false, 'error' => 'Gunakan POST.']);
}
if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    respond(400, ['ok' => false, 'error' => 'Gambar tidak terkirim.']);
}

try {
    $id = history_save(
        isset($_POST['mode']) ? (string) $_POST['mode'] : '',
        isset($_POST['code']) ? trim((string) $_POST['code']) : '',
        isset($_POST['raw']) ? (string) $_POST['raw'] : '',
        isset($_POST['source']) ? (string) $_POST['source'] : 'camera',
        $_FILES['image']['tmp_name']
    );
    respond(201, ['ok' => true, 'id' => $id]);
} catch (InvalidArgumentException $e) {
    respond(400, ['ok' => false, 'error' => $e->getMessage()]);
} catch (Exception $e) {
    respond(500, ['ok' => false, 'error' => 'Gagal menyimpan history.']);
}
