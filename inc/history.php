<?php
/**
 * History scan: gambar QR yang ter-scan + metadata (SQLite).
 * File disimpan di storage/ yang diblokir dari akses web langsung (storage/.htaccess);
 * gambar hanya bisa dilihat lewat history_image.php oleh admin.
 */

date_default_timezone_set('Asia/Makassar'); // waktu scan dicatat dalam WITA

define('STORAGE_DIR', dirname(__DIR__) . '/storage');
define('HISTORY_DIR', STORAGE_DIR . '/history');
define('HISTORY_DB', STORAGE_DIR . '/history.sqlite');
define('HISTORY_MAX_UPLOAD', 5 * 1024 * 1024); // byte
define('HISTORY_MAX_SIDE', 1280);              // px, sisi terpanjang gambar simpanan
define('HISTORY_THUMB_SIDE', 320);             // px, sisi terpanjang thumbnail

$HISTORY_MODES = ['invitation' => 'Invitation', 'idcard' => 'ID Card'];

function history_db()
{
    static $db = null;
    if ($db) {
        return $db;
    }

    ensure_storage();
    $db = new PDO('sqlite:' . HISTORY_DB);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $db->exec('CREATE TABLE IF NOT EXISTS history (
        id         INTEGER PRIMARY KEY AUTOINCREMENT,
        mode       TEXT NOT NULL,
        code       TEXT NOT NULL,
        raw        TEXT NOT NULL DEFAULT "",
        source     TEXT NOT NULL DEFAULT "camera",
        image      TEXT NOT NULL,
        thumb      TEXT NOT NULL,
        ip         TEXT NOT NULL DEFAULT "",
        user_agent TEXT NOT NULL DEFAULT "",
        created_at TEXT NOT NULL
    )');
    $db->exec('CREATE INDEX IF NOT EXISTS idx_history_created ON history (created_at)');
    $db->exec('CREATE INDEX IF NOT EXISTS idx_history_code ON history (code)');
    return $db;
}

function ensure_storage()
{
    if (!is_dir(HISTORY_DIR)) {
        @mkdir(HISTORY_DIR, 0775, true);
    }
    // Lapis kedua bila storage/.htaccess tidak ikut ter-deploy
    $htaccess = STORAGE_DIR . '/.htaccess';
    if (!is_file($htaccess)) {
        @file_put_contents($htaccess, "Require all denied\n");
    }
}

// Kecilkan gambar agar sisi terpanjang <= $maxSide, kembalikan resource GD baru
function history_resize($src, $maxSide)
{
    $w = imagesx($src);
    $h = imagesy($src);
    $scale = min(1, $maxSide / max($w, $h));
    $nw = max(1, (int) round($w * $scale));
    $nh = max(1, (int) round($h * $scale));

    $dst = imagecreatetruecolor($nw, $nh);
    imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255));
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
    return $dst;
}

/**
 * Simpan satu scan. $tmpFile = file gambar hasil upload.
 * Gambar selalu di-encode ulang ke JPEG (membuang metadata/isi selain piksel).
 *
 * @return int id history
 */
function history_save($mode, $code, $raw, $source, $tmpFile)
{
    global $HISTORY_MODES;

    if (!isset($HISTORY_MODES[$mode])) {
        throw new InvalidArgumentException('Mode tidak valid.');
    }
    if (!preg_match('/^[A-Za-z0-9._-]{3,64}$/', $code)) {
        throw new InvalidArgumentException('Kode tidak valid.');
    }
    if (!is_file($tmpFile) || filesize($tmpFile) > HISTORY_MAX_UPLOAD) {
        throw new InvalidArgumentException('Gambar tidak valid atau terlalu besar.');
    }
    $info = @getimagesize($tmpFile);
    if (!$info || !in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) {
        throw new InvalidArgumentException('Format gambar harus JPEG/PNG/WebP.');
    }
    $src = @imagecreatefromstring(file_get_contents($tmpFile));
    if (!$src) {
        throw new InvalidArgumentException('Gambar tidak bisa dibaca.');
    }

    ensure_storage();
    $subdir = date('Y/m');
    $dir    = HISTORY_DIR . '/' . $subdir;
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    $name  = date('Ymd-His') . '-' . bin2hex(random_bytes(4));
    $image = $subdir . '/' . $name . '.jpg';
    $thumb = $subdir . '/' . $name . '_t.jpg';

    $full = history_resize($src, HISTORY_MAX_SIDE);
    $small = history_resize($src, HISTORY_THUMB_SIDE);
    $okFull  = imagejpeg($full, HISTORY_DIR . '/' . $image, 82);
    $okThumb = imagejpeg($small, HISTORY_DIR . '/' . $thumb, 75);
    imagedestroy($src);
    imagedestroy($full);
    imagedestroy($small);
    if (!$okFull || !$okThumb) {
        throw new RuntimeException('Gagal menyimpan gambar.');
    }

    $stmt = history_db()->prepare('INSERT INTO history (mode, code, raw, source, image, thumb, ip, user_agent, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([
        $mode,
        $code,
        mb_substr((string) $raw, 0, 500),
        $source === 'file' ? 'file' : 'camera',
        $image,
        $thumb,
        isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '',
        mb_substr(isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '', 0, 255),
        date('Y-m-d H:i:s'),
    ]);
    return (int) history_db()->lastInsertId();
}

// Bangun klausa WHERE dari filter admin: mode, q (kode), date (Y-m-d)
function history_where($filters, &$params)
{
    $where  = [];
    $params = [];
    if (!empty($filters['mode'])) {
        $where[]  = 'mode = ?';
        $params[] = $filters['mode'];
    }
    if (!empty($filters['q'])) {
        $where[]  = 'code LIKE ?';
        $params[] = '%' . $filters['q'] . '%';
    }
    if (!empty($filters['date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $filters['date'])) {
        $where[]  = 'substr(created_at, 1, 10) = ?';
        $params[] = $filters['date'];
    }
    return $where ? 'WHERE ' . implode(' AND ', $where) : '';
}

function history_count($filters)
{
    $sql  = 'SELECT COUNT(*) FROM history ' . history_where($filters, $params);
    $stmt = history_db()->prepare($sql);
    $stmt->execute($params);
    return (int) $stmt->fetchColumn();
}

function history_list($filters, $limit, $offset)
{
    $sql  = 'SELECT * FROM history ' . history_where($filters, $params) . ' ORDER BY id DESC LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset;
    $stmt = history_db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function history_get($id)
{
    $stmt = history_db()->prepare('SELECT * FROM history WHERE id = ?');
    $stmt->execute([(int) $id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

// Hapus baris + file gambarnya. @return int jumlah yang terhapus
function history_delete($ids)
{
    $deleted = 0;
    foreach ($ids as $id) {
        $row = history_get($id);
        if (!$row) {
            continue;
        }
        foreach ([$row['image'], $row['thumb']] as $file) {
            $path = HISTORY_DIR . '/' . $file;
            if (is_file($path)) {
                @unlink($path);
            }
        }
        $stmt = history_db()->prepare('DELETE FROM history WHERE id = ?');
        $stmt->execute([(int) $row['id']]);
        $deleted += $stmt->rowCount();
    }
    return $deleted;
}
