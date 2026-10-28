<?php
/**
 * Scraper halaman "Informasi Status Invitation" EMS GIIAS MKS 2026.
 * Sumber: https://sevenems.com/ems_giias_mks/admin/check-status-invitation.php?id=XXXX
 */

date_default_timezone_set('Asia/Makassar');

define('SOURCE_URL', 'https://sevenems.com/ems_giias_mks/admin/check-status-invitation.php?id=');
define('CACHE_DIR', __DIR__ . '/cache');
define('CACHE_TTL', 30); // detik, agar scan berulang tidak membebani server sumber

// name input di halaman sumber => label tampilan
$INVITATION_FIELDS = [
    'namefile'   => 'Nama File',
    'peruntukan' => 'Peruntukan',
    'stsdown'    => 'Status Unduh',
    'datedown'   => 'Tanggal Unduh',
    'timedown'   => 'Waktu Unduh',
    'stsdatang'  => 'Status Datang',
    'datadatang' => 'Pintu Masuk/Penukaran',
    'datedatang' => 'Tanggal Datang',
    'timedatang' => 'Waktu Datang',
];

function is_valid_invitation_id($id)
{
    return is_string($id) && preg_match('/^[A-Za-z0-9]{4,32}$/', $id);
}

function fetch_source_html($id)
{
    $ch = curl_init(SOURCE_URL . rawurlencode($id));
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_USERAGENT      => 'Mozilla/5.0 (TOEMS Invitation Checker)',
    ]);
    $html = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);

    if ($html === false || $code !== 200) {
        throw new RuntimeException($err ?: "Server sumber membalas HTTP $code");
    }
    return $html;
}

function parse_invitation_html($html)
{
    global $INVITATION_FIELDS;

    $dom = new DOMDocument();
    libxml_use_internal_errors(true);
    $dom->loadHTML('<?xml encoding="utf-8"?>' . $html);
    libxml_clear_errors();

    $xpath = new DOMXPath($dom);
    $data  = [];
    foreach ($INVITATION_FIELDS as $name => $label) {
        $node = $xpath->query("//input[@name='$name']")->item(0);
        $data[$name] = $node instanceof DOMElement ? trim($node->getAttribute('value')) : '';
    }
    return $data;
}

/**
 * Info turunan dari nama file, mis. "EXH PASS GIIAS MKS 2026 - KIA 1_4.pdf"
 * => jenis "EXH PASS", nomor 1 dari 4.
 */
function parse_invitation_filename($filename)
{
    $info = ['jenis' => '', 'nomor' => '', 'total' => ''];
    if (preg_match('/^(.*?)\s+GIIAS.*?-\s*.+?\s+(\d+)_(\d+)\.pdf$/i', $filename, $m)) {
        $info['jenis'] = trim($m[1]);
        $info['nomor'] = $m[2];
        $info['total'] = $m[3];
    }
    return $info;
}

/**
 * @return array ['ok' => bool, 'found' => bool, 'id' => string, 'data' => array, 'file' => array, 'fetched_at' => int, 'error' => string]
 */
function get_invitation($id)
{
    $result = ['ok' => false, 'found' => false, 'id' => $id, 'data' => [], 'file' => [], 'fetched_at' => time(), 'error' => ''];

    if (!is_valid_invitation_id($id)) {
        $result['error'] = 'ID invitation tidak valid.';
        return $result;
    }

    $cacheFile = CACHE_DIR . '/' . $id . '.json';
    if (is_file($cacheFile) && time() - filemtime($cacheFile) < CACHE_TTL) {
        $cached = json_decode(file_get_contents($cacheFile), true);
        if (is_array($cached)) {
            return $cached;
        }
    }

    try {
        $data = parse_invitation_html(fetch_source_html($id));
    } catch (Exception $e) {
        $result['error'] = 'Gagal mengambil data: ' . $e->getMessage();
        return $result;
    }

    $result['ok']    = true;
    $result['found'] = $data['namefile'] !== '';
    $result['data']  = $data;
    $result['file']  = parse_invitation_filename($data['namefile']);

    if (!is_dir(CACHE_DIR)) {
        @mkdir(CACHE_DIR, 0775, true);
    }
    @file_put_contents($cacheFile, json_encode($result));

    return $result;
}
