<?php
/**
 * Process Public Meeting Creation
 * Tanpa auth — diakses via form publik
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL . '/rapat');
}

$db = getDB();

// Gather data
$createdBy = intval($_POST['created_by'] ?? 0);
$jenisRapat = sanitize($_POST['jenis_rapat'] ?? '');
$modeRapat = sanitize($_POST['mode_rapat'] ?? 'offline');
$tanggal = sanitize($_POST['tanggal'] ?? '');
$waktuMulai = sanitize($_POST['waktu_mulai'] ?? '');

// Validate
$validJenis = ['internal_dept', 'perdana', 'kepanitiaan', 'bph', 'lintas_dept'];
$validMode = ['offline', 'online'];

if (!$createdBy || !in_array($jenisRapat, $validJenis) || !in_array($modeRapat, $validMode) || empty($tanggal) || empty($waktuMulai)) {
    die('<script>alert("Data tidak valid."); history.back();</script>');
}

// Verify creator exists
$stmt = $db->prepare("SELECT id FROM users WHERE id = ?");
$stmt->execute([$createdBy]);
if (!$stmt->fetch()) {
    die('<script>alert("Pembuat rapat tidak valid."); history.back();</script>');
}

// Type-specific data
$departemen = null;
$namaEvent = null;
$divisiRapat = null;
$tipeDivisi = null;
$fokusBahasan = null;
$departemenCollab = null;

switch ($jenisRapat) {
    case 'internal_dept':
        $departemen = sanitize($_POST['departemen'] ?? '');
        $fokusBahasan = sanitize($_POST['fokus_bahasan_internal'] ?? '');
        break;
    case 'perdana':
        $namaEvent = sanitize($_POST['nama_event_perdana'] ?? '');
        break;
    case 'kepanitiaan':
        $namaEvent = sanitize($_POST['nama_event'] ?? '');
        $tipeDivisi = sanitize($_POST['tipe_divisi'] ?? '');
        $divisiRapat = sanitize($_POST['divisi_rapat'] ?? '');
        $fokusBahasan = sanitize($_POST['fokus_bahasan_kepanitiaan'] ?? '');
        break;
    case 'bph':
        $fokusBahasan = sanitize($_POST['fokus_bahasan_bph'] ?? '');
        break;
    case 'lintas_dept':
        $collabArr = $_POST['departemen_collab'] ?? [];
        $departemenCollab = json_encode(array_map('sanitize', $collabArr));
        $fokusBahasan = sanitize($_POST['fokus_bahasan_lintas'] ?? '');
        break;
}

// Generate unique links
$uniqueLink = generateShortLink();
$absentLink = generateShortLink();

try {
    $stmt = $db->prepare("
        INSERT INTO meetings (
            created_by, jenis_rapat, mode_rapat, departemen, nama_event,
            divisi_rapat, tipe_divisi, fokus_bahasan, departemen_collab,
            tanggal, waktu_mulai, status, unique_link, absent_link
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'scanning', ?, ?)
    ");
    
    $stmt->execute([
        $createdBy, $jenisRapat, $modeRapat, $departemen, $namaEvent,
        $divisiRapat, $tipeDivisi, $fokusBahasan, $departemenCollab,
        $tanggal, $waktuMulai, $uniqueLink, $absentLink
    ]);
    
    $meetingId = $db->lastInsertId();
    
    // Auto-create attendance records
    $participantQuery = '';
    $participantParams = [];
    
    switch ($jenisRapat) {
        case 'internal_dept':
            $participantQuery = "SELECT id FROM users WHERE departemen = ?";
            $participantParams = [$departemen];
            break;
        case 'perdana':
        case 'kepanitiaan':
            $participantQuery = "SELECT id FROM users";
            break;
        case 'bph':
            $participantQuery = "SELECT id FROM users WHERE is_bph = 1";
            break;
        case 'lintas_dept':
            $depts = json_decode($departemenCollab, true);
            $placeholders = implode(',', array_fill(0, count($depts), '?'));
            $participantQuery = "SELECT id FROM users WHERE departemen IN ($placeholders)";
            $participantParams = $depts;
            break;
    }
    
    if ($participantQuery) {
        $pStmt = $db->prepare($participantQuery);
        $pStmt->execute($participantParams);
        $participants = $pStmt->fetchAll();
        
        $insertStmt = $db->prepare("INSERT INTO attendances (meeting_id, user_id, status) VALUES (?, ?, 'alpha')");
        foreach ($participants as $p) {
            $insertStmt->execute([$meetingId, $p['id']]);
        }
    }
    
    // Redirect to result page with token
    redirect(BASE_URL . '/rapat/hasil.php?token=' . $uniqueLink);
    
} catch (PDOException $e) {
    if (APP_DEBUG) {
        die('Database error: ' . $e->getMessage());
    } else {
        die('<script>alert("Terjadi kesalahan."); history.back();</script>');
    }
}
