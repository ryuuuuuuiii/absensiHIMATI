<?php

/**
 * Global Configuration
 * Absensi HIMA TI Politeknik Hasnur
 */

// ============================================
// APPLICATION SETTINGS
// ============================================
define('APP_NAME', 'Absensi HIMA TI');
define('APP_FULL_NAME', 'Absensi HIMA TI Politeknik Hasnur');
define('APP_VERSION', '1.0.0');
define('APP_DEBUG', true); // Set false di production

// ============================================
// BASE URL — Ubah sesuai domain hosting
// ============================================
$protocol = (! empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');

// Cari root project dari path
$rootPath = '';
$parts = explode('/', $scriptDir);
foreach ($parts as $i => $part) {
    $rootPath .= $part.'/';
    if ($part === 'Absensi-HIMATI-new') {
        break;
    }
}
// Jika di root hosting (InfinityFree), gunakan '/'
if (strpos($scriptDir, 'Absensi-HIMATI-new') === false) {
    $rootPath = '/';
}

define('BASE_URL', $protocol.'://'.$host.rtrim($rootPath, '/'));

// ============================================
// TIMEZONE
// ============================================
date_default_timezone_set('Asia/Makassar'); // WITA

// ============================================
// SESSION CONFIGURATION
// ============================================
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_strict_mode', 1);
    ini_set('session.cookie_samesite', 'Lax');
    session_start();
}

// ============================================
// CSRF TOKEN
// ============================================
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ============================================
// FILE PATHS
// ============================================
define('ROOT_PATH', realpath(__DIR__.'/..').DIRECTORY_SEPARATOR);
define('ASSETS_PATH', ROOT_PATH.'assets'.DIRECTORY_SEPARATOR);
define('QR_PATH', ASSETS_PATH.'img'.DIRECTORY_SEPARATOR.'qrcodes'.DIRECTORY_SEPARATOR);
define('UPLOAD_PATH', ROOT_PATH.'uploads'.DIRECTORY_SEPARATOR);

// ============================================
// DEPARTEMEN LIST
// ============================================
define('DEPARTEMEN_LIST', json_encode([
    'Inti' => 'Departemen Inti',
    'PSDM' => 'Departemen PSDM',
    'Medinfo' => 'Departemen Medinfo',
    'Afkers' => 'Departemen Afkers',
    'Industif' => 'Departemen Industif',
]));

// ============================================
// JENIS RAPAT
// ============================================
define('JENIS_RAPAT', json_encode([
    'internal_dept' => 'Rapat Internal Departemen',
    'perdana' => 'Rapat Perdana',
    'kepanitiaan' => 'Rapat Kepanitiaan / Even',
    'bph' => 'Rapat BPH / Inti',
    'lintas_dept' => 'Rapat Lintas Departemen',
]));

// ============================================
// STATUS KEHADIRAN
// ============================================
define('STATUS_HADIR', 'hadir');
define('STATUS_IZIN', 'izin');
define('STATUS_SAKIT', 'sakit');
define('STATUS_ALPHA', 'alpha');

// ============================================
// UPLOAD LIMITS
// ============================================
define('MAX_PHOTO_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'webp']);

// ============================================
// ONLINE LINK EXPIRY (hours)
// ============================================
define('ONLINE_LINK_EXPIRY', 3);

// Include database
require_once __DIR__.'/database.php';
