<?php
/**
 * Logout Handler
 * Absensi HIMA TI Politeknik Hasnur
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

// Destroy session
$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();

// Redirect to login with message
session_start();
setFlash('success', 'Anda telah berhasil logout.');
redirect(BASE_URL . '/auth/login.php');
