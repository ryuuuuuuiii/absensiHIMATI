<?php

/**
 * Save Photo Documentation
 * API for uploading meeting documentation photos
 */

require_once __DIR__.'/../../config/config.php';
require_once __DIR__.'/../../config/google_config.php';
require_once __DIR__.'/../../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
}

$meetingId = intval($_POST['meeting_id'] ?? 0);
$csrfToken = $_POST['csrf_token'] ?? '';

if (! $meetingId || ! validateCSRF($csrfToken)) {
    jsonResponse(['success' => false, 'message' => 'Invalid request']);
}

if (! isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
    jsonResponse(['success' => false, 'message' => 'No photo uploaded']);
}

$db = getDB();

// Verify meeting exists
$stmt = $db->prepare('SELECT id, foto_dokumentasi FROM meetings WHERE id = ?');
$stmt->execute([$meetingId]);
$meeting = $stmt->fetch();

if (! $meeting) {
    jsonResponse(['success' => false, 'message' => 'Meeting not found']);
}

// Upload photo
$uploadDir = ROOT_PATH.'uploads'.DIRECTORY_SEPARATOR.'dokumentasi'.DIRECTORY_SEPARATOR.$meetingId.DIRECTORY_SEPARATOR;
$result = uploadFile($_FILES['photo'], $uploadDir);

if (! $result['success']) {
    jsonResponse(['success' => false, 'message' => $result['message']]);
}

// Update meeting photos list
$photos = $meeting['foto_dokumentasi'] ? json_decode($meeting['foto_dokumentasi'], true) : [];
$photos[] = $result['filename'];

$stmt = $db->prepare('UPDATE meetings SET foto_dokumentasi = ? WHERE id = ?');
$stmt->execute([json_encode($photos), $meetingId]);

// Try to upload to Google Drive
try {
    $imageData = base64_encode(file_get_contents($result['filepath']));
    uploadToGoogleDrive($imageData, 'dok_'.$meetingId.'_'.$result['filename']);
} catch (Exception $e) {
    // Silent fail - photo saved locally
}

jsonResponse([
    'success' => true,
    'message' => 'Foto berhasil disimpan',
    'filename' => $result['filename'],
]);
