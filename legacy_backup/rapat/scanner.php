<?php
/**
 * Public QR Scanner — Tanpa Login
 * Diamankan via meeting unique_link token di URL
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

$token = sanitize($_GET['token'] ?? '');
if (empty($token)) {
    die('Link tidak valid.');
}

$db = getDB();

// Get meeting by token
$stmt = $db->prepare("
    SELECT m.*, u.nama_lengkap as creator_name 
    FROM meetings m JOIN users u ON m.created_by = u.id 
    WHERE m.unique_link = ?
");
$stmt->execute([$token]);
$meeting = $stmt->fetch();

if (!$meeting) {
    die('Rapat tidak ditemukan.');
}

$meetingId = $meeting['id'];

// Get attendance data
$stmt = $db->prepare("
    SELECT a.*, u.nama_lengkap, u.departemen, u.jabatan 
    FROM attendances a 
    JOIN users u ON a.user_id = u.id 
    WHERE a.meeting_id = ?
    ORDER BY a.scanned_at DESC
");
$stmt->execute([$meetingId]);
$attendances = $stmt->fetchAll();

$totalPeserta = count($attendances);
$hadirCount = 0;
$scannedIds = [];
foreach ($attendances as $a) {
    if ($a['status'] === 'hadir') {
        $hadirCount++;
        $scannedIds[] = $a['user_id'];
    }
}

$absentUrl = BASE_URL . '/meeting/absent_form.php?token=' . $meeting['absent_link'];
$onlineUrl = BASE_URL . '/meeting/online_attend.php?token=' . $meeting['unique_link'];
$hasilUrl = BASE_URL . '/rapat/hasil.php?token=' . $meeting['unique_link'];

// Generate CSRF for API calls
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$logo1 = getSetting('logo_1', '');
$logo2 = getSetting('logo_2', '');
$logo3 = getSetting('logo_3', '');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scanner — <?= APP_NAME ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body style="background: var(--dark-bg);">
    
    <!-- Top Bar -->
    <div style="background: var(--dark-surface); padding: var(--space-3) var(--space-6); display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid var(--dark-border);">
        <div>
            <h3 style="color: var(--primary); margin: 0; font-size: var(--font-base);">
                <i class="fas fa-qrcode"></i> Scanner Absensi
            </h3>
            <small style="color: var(--text-muted);"><?= getJenisRapatLabel($meeting['jenis_rapat']) ?> — <?= formatTanggal($meeting['tanggal'], 'short') ?></small>
        </div>
        <div style="text-align: center;">
            <div style="font-size: var(--font-2xl); font-weight: 800; color: var(--success);">
                <span id="hadirCounter"><?= $hadirCount ?></span>/<span id="totalCounter"><?= $totalPeserta ?></span>
            </div>
        </div>
    </div>
    
    <div style="padding: var(--space-4); max-width: 1200px; margin: 0 auto;">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-4);">
            
            <!-- Left: Scanner -->
            <div style="min-width: 0;">
                <div class="card">
                    <h3 class="card-title mb-4"><i class="fas fa-camera"></i> Scanner QR Code</h3>
                    <div class="scanner-container">
                        <div id="qr-reader" class="scanner-viewport"></div>
                    </div>
                    <div id="scanResult" style="display: none;" class="mt-4"></div>
                    <div class="scan-actions mt-4">
                        <button class="btn btn-secondary" onclick="capturePhoto()">
                            <i class="fas fa-camera-retro"></i> Foto Dokumentasi
                        </button>
                        <button class="btn btn-success" onclick="finishAttendance()">
                            <i class="fas fa-check-circle"></i> Selesai
                        </button>
                    </div>
                </div>
                
                <!-- Share Links -->
                <div class="card mt-4">
                    <h4 style="font-size: var(--font-sm); margin-bottom: var(--space-3);">
                        <i class="fas fa-share-alt"></i> Bagikan Link
                    </h4>
                    <div style="margin-bottom: var(--space-3);">
                        <label class="form-label" style="font-size: var(--font-xs);"><i class="fas fa-user-slash" style="color: var(--danger);"></i> Link Tidak Hadir</label>
                        <div class="share-link-box">
                            <input type="text" value="<?= $absentUrl ?>" readonly>
                            <button onclick="copyToClipboard('<?= $absentUrl ?>', this)"><i class="fas fa-copy"></i></button>
                        </div>
                    </div>
                    <?php if ($meeting['mode_rapat'] === 'online'): ?>
                    <div>
                        <label class="form-label" style="font-size: var(--font-xs);"><i class="fas fa-link" style="color: var(--info);"></i> Link Absensi Online</label>
                        <div class="share-link-box">
                            <input type="text" value="<?= $onlineUrl ?>" readonly>
                            <button onclick="copyToClipboard('<?= $onlineUrl ?>', this)"><i class="fas fa-copy"></i></button>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Right: Attendance List -->
            <div style="min-width: 0;">
                <div class="card">
                    <div class="scanned-list-header">
                        <h3><i class="fas fa-users"></i> Daftar Kehadiran</h3>
                        <span class="scanned-count" id="scannedCount"><?= $hadirCount ?> hadir</span>
                    </div>
                    <div id="scannedList" style="max-height: 500px; overflow-y: auto;">
                        <?php foreach ($attendances as $a): ?>
                            <?php if ($a['status'] === 'hadir'): ?>
                            <div class="scanned-item" data-user-id="<?= $a['user_id'] ?>">
                                <div class="scanned-item-avatar"><i class="fas fa-check"></i></div>
                                <div class="scanned-item-info">
                                    <h4><?= htmlspecialchars($a['nama_lengkap']) ?></h4>
                                    <p><?= $a['departemen'] ?> • <?= $a['jabatan'] ?></p>
                                </div>
                                <span class="scanned-item-time"><?= $a['scanned_at'] ? date('H:i', strtotime($a['scanned_at'])) : '-' ?></span>
                            </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        <?php if ($hadirCount === 0): ?>
                        <div class="empty-state" id="emptyState" style="padding: var(--space-8) var(--space-4);">
                            <i class="fas fa-qrcode"></i>
                            <h3>Belum Ada Scan</h3>
                            <p>Arahkan kamera ke QR Code peserta</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <input type="file" id="photoInput" accept="image/*" capture="environment" style="display: none;">
    <div id="toastContainer" class="toast-container"></div>
    
    <script>
        const MEETING_ID = <?= $meetingId ?>;
        const MEETING_DATA = <?= json_encode(['id' => $meeting['id'], 'status' => $meeting['status']]) ?>;
        const SCANNED_USER_IDS = <?= json_encode($scannedIds) ?>;
        const BASE_URL = '<?= BASE_URL ?>';
        const CSRF_TOKEN = '<?= $_SESSION['csrf_token'] ?>';
        const HASIL_URL = '<?= $hasilUrl ?>';
    </script>
    
    <script src="<?= BASE_URL ?>/assets/js/app.js"></script>
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    <script src="<?= BASE_URL ?>/assets/js/scanner.js"></script>
    
    <script>
    // Override finishAttendance to redirect to hasil page
    const _origFinish = finishAttendance;
    finishAttendance = async function() {
        if (!confirm('Selesaikan absensi? Peserta yang belum scan = Alpha.')) return;
        try {
            const response = await fetch(BASE_URL + '/meeting/process/process_scan.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({ action: 'finish', meeting_id: MEETING_ID, csrf_token: CSRF_TOKEN })
            });
            const result = await response.json();
            if (result.success) {
                if (typeof stopScanning === 'function') stopScanning();
                if (typeof clearState === 'function') clearState();
                showToast('success', 'Selesai!', 'Absensi berhasil diselesaikan.');
                setTimeout(() => { window.location.href = HASIL_URL; }, 1500);
            } else {
                showToast('error', 'Error', result.message || 'Gagal.');
            }
        } catch (error) {
            showToast('error', 'Error', 'Gagal menghubungi server.');
        }
    };
    </script>
    
    <style>
    @media (max-width: 768px) {
        div[style*="grid-template-columns: 1fr 1fr"] { grid-template-columns: 1fr !important; }
    }
    </style>
</body>
</html>
