<?php
/**
 * QR Scanner Page — Scan Attendance
 * Absensi HIMA TI Politeknik Hasnur
 */

require_once __DIR__ . '/../includes/auth_check.php';

$pageTitle = 'Scan Absensi';
$pageIcon = 'fas fa-qrcode';

$meetingId = intval($_GET['id'] ?? 0);
if (!$meetingId) {
    setFlash('danger', 'ID rapat tidak valid.');
    redirect(BASE_URL . '/dashboard/index.php');
}

$db = getDB();

// Get meeting data
$stmt = $db->prepare("SELECT m.*, u.nama_lengkap as creator_name FROM meetings m JOIN users u ON m.created_by = u.id WHERE m.id = ?");
$stmt->execute([$meetingId]);
$meeting = $stmt->fetch();

if (!$meeting) {
    setFlash('danger', 'Rapat tidak ditemukan.');
    redirect(BASE_URL . '/dashboard/index.php');
}

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

// Absent link URL
$absentUrl = BASE_URL . '/meeting/absent_form.php?token=' . $meeting['absent_link'];

// Online attend link
$onlineUrl = BASE_URL . '/meeting/online_attend.php?token=' . $meeting['unique_link'];

$extraJS = [
    'https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js',
    BASE_URL . '/assets/js/scanner.js'
];

include __DIR__ . '/../includes/header.php';
?>

<!-- Meeting Info Banner -->
<div class="card" style="margin-bottom: var(--space-6); border-left: 4px solid var(--primary);">
    <div class="d-flex align-center justify-between flex-wrap gap-4">
        <div>
            <h3 style="color: var(--primary); margin-bottom: var(--space-1);">
                <i class="fas fa-broadcast-tower"></i> 
                <?= getJenisRapatLabel($meeting['jenis_rapat']) ?>
            </h3>
            <p style="font-size: var(--font-sm); color: var(--text-muted);">
                <?= formatTanggal($meeting['tanggal']) ?> • <?= formatWaktu($meeting['waktu_mulai']) ?>
            </p>
            <?php if ($meeting['fokus_bahasan']): ?>
                <p style="font-size: var(--font-sm); color: var(--text-secondary); margin-top: var(--space-2);">
                    <i class="fas fa-bookmark"></i> <?= htmlspecialchars($meeting['fokus_bahasan']) ?>
                </p>
            <?php endif; ?>
            <?php if ($meeting['nama_event']): ?>
                <p style="font-size: var(--font-sm); color: var(--text-secondary); margin-top: var(--space-2);">
                    <i class="fas fa-calendar-star"></i> <?= htmlspecialchars($meeting['nama_event']) ?>
                </p>
            <?php endif; ?>
        </div>
        <div class="text-center">
            <div style="font-size: var(--font-3xl); font-weight: 800; color: var(--success);">
                <span id="hadirCounter"><?= $hadirCount ?></span>/<span id="totalCounter"><?= $totalPeserta ?></span>
            </div>
            <small style="color: var(--text-muted);">Kehadiran</small>
        </div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-6);">
    <!-- Left Column: Scanner -->
    <div style="min-width: 0;">
        <!-- QR Scanner -->
        <div class="card">
            <h3 class="card-title mb-4">
                <i class="fas fa-camera"></i> Scanner QR Code
            </h3>
            
            <div class="scanner-container">
                <div id="qr-reader" class="scanner-viewport"></div>
            </div>
            
            <!-- Scan Result -->
            <div id="scanResult" style="display: none;" class="mt-4"></div>
            
            <!-- Scanner Actions -->
            <div class="scan-actions mt-4">
                <button class="btn btn-secondary" onclick="capturePhoto()" id="btnPhoto">
                    <i class="fas fa-camera-retro"></i> Foto Dokumentasi
                </button>
                <button class="btn btn-success" onclick="finishAttendance()" id="btnFinish">
                    <i class="fas fa-check-circle"></i> Selesai Absensi
                </button>
            </div>
        </div>
        
        <!-- Share Links -->
        <div class="card mt-4">
            <h4 class="card-title mb-4" style="font-size: var(--font-base);">
                <i class="fas fa-share-alt"></i> Bagikan Link
            </h4>
            
            <!-- Absent Link -->
            <div style="margin-bottom: var(--space-4);">
                <label class="form-label" style="font-size: var(--font-xs);">
                    <i class="fas fa-user-slash" style="color: var(--danger);"></i> Link Tidak Hadir
                </label>
                <div class="share-link-box">
                    <input type="text" value="<?= $absentUrl ?>" readonly id="absentLink">
                    <button onclick="copyToClipboard('<?= $absentUrl ?>', this)">
                        <i class="fas fa-copy"></i> Salin
                    </button>
                </div>
            </div>
            
            <!-- Online Attend Link (if online mode) -->
            <?php if ($meeting['mode_rapat'] === 'online'): ?>
            <div>
                <label class="form-label" style="font-size: var(--font-xs);">
                    <i class="fas fa-link" style="color: var(--info);"></i> Link Absensi Online
                </label>
                <div class="share-link-box">
                    <input type="text" value="<?= $onlineUrl ?>" readonly id="onlineLink">
                    <button onclick="copyToClipboard('<?= $onlineUrl ?>', this)">
                        <i class="fas fa-copy"></i> Salin
                    </button>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Right Column: Attendance List -->
    <div style="min-width: 0;">
        <div class="card">
            <div class="scanned-list-header">
                <h3>
                    <i class="fas fa-users"></i> Daftar Kehadiran
                </h3>
                <span class="scanned-count" id="scannedCount"><?= $hadirCount ?> hadir</span>
            </div>
            
            <div id="scannedList" style="max-height: 500px; overflow-y: auto;">
                <?php foreach ($attendances as $a): ?>
                    <?php if ($a['status'] === 'hadir'): ?>
                    <div class="scanned-item" data-user-id="<?= $a['user_id'] ?>">
                        <div class="scanned-item-avatar">
                            <i class="fas fa-check"></i>
                        </div>
                        <div class="scanned-item-info">
                            <h4><?= htmlspecialchars($a['nama_lengkap']) ?></h4>
                            <p><?= htmlspecialchars($a['departemen']) ?> • <?= htmlspecialchars($a['jabatan']) ?></p>
                        </div>
                        <span class="scanned-item-time">
                            <?= $a['scanned_at'] ? date('H:i', strtotime($a['scanned_at'])) : '-' ?>
                        </span>
                    </div>
                    <?php endif; ?>
                <?php endforeach; ?>
                
                <?php if ($hadirCount === 0): ?>
                <div class="empty-state" id="emptyState" style="padding: var(--space-8) var(--space-4);">
                    <i class="fas fa-qrcode"></i>
                    <h3>Belum Ada Scan</h3>
                    <p>Arahkan kamera ke QR Code peserta untuk mulai scan absensi</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Hidden Photo Capture -->
<input type="file" id="photoInput" accept="image/*" capture="environment" style="display: none;">

<!-- Pass data to JS -->
<script>
    const MEETING_ID = <?= $meetingId ?>;
    const MEETING_DATA = <?= json_encode([
        'id' => $meeting['id'],
        'jenis_rapat' => $meeting['jenis_rapat'],
        'tanggal' => $meeting['tanggal'],
        'status' => $meeting['status']
    ]) ?>;
    const SCANNED_USER_IDS = <?= json_encode($scannedIds) ?>;
    const BASE_URL = '<?= BASE_URL ?>';
    const CSRF_TOKEN = '<?= $_SESSION['csrf_token'] ?>';
</script>

<style>
    @media (max-width: 768px) {
        div[style*="grid-template-columns: 1fr 1fr"] {
            grid-template-columns: 1fr !important;
        }
    }
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>
