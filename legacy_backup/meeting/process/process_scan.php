<?php

/**
 * Process Scan / Finish Attendance
 * API endpoint for QR scan processing
 */

require_once __DIR__.'/../../config/config.php';
require_once __DIR__.'/../../config/google_config.php';
require_once __DIR__.'/../../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
}

// Parse JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (! $input) {
    jsonResponse(['success' => false, 'message' => 'Invalid input']);
}

// Validate CSRF
if (! validateCSRF($input['csrf_token'] ?? '')) {
    jsonResponse(['success' => false, 'message' => 'Invalid security token'], 403);
}

$db = getDB();
$action = $input['action'] ?? 'scan';

// ============================================
// ACTION: Finish Meeting
// ============================================
if ($action === 'finish') {
    $meetingId = intval($input['meeting_id'] ?? 0);

    if (! $meetingId) {
        jsonResponse(['success' => false, 'message' => 'Meeting ID invalid']);
    }

    try {
        // Update meeting status
        $stmt = $db->prepare("UPDATE meetings SET status = 'completed', waktu_selesai = CURTIME(), updated_at = NOW() WHERE id = ?");
        $stmt->execute([$meetingId]);

        // Sync to Google Sheets
        syncMeetingToSheets($db, $meetingId);

        jsonResponse(['success' => true, 'message' => 'Absensi berhasil diselesaikan']);
    } catch (Exception $e) {
        jsonResponse(['success' => false, 'message' => 'Error: '.$e->getMessage()]);
    }
}

// ============================================
// ACTION: Process QR Scan
// ============================================
$meetingId = intval($input['meeting_id'] ?? 0);
$userId = intval($input['user_id'] ?? 0);
$qrCode = $input['qr_code'] ?? '';

if (! $meetingId || ! $userId || ! $qrCode) {
    jsonResponse(['success' => false, 'message' => 'Data scan tidak lengkap']);
}

try {
    // Verify QR code belongs to user
    $stmt = $db->prepare('SELECT id, nama_lengkap, departemen, is_bph FROM users WHERE id = ? AND qr_code = ?');
    $stmt->execute([$userId, $qrCode]);
    $user = $stmt->fetch();

    if (! $user) {
        jsonResponse(['success' => false, 'message' => 'QR Code tidak valid atau tidak terdaftar']);
    }

    // Check if meeting exists and is active
    $stmt = $db->prepare("SELECT id, status, jenis_rapat FROM meetings WHERE id = ? AND status IN ('scanning', 'draft')");
    $stmt->execute([$meetingId]);
    $meeting = $stmt->fetch();

    if (! $meeting) {
        jsonResponse(['success' => false, 'message' => 'Rapat tidak aktif atau sudah selesai']);
    }

    // Check if already scanned
    $stmt = $db->prepare('SELECT id, status FROM attendances WHERE meeting_id = ? AND user_id = ?');
    $stmt->execute([$meetingId, $userId]);
    $existing = $stmt->fetch();

    $now = date('Y-m-d H:i:s');

    if ($existing) {
        if ($existing['status'] === 'hadir') {
            jsonResponse(['success' => false, 'message' => 'Sudah tercatat hadir', 'duplicate' => true]);
        }
        // Update existing record
        $stmt = $db->prepare("UPDATE attendances SET status = 'hadir', scanned_at = ?, scan_method = 'qr_scan' WHERE id = ?");
        $stmt->execute([$now, $existing['id']]);
    } else {
        // Jika rapat kepanitiaan, cek apakah dia BPH. Jika bukan BPH dan belum ada record (bukan panitia terpilih) -> tolak
        if ($meeting['jenis_rapat'] === 'kepanitiaan') {
            if (! $user['is_bph']) {
                jsonResponse(['success' => false, 'message' => 'Anda tidak terdaftar sebagai peserta di rapat kepanitiaan ini.']);
            }
        }

        // Insert new attendance (untuk BPH opsional atau default fallback)
        $stmt = $db->prepare("INSERT INTO attendances (meeting_id, user_id, status, scanned_at, scan_method) VALUES (?, ?, 'hadir', ?, 'qr_scan')");
        $stmt->execute([$meetingId, $userId, $now]);
    }

    jsonResponse([
        'success' => true,
        'message' => 'Kehadiran tercatat',
        'scanned_at' => $now,
        'user' => [
            'id' => $user['id'],
            'nama' => $user['nama_lengkap'],
            'departemen' => $user['departemen'],
        ],
    ]);

} catch (Exception $e) {
    jsonResponse(['success' => false, 'message' => 'Error: '.$e->getMessage()]);
}

// ============================================
// SYNC HELPER
// ============================================
function syncMeetingToSheets($db, $meetingId)
{
    try {
        // Get meeting with attendances
        $stmt = $db->prepare('
            SELECT m.*, 
                   a.status as att_status, a.keterangan, a.scanned_at, a.scan_method,
                   u.nama_lengkap, u.departemen, u.jabatan
            FROM meetings m
            JOIN attendances a ON a.meeting_id = m.id
            JOIN users u ON a.user_id = u.id
            WHERE m.id = ?
            ORDER BY u.departemen, u.nama_lengkap
        ');
        $stmt->execute([$meetingId]);
        $rows = $stmt->fetchAll();

        if (empty($rows)) {
            return;
        }

        $meeting = $rows[0];
        $data = [];

        foreach ($rows as $row) {
            $data[] = [
                'tanggal' => $meeting['tanggal'],
                'jenis_rapat' => $meeting['jenis_rapat'],
                'departemen_rapat' => $meeting['departemen'] ?? '',
                'nama_event' => $meeting['nama_event'] ?? '',
                'fokus_bahasan' => $meeting['fokus_bahasan'] ?? '',
                'nama_peserta' => $row['nama_lengkap'],
                'departemen_peserta' => $row['departemen'],
                'jabatan' => $row['jabatan'],
                'status' => $row['att_status'],
                'keterangan' => $row['keterangan'] ?? '',
                'waktu_checkin' => $row['scanned_at'] ?? '',
                'mode' => $meeting['mode_rapat'],
            ];
        }

        syncToGoogleSheets($data, 'appendAttendance');
    } catch (Exception $e) {
        // Silent fail for sync
        error_log('Google Sheets sync error: '.$e->getMessage());
    }
}
