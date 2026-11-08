<?php

/**
 * Helper Functions
 * Absensi HIMA TI Politeknik Hasnur
 */

/**
 * Redirect ke URL lain
 */
function redirect($url)
{
    header('Location: '.$url);
    exit;
}

/**
 * Cek apakah admin sudah login
 */
function isLoggedIn()
{
    return isset($_SESSION['admin_id']) && ! empty($_SESSION['admin_id']);
}

/**
 * Cek apakah user adalah admin (always true if logged in)
 */
function isAdmin()
{
    return isLoggedIn();
}

/**
 * Cek apakah user adalah BPH (not used in admin-only mode)
 */
function isBPH()
{
    return isLoggedIn();
}

/**
 * Get current admin data
 */
function getCurrentUser()
{
    if (! isLoggedIn()) {
        return null;
    }

    return [
        'id' => $_SESSION['admin_id'],
        'nama_lengkap' => $_SESSION['admin_nama'] ?? 'Admin',
        'departemen' => 'Admin',
        'jabatan' => 'Administrator',
        'email' => $_SESSION['admin_email'] ?? '',
        'is_bph' => 1,
        'is_admin' => 1,
    ];
}

/**
 * Get user initials for avatar
 */
function getInitials($name)
{
    $words = explode(' ', trim($name));
    $initials = '';
    foreach (array_slice($words, 0, 2) as $word) {
        $initials .= mb_strtoupper(mb_substr($word, 0, 1));
    }

    return $initials;
}

/**
 * Generate unique token
 */
function generateToken($length = 32)
{
    return bin2hex(random_bytes($length));
}

/**
 * Generate short unique link
 */
function generateShortLink()
{
    return substr(md5(uniqid(mt_rand(), true)), 0, 12);
}

/**
 * Validate CSRF token
 */
function validateCSRF($token)
{
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Sanitize input
 */
function sanitize($input)
{
    if (is_array($input)) {
        return array_map('sanitize', $input);
    }

    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Format tanggal Indonesia
 */
function formatTanggal($date, $format = 'long')
{
    $bulan = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
    ];

    $hari = [
        'Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa',
        'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat',
        'Saturday' => 'Sabtu',
    ];

    $timestamp = strtotime($date);

    if ($format === 'long') {
        $hariNama = $hari[date('l', $timestamp)];
        $tgl = date('j', $timestamp);
        $bln = $bulan[(int) date('n', $timestamp)];
        $thn = date('Y', $timestamp);

        return "$hariNama, $tgl $bln $thn";
    } elseif ($format === 'short') {
        $tgl = date('j', $timestamp);
        $bln = $bulan[(int) date('n', $timestamp)];
        $thn = date('Y', $timestamp);

        return "$tgl $bln $thn";
    } else {
        return date($format, $timestamp);
    }
}

/**
 * Format waktu
 */
function formatWaktu($time)
{
    return date('H:i', strtotime($time)).' WITA';
}

/**
 * Get jenis rapat label
 */
function getJenisRapatLabel($jenis)
{
    $labels = json_decode(JENIS_RAPAT, true);

    return $labels[$jenis] ?? $jenis;
}

/**
 * Get departemen label
 */
function getDepartemenLabel($dept)
{
    $labels = json_decode(DEPARTEMEN_LIST, true);

    return $labels[$dept] ?? $dept;
}

/**
 * Get status badge HTML
 */
function getStatusBadge($status)
{
    $badges = [
        'hadir' => '<span class="badge badge-hadir"><i class="fas fa-check-circle"></i> Hadir</span>',
        'izin' => '<span class="badge badge-izin"><i class="fas fa-info-circle"></i> Izin</span>',
        'sakit' => '<span class="badge badge-sakit"><i class="fas fa-medkit"></i> Sakit</span>',
        'alpha' => '<span class="badge badge-alpha"><i class="fas fa-times-circle"></i> Alpha</span>',
    ];

    return $badges[$status] ?? '<span class="badge badge-dept">'.$status.'</span>';
}

/**
 * Get meeting status badge
 */
function getMeetingStatusBadge($status)
{
    $badges = [
        'draft' => '<span class="badge badge-warning"><i class="fas fa-edit"></i> Draft</span>',
        'scanning' => '<span class="badge badge-primary"><i class="fas fa-qrcode"></i> Scanning</span>',
        'completed' => '<span class="badge badge-success"><i class="fas fa-check"></i> Selesai</span>',
        'cancelled' => '<span class="badge badge-danger"><i class="fas fa-ban"></i> Dibatalkan</span>',
    ];

    return $badges[$status] ?? $status;
}

/**
 * Set flash message
 */
function setFlash($type, $message)
{
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message,
    ];
}

/**
 * Get and clear flash message
 */
function getFlash()
{
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);

        return $flash;
    }

    return null;
}

/**
 * Render flash message HTML
 */
function renderFlash()
{
    $flash = getFlash();
    if ($flash) {
        $icon = match ($flash['type']) {
            'success' => 'fas fa-check-circle',
            'danger' => 'fas fa-exclamation-circle',
            'warning' => 'fas fa-exclamation-triangle',
            'info' => 'fas fa-info-circle',
            default => 'fas fa-info-circle'
        };
        echo '<div class="alert alert-'.$flash['type'].'">';
        echo '<i class="'.$icon.'"></i>';
        echo '<span>'.$flash['message'].'</span>';
        echo '</div>';
    }
}

/**
 * Get settings value from database
 */
function getSetting($key, $default = '')
{
    try {
        $db = getDB();
        $stmt = $db->prepare('SELECT setting_value FROM settings WHERE setting_key = ?');
        $stmt->execute([$key]);
        $result = $stmt->fetch();

        return $result ? ($result['setting_value'] ?: $default) : $default;
    } catch (Exception $e) {
        return $default;
    }
}

/**
 * Update setting value
 */
function updateSetting($key, $value)
{
    try {
        $db = getDB();
        $stmt = $db->prepare('INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = NOW()');
        $stmt->execute([$key, $value, $value]);

        return true;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Count records
 */
function countRecords($table, $where = '', $params = [])
{
    $db = getDB();
    $sql = "SELECT COUNT(*) as total FROM `$table`";
    if ($where) {
        $sql .= " WHERE $where";
    }
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $result = $stmt->fetch();

    return $result['total'];
}

/**
 * Get user's attendance percentage
 */
function getAttendancePercentage($userId, $month = null, $year = null)
{
    $db = getDB();

    $where = 'a.user_id = ?';
    $params = [$userId];

    if ($month && $year) {
        $where .= ' AND MONTH(m.tanggal) = ? AND YEAR(m.tanggal) = ?';
        $params[] = $month;
        $params[] = $year;
    }

    $stmt = $db->prepare("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN a.status = 'hadir' THEN 1 ELSE 0 END) as hadir
        FROM attendances a
        JOIN meetings m ON a.meeting_id = m.id
        WHERE $where AND m.status = 'completed'
    ");
    $stmt->execute($params);
    $result = $stmt->fetch();

    if ($result['total'] == 0) {
        return 0;
    }

    return round(($result['hadir'] / $result['total']) * 100);
}

/**
 * JSON response helper
 */
function jsonResponse($data, $statusCode = 200)
{
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/**
 * File upload helper
 */
function uploadFile($file, $destination, $allowedTypes = ['jpg', 'jpeg', 'png', 'webp'], $maxSize = null)
{
    if ($maxSize === null) {
        $maxSize = MAX_PHOTO_SIZE;
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Upload error: '.$file['error']];
    }

    if ($file['size'] > $maxSize) {
        return ['success' => false, 'message' => 'File terlalu besar. Maksimal '.($maxSize / 1024 / 1024).'MB'];
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (! in_array($ext, $allowedTypes)) {
        return ['success' => false, 'message' => 'Tipe file tidak diizinkan. Gunakan: '.implode(', ', $allowedTypes)];
    }

    $filename = uniqid('upload_').'.'.$ext;
    $filepath = rtrim($destination, '/\\').DIRECTORY_SEPARATOR.$filename;

    // Create directory if not exists
    if (! is_dir(dirname($filepath))) {
        mkdir(dirname($filepath), 0755, true);
    }

    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return ['success' => true, 'filename' => $filename, 'filepath' => $filepath];
    }

    return ['success' => false, 'message' => 'Gagal menyimpan file'];
}

/**
 * Format data string untuk isi QR Code anggota (persis format scanner absensi)
 */
function getMemberQrData($user)
{
    return json_encode([
        'id' => intval($user['id']),
        'qr_code' => $user['qr_code'],
        'nama' => $user['nama_lengkap'],
        'departemen' => $user['departemen'],
        'jabatan' => $user['jabatan'],
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * Pastikan file gambar QR code ada di server, jika belum ada otomatis di-generate
 */
function ensureMemberQrImage($user)
{
    $qrDir = QR_PATH;
    if (! is_dir($qrDir)) {
        mkdir($qrDir, 0755, true);
    }

    $cleanCode = preg_replace('/[^a-zA-Z0-9_-]/', '', $user['qr_code']);
    $filename = 'qr_'.$user['id'].'_'.$cleanCode.'.png';
    $filepath = $qrDir.$filename;

    if (file_exists($filepath) && filesize($filepath) > 100) {
        return ['filename' => $filename, 'filepath' => $filepath];
    }

    $qrData = getMemberQrData($user);
    $apiUrls = [
        'https://api.qrserver.com/v1/create-qr-code/?size=500x500&margin=15&data='.urlencode($qrData),
        'https://quickchart.io/qr?size=500&margin=2&text='.urlencode($qrData),
    ];

    $imageData = null;
    foreach ($apiUrls as $url) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 8,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_FOLLOWLOCATION => true,
        ]);
        $imageData = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200 && $imageData && strlen($imageData) > 100) {
            break;
        }
        $imageData = null;
    }

    if ($imageData) {
        file_put_contents($filepath, $imageData);
        try {
            $db = getDB();
            $stmt = $db->prepare('UPDATE users SET qr_image = ? WHERE id = ?');
            $stmt->execute([$filename, $user['id']]);
        } catch (Exception $e) {
        }

        return ['filename' => $filename, 'filepath' => $filepath];
    }

    return null;
}
