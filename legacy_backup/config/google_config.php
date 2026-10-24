<?php
/**
 * Google Apps Script Integration Config
 * Absensi HIMA TI Politeknik Hasnur
 * 
 * Setelah deploy Google Apps Script, masukkan URL Web App di sini
 * atau melalui halaman Admin → Settings
 */

// ============================================
// GOOGLE APPS SCRIPT WEB APP URL
// Deploy Apps Script sebagai Web App, lalu paste URL di sini
// ============================================
define('GOOGLE_SCRIPT_URL', 'https://script.google.com/macros/s/AKfycbyLfxTu21-oz2sxULh8nAPlOLOpBj3ZfpFajXbKoruaVuDiv2KPWiemv65lEuoSqQ7M7w/exec');

// ============================================
// GOOGLE DRIVE FOLDER ID
// Buat folder di Google Drive untuk simpan foto
// Copy folder ID dari URL: drive.google.com/drive/folders/[FOLDER_ID]
// ============================================
define('GOOGLE_DRIVE_FOLDER_ID', ''); // Isi folder ID

/**
 * Kirim data ke Google Sheets via Apps Script
 * 
 * @param array $data Data yang akan dikirim
 * @param string $action Aksi (appendAttendance, savePhoto, dll)
 * @return array Response dari Apps Script
 */
function syncToGoogleSheets($data, $action = 'appendAttendance') {
    $url = GOOGLE_SCRIPT_URL;
    
    // Coba ambil dari database jika kosong
    if (empty($url)) {
        try {
            $db = getDB();
            $stmt = $db->query("SELECT setting_value FROM settings WHERE setting_key = 'google_script_url'");
            $result = $stmt->fetch();
            if ($result && !empty($result['setting_value'])) {
                $url = $result['setting_value'];
            }
        } catch (Exception $e) {
            // Silent fail
        }
    }
    
    if (empty($url)) {
        return ['success' => false, 'message' => 'Google Script URL belum dikonfigurasi'];
    }
    
    $payload = json_encode([
        'action' => $action,
        'data'   => $data
    ]);
    
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_SSL_VERIFYPEER => false // InfinityFree compatibility
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        return ['success' => false, 'message' => 'cURL Error: ' . $error];
    }
    
    $decoded = json_decode($response, true);
    return $decoded ?: ['success' => false, 'message' => 'Invalid response from Google'];
}

/**
 * Upload foto ke Google Drive via Apps Script
 * 
 * @param string $base64Image Base64 encoded image
 * @param string $filename Nama file
 * @return array Response dengan URL file
 */
function uploadToGoogleDrive($base64Image, $filename) {
    return syncToGoogleSheets([
        'image'    => $base64Image,
        'filename' => $filename,
        'folderId' => GOOGLE_DRIVE_FOLDER_ID
    ], 'savePhoto');
}
