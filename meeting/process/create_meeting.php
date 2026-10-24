<?php
/**
 * Process Create Meeting
 * Absensi HIMA TI Politeknik Hasnur
 */

require_once __DIR__ . '/../../includes/auth_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL . '/meeting/create.php');
}

// Validate CSRF
if (!validateCSRF($_POST['csrf_token'] ?? '')) {
    setFlash('danger', 'Token keamanan tidak valid.');
    redirect(BASE_URL . '/meeting/create.php');
}

$db = getDB();
$currentUser = getCurrentUser();

// Gather common data
$jenisRapat = sanitize($_POST['jenis_rapat'] ?? '');
$modeRapat = sanitize($_POST['mode_rapat'] ?? 'offline');
$tanggal = sanitize($_POST['tanggal'] ?? '');
$waktuMulai = sanitize($_POST['waktu_mulai'] ?? '');

// Validate
$validJenis = ['internal_dept', 'perdana', 'kepanitiaan', 'bph', 'lintas_dept'];
$validMode = ['offline', 'online'];

if (!in_array($jenisRapat, $validJenis) || !in_array($modeRapat, $validMode) || empty($tanggal) || empty($waktuMulai)) {
    setFlash('danger', 'Data tidak valid. Silakan coba lagi.');
    redirect(BASE_URL . '/meeting/create.php');
}

// Gather type-specific data
$eventId = null;
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
        if (empty($departemen) || empty($fokusBahasan)) {
            setFlash('danger', 'Departemen dan fokus pembahasan harus diisi.');
            redirect(BASE_URL . '/meeting/create.php');
        }
        break;
        
    case 'perdana':
        $namaEvent = sanitize($_POST['nama_event_perdana'] ?? '');
        if (empty($namaEvent)) {
            setFlash('danger', 'Nama/agenda rapat harus diisi.');
            redirect(BASE_URL . '/meeting/create.php');
        }
        break;
        
    case 'kepanitiaan':
        $eventId = intval($_POST['event_id'] ?? 0);
        $tipeDivisi = sanitize($_POST['tipe_divisi'] ?? '');
        $divisiId = intval($_POST['divisi_id'] ?? 0);
        $fokusBahasan = sanitize($_POST['fokus_bahasan_kepanitiaan'] ?? '');
        
        if (!$eventId || empty($fokusBahasan)) {
            setFlash('danger', 'Event dan agenda harus diisi.');
            redirect(BASE_URL . '/meeting/create.php');
        }
        
        // Fetch event name
        $eStmt = $db->prepare("SELECT nama_event FROM events WHERE id = ?");
        $eStmt->execute([$eventId]);
        $namaEvent = $eStmt->fetchColumn();
        
        if ($tipeDivisi === 'perdivisi' && $divisiId) {
            $dStmt = $db->prepare("SELECT nama_divisi FROM event_divisions WHERE id = ?");
            $dStmt->execute([$divisiId]);
            $divisiRapat = $dStmt->fetchColumn();
        }
        break;
        
    case 'bph':
        $fokusBahasan = sanitize($_POST['fokus_bahasan_bph'] ?? '');
        if (empty($fokusBahasan)) {
            setFlash('danger', 'Fokus bahasan harus diisi.');
            redirect(BASE_URL . '/meeting/create.php');
        }
        break;
        
    case 'lintas_dept':
        $collabArr = $_POST['departemen_collab'] ?? [];
        $departemenCollab = json_encode(array_map('sanitize', $collabArr));
        $fokusBahasan = sanitize($_POST['fokus_bahasan_lintas'] ?? '');
        if (count($collabArr) < 2 || empty($fokusBahasan)) {
            setFlash('danger', 'Pilih minimal 2 departemen dan isi agenda.');
            redirect(BASE_URL . '/meeting/create.php');
        }
        break;
}

// Generate unique links
$uniqueLink = generateShortLink();
$absentLink = generateShortLink();

try {
    $stmt = $db->prepare("
        INSERT INTO meetings (
            created_by, jenis_rapat, mode_rapat, event_id, departemen, nama_event,
            divisi_rapat, tipe_divisi, fokus_bahasan, departemen_collab,
            tanggal, waktu_mulai, status, unique_link, absent_link
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'scanning', ?, ?)
    ");
    
    $stmt->execute([
        $currentUser['id'],
        $jenisRapat,
        $modeRapat,
        $eventId,
        $departemen,
        $namaEvent,
        $divisiRapat,
        $tipeDivisi,
        $fokusBahasan,
        $departemenCollab,
        $tanggal,
        $waktuMulai,
        $uniqueLink,
        $absentLink
    ]);
    
    $meetingId = $db->lastInsertId();
    
    // Auto-create attendance records based on meeting type
    $participantQuery = '';
    $participantParams = [];
    
    switch ($jenisRapat) {
        case 'internal_dept':
            $participantQuery = "SELECT id FROM users WHERE departemen = ?";
            $participantParams = [$departemen];
            break;
        case 'perdana':
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
        case 'kepanitiaan':
            if ($tipeDivisi === 'perdivisi' && isset($divisiId) && $divisiId > 0) {
                $participantQuery = "SELECT user_id as id FROM event_members WHERE event_id = ? AND division_id = ?";
                $participantParams = [$eventId, $divisiId];
            } else {
                $participantQuery = "SELECT DISTINCT user_id as id FROM event_members WHERE event_id = ?";
                $participantParams = [$eventId];
            }
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
    
    setFlash('success', 'Rapat berhasil dibuat!');
    
    // Redirect based on mode
    if ($modeRapat === 'offline') {
        redirect(BASE_URL . '/meeting/scan.php?id=' . $meetingId);
    } else {
        redirect(BASE_URL . '/meeting/detail.php?id=' . $meetingId);
    }
    
} catch (PDOException $e) {
    if (APP_DEBUG) {
        setFlash('danger', 'Database error: ' . $e->getMessage());
    } else {
        setFlash('danger', 'Terjadi kesalahan saat menyimpan rapat.');
    }
    redirect(BASE_URL . '/meeting/create.php');
}
