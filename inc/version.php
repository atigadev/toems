<?php
/*
 * Versi aplikasi. NAIKKAN setiap kali ada perubahan CSS/JS/halaman:
 * - URL aset jadi baru (scan.css?v=...) sehingga browser tidak memakai cache lama
 * - service worker (sw.php) ikut berubah sehingga PWA menawarkan "Update"
 */
define('APP_VERSION', '1.1.0');

// URL aset lokal dengan penanda versi, mis. assets/scan.css?v=1.1.0
function asset($path)
{
    return $path . '?v=' . APP_VERSION;
}
