<?php
/**
 * Autentikasi admin sederhana: satu password, disimpan sebagai hash di storage/admin.json.
 * Password dibuat sendiri oleh admin saat admin.php pertama kali dibuka.
 */
require_once __DIR__ . '/history.php';

define('ADMIN_FILE', STORAGE_DIR . '/admin.json');

function admin_session_start()
{
    if (session_status() === PHP_SESSION_NONE) {
        session_name('TOEMSADMIN');
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => dirname($_SERVER['SCRIPT_NAME']) . '/',
            'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
}

function admin_is_setup()
{
    return is_file(ADMIN_FILE);
}

function admin_set_password($password)
{
    ensure_storage();
    return (bool) file_put_contents(ADMIN_FILE, json_encode(['password_hash' => password_hash($password, PASSWORD_DEFAULT)]));
}

function admin_check_password($password)
{
    $data = json_decode((string) @file_get_contents(ADMIN_FILE), true);
    return is_array($data) && isset($data['password_hash']) && password_verify($password, $data['password_hash']);
}

function admin_logged_in()
{
    return !empty($_SESSION['admin']);
}

function admin_login()
{
    session_regenerate_id(true);
    $_SESSION['admin'] = true;
}

function admin_logout()
{
    $_SESSION = [];
    session_destroy();
}

function csrf_token()
{
    return $_SESSION['csrf'];
}

function csrf_valid()
{
    return isset($_POST['csrf']) && hash_equals($_SESSION['csrf'], (string) $_POST['csrf']);
}
