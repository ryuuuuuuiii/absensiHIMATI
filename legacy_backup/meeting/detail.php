<?php
/**
 * Meeting Detail Page
 * Shows meeting info and attendance results
 */

require_once __DIR__ . '/../includes/auth_check.php';

$pageTitle = 'Detail Rapat';
$pageIcon = 'fas fa-clipboard-list';

$meetingId = intval($_GET['id'] ?? 0);
if (!$meetingId) {
    setFlash('danger', 'ID rapat tidak valid.');
    redirect(BASE_URL . '/dashboard/index.php');
}

$db = getDB();

// Get meeting
$stmt = $db->prepare("SELECT m.*, u.nama_lengkap as creator_name FROM meetings m JOIN users u ON m.created_by = u.id WHERE m.id = ?");
$stmt->execute([$meetingId]);
$meeting = $stmt->fetch();

if (!$meeting) {
    setFlash('danger', 'Rapat tidak ditemukan.');
    redirect(BASE_URL . '/dashboard/index.php');
}

// Get attendances
$stmt = $db->prepare("
    SELECT a.*, u.nama_lengkap, u.departemen, u.jabatan, u.is_bph
    FROM attendances a 
    JOIN users u ON a.user_id = u.id 
    WHERE a.meeting_id = ?
    ORDER BY a.status ASC, u.nama_lengkap ASC
");
$stmt->execute([$meetingId]);
$attendances = $stmt->fetchAll();

// Stats
$stats = ['hadir' => 0, 'izin' => 0, 'sakit' => 0, 'alpha' => 0];
foreach ($attendances as $a) {
    $stats[$a['status']]++;
}
$totalPeserta = count($attendances);

// URLs
$onlineUrl = BASE_URL . '/meeting/online_attend.php?token=' . $meeting['unique_link'];
$absentUrl = BASE_URL . '/meeting/absent_form.php?token=' . $meeting['absent_link'];

include __DIR__ . '/../includes/header.php';
?>

<!-- Meeting Info -->
<div class="card mb-6" style="border-left: 4px solid var(--primary);">
    <div class="d-flex align-center justify-between flex-wrap gap-4 mb-4">
        <div>
            <h2 style="color: var(--primary); font-size: var(--font-xl);">
                <?= getJenisRapatLabel($meeting['jenis_rapat']) ?>
            </h2>
            <p style="font-size: var(--font-sm); color: var(--text-muted);">
                Dibuat oleh: <?= htmlspecialchars($meeting['creator_name']) ?>
            </p>
        </div>
        <?= getMeetingStatusBadge($meeting['status']) ?>
    </div>
    
    <div class="meeting-info-grid">
        <div class="meeting-info-item">
            <label><i class="fas fa-calendar"></i> Tanggal</label>
            <p><?= formatTanggal($meeting['tanggal']) ?></p>
        </div>
        <div class="meeting-info-item">
            <label><i class="fas fa-clock"></i> Waktu</label>
            <p><?= formatWaktu($meeting['waktu_mulai']) ?><?= $meeting['waktu_selesai'] ? ' - ' . formatWaktu($meeting['waktu_selesai']) : '' ?></p>
        </div>
        <div class="meeting-info-item">
            <label><i class="fas fa-wifi"></i> Mode</label>
            <p><?= $meeting['mode_rapat'] === 'offline' ? '🏢 Offline' : '🌐 Online' ?></p>
        </div>
        <?php if ($meeting['departemen']): ?>
        <div class="meeting-info-item">
            <label><i class="fas fa-building"></i> Departemen</label>
            <p><?= getDepartemenLabel($meeting['departemen']) ?></p>
        </div>
        <?php endif; ?>
    </div>
    
    <?php if ($meeting['fokus_bahasan']): ?>
    <div class="meeting-info-item mt-4">
        <label><i class="fas fa-bookmark"></i> Fokus Bahasan / Agenda</label>
        <p><?= nl2br(htmlspecialchars($meeting['fokus_bahasan'])) ?></p>
    </div>
    <?php endif; ?>
    
    <?php if ($meeting['nama_event']): ?>
    <div class="meeting-info-item mt-4">
        <label><i class="fas fa-calendar-star"></i> Nama Event</label>
        <p><?= htmlspecialchars($meeting['nama_event']) ?></p>
    </div>
    <?php endif; ?>
    
    <!-- Action Buttons -->
    <?php if ($meeting['status'] === 'scanning'): ?>
    <div class="btn-group mt-6">
        <a href="<?= BASE_URL ?>/meeting/scan.php?id=<?= $meetingId ?>" class="btn btn-primary">
            <i class="fas fa-qrcode"></i> Lanjut Scan
        </a>
        <button class="btn btn-secondary" onclick="copyToClipboard('<?= $absentUrl ?>', this)">
            <i class="fas fa-share-alt"></i> Share Link Absent
        </button>
        <?php if ($meeting['mode_rapat'] === 'online'): ?>
        <button class="btn btn-secondary" onclick="copyToClipboard('<?= $onlineUrl ?>', this)">
            <i class="fas fa-link"></i> Share Link Online
        </button>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<!-- Attendance Stats -->
<div class="stat-cards">
    <div class="stat-card">
        <div class="stat-icon green"><i class="fas fa-check"></i></div>
        <div class="stat-info">
            <h3><?= $stats['hadir'] ?></h3>
            <p>Hadir</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon orange"><i class="fas fa-info-circle"></i></div>
        <div class="stat-info">
            <h3><?= $stats['izin'] ?></h3>
            <p>Izin</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon blue"><i class="fas fa-medkit"></i></div>
        <div class="stat-info">
            <h3><?= $stats['sakit'] ?></h3>
            <p>Sakit</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon red"><i class="fas fa-times"></i></div>
        <div class="stat-info">
            <h3><?= $stats['alpha'] ?></h3>
            <p>Alpha</p>
        </div>
    </div>
</div>

<!-- Attendance Table -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-users"></i> Daftar Kehadiran</h3>
        <span class="badge badge-primary"><?= $totalPeserta ?> peserta</span>
    </div>
    
    <div class="table-wrapper">
        <table class="table">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama</th>
                    <th>Departemen</th>
                    <th>Jabatan</th>
                    <th>Status</th>
                    <th>Waktu</th>
                    <th>Keterangan</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($attendances as $i => $a): ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td>
                        <strong><?= htmlspecialchars($a['nama_lengkap']) ?></strong>
                        <?php if ($a['is_bph']): ?>
                            <span class="badge badge-success" style="font-size: 0.7em; margin-left: var(--space-2);">BPH Opsional</span>
                        <?php endif; ?>
                    </td>
                    <td><span class="badge badge-dept"><?= htmlspecialchars($a['departemen']) ?></span></td>
                    <td><?= htmlspecialchars($a['jabatan']) ?></td>
                    <td><?= getStatusBadge($a['status']) ?></td>
                    <td style="font-size: var(--font-xs);">
                        <?= $a['scanned_at'] ? date('H:i', strtotime($a['scanned_at'])) : '-' ?>
                    </td>
                    <td style="font-size: var(--font-xs); max-width: 200px;">
                        <?= $a['keterangan'] ? htmlspecialchars(mb_substr($a['keterangan'], 0, 60)) : '-' ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="<?= BASE_URL ?>/assets/js/app.js"></script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
