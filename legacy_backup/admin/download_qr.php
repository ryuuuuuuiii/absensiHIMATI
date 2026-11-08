<?php

/**
 * Admin: Download & Export QR Code
 * Absensi HIMA TI Politeknik Hasnur
 */

require_once __DIR__.'/../includes/auth_check.php';

if (! isAdmin()) {
    http_response_code(403);
    exit('Akses ditolak.');
}

$db = getDB();

// ============================================================
// 1. BULK DOWNLOAD AS ZIP
// ============================================================
if (isset($_GET['action']) && $_GET['action'] === 'zip') {
    if (! class_exists('ZipArchive')) {
        exit('PHP ZipArchive extension tidak aktif.');
    }

    $stmt = $db->query('SELECT * FROM users ORDER BY departemen, nama_lengkap');
    $users = $stmt->fetchAll();

    if (empty($users)) {
        exit('Belum ada data anggota.');
    }

    $zipFilename = 'Semua_QR_Code_Anggota_HIMATI_'.date('Ymd').'.zip';
    $zipPath = sys_get_temp_dir().DIRECTORY_SEPARATOR.'himati_qr_'.uniqid().'.zip';

    $zip = new ZipArchive;
    if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        exit('Gagal membuat file ZIP.');
    }

    $count = 0;
    foreach ($users as $user) {
        $res = ensureMemberQrImage($user);
        if ($res && file_exists($res['filepath'])) {
            $safeName = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $user['nama_lengkap']);
            $safeDept = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $user['departemen']);
            $entryName = "{$safeDept}/{$safeName} - {$user['jabatan']} ({$user['qr_code']}).png";
            $zip->addFile($res['filepath'], $entryName);
            $count++;
        }
    }

    $zip->close();

    if ($count === 0 || ! file_exists($zipPath)) {
        exit('Tidak ada file QR yang berhasil di-generate.');
    }

    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="'.$zipFilename.'"');
    header('Content-Length: '.filesize($zipPath));
    header('Pragma: no-cache');
    header('Expires: 0');

    readfile($zipPath);
    @unlink($zipPath);
    exit;
}

// ============================================================
// 2. SINGLE MEMBER DOWNLOAD / VIEW
// ============================================================
$userId = intval($_GET['id'] ?? 0);
if (! $userId) {
    exit('ID Anggota tidak valid.');
}

$stmt = $db->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (! $user) {
    exit('Anggota tidak ditemukan.');
}

$res = ensureMemberQrImage($user);
if (! $res || ! file_exists($res['filepath'])) {
    exit('Gagal menghasilkan file QR Code. Coba muat ulang atau periksa koneksi internet.');
}

// Tampilkan inline untuk preview gambar di browser
if (isset($_GET['view']) && $_GET['view'] == '1') {
    header('Content-Type: image/png');
    header('Content-Length: '.filesize($res['filepath']));
    readfile($res['filepath']);
    exit;
}

// Download file PNG
$safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $user['nama_lengkap']);
$downloadName = 'QR_'.$user['departemen'].'_'.$safeName.'.png';

header('Content-Type: image/png');
header('Content-Disposition: attachment; filename="'.$downloadName.'"');
header('Content-Length: '.filesize($res['filepath']));
header('Pragma: no-cache');
header('Expires: 0');

readfile($res['filepath']);
exit;
