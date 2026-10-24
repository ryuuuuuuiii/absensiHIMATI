<?php
/**
 * Admin Dashboard
 * Absensi HIMA TI Politeknik Hasnur
 */

require_once __DIR__ . '/../includes/auth_check.php';

$pageTitle = 'Dashboard';
$pageIcon = 'fas fa-th-large';
$currentUser = getCurrentUser();
$db = getDB();

// ---- Stats ----
$currentMonth = date('n');
$currentYear = date('Y');

$totalMembers = countRecords('users');

$stmt = $db->prepare("SELECT COUNT(*) as total FROM meetings WHERE MONTH(tanggal) = ? AND YEAR(tanggal) = ? AND status = 'completed'");
$stmt->execute([$currentMonth, $currentYear]);
$totalRapatBulanIni = $stmt->fetch()['total'];

$totalSemuaRapat = countRecords('meetings', "status = 'completed'");
$activeMeetings = countRecords('meetings', "status = 'scanning'");

// Average attendance rate
$stmt = $db->query("
    SELECT ROUND(AVG(hadir_pct), 1) as avg_pct FROM (
        SELECT m.id, (SUM(CASE WHEN a.status = 'hadir' THEN 1 ELSE 0 END) * 100.0 / COUNT(*)) as hadir_pct
        FROM meetings m JOIN attendances a ON a.meeting_id = m.id
        WHERE m.status = 'completed' GROUP BY m.id
    ) sub
");
$avgAttendance = $stmt->fetch()['avg_pct'] ?? 0;

// Public link
$publicLink = BASE_URL . '/rapat';

// 5 Rapat terakhir
$stmt = $db->prepare("
    SELECT m.*, u.nama_lengkap as creator_name,
        (SELECT COUNT(*) FROM attendances WHERE meeting_id = m.id AND status = 'hadir') as jumlah_hadir,
        (SELECT COUNT(*) FROM attendances WHERE meeting_id = m.id) as total_peserta
    FROM meetings m JOIN users u ON m.created_by = u.id
    ORDER BY m.tanggal DESC, m.waktu_mulai DESC LIMIT 5
");
$stmt->execute();
$rapatTerakhir = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<!-- Stat Cards -->
<div class="stat-cards">
    <div class="stat-card">
        <div class="stat-icon orange"><i class="fas fa-users"></i></div>
        <div class="stat-info">
            <h3><?= $totalMembers ?></h3>
            <p>Total Pengurus</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i class="fas fa-calendar-check"></i></div>
        <div class="stat-info">
            <h3><?= $totalRapatBulanIni ?></h3>
            <p>Rapat Bulan Ini</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon blue"><i class="fas fa-broadcast-tower"></i></div>
        <div class="stat-info">
            <h3><?= $activeMeetings ?></h3>
            <p>Rapat Aktif</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon red"><i class="fas fa-chart-line"></i></div>
        <div class="stat-info">
            <h3><?= $avgAttendance ?>%</h3>
            <p>Rata-rata Kehadiran</p>
        </div>
    </div>
</div>

<!-- Public Link Card -->
<div class="card mb-6" style="border-left: 4px solid var(--primary);">
    <div class="d-flex align-center justify-between flex-wrap gap-4">
        <div>
            <h3 style="color: var(--primary); margin-bottom: var(--space-1);">
                <i class="fas fa-link"></i> Link Publik Buat Rapat
            </h3>
            <p style="font-size: var(--font-sm); color: var(--text-muted);">
                Share link ini ke semua pengurus agar bisa membuat rapat tanpa login
            </p>
        </div>
        <div style="display: flex; gap: var(--space-3);">
            <button class="btn btn-primary" onclick="copyToClipboard('<?= $publicLink ?>', this)">
                <i class="fas fa-copy"></i> Salin Link
            </button>
            <a href="<?= $publicLink ?>" class="btn btn-secondary" target="_blank">
                <i class="fas fa-external-link-alt"></i> Buka
            </a>
        </div>
    </div>
    <div class="share-link-box mt-4">
        <input type="text" value="<?= $publicLink ?>" readonly>
        <button onclick="copyToClipboard('<?= $publicLink ?>', this)"><i class="fas fa-copy"></i> Salin</button>
    </div>
</div>

<!-- Quick Actions -->
<div class="quick-actions">
    <a href="<?= BASE_URL ?>/admin/members.php" class="quick-action-card">
        <i class="fas fa-users-cog"></i>
        <span>Kelola Anggota</span>
    </a>
    <a href="<?= BASE_URL ?>/admin/meetings.php" class="quick-action-card">
        <i class="fas fa-clipboard-list"></i>
        <span>Kelola Rapat</span>
    </a>
    <a href="<?= BASE_URL ?>/admin/reports.php" class="quick-action-card">
        <i class="fas fa-chart-bar"></i>
        <span>Laporan</span>
    </a>
    <a href="<?= BASE_URL ?>/admin/generate_qr.php" class="quick-action-card">
        <i class="fas fa-qrcode"></i>
        <span>Generate QR</span>
    </a>
</div>

<!-- Rapat Terakhir -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title"><i class="fas fa-clock"></i> Rapat Terbaru</h2>
        <a href="<?= BASE_URL ?>/admin/meetings.php" class="btn btn-ghost btn-sm">Lihat Semua <i class="fas fa-arrow-right"></i></a>
    </div>
    
    <?php if (empty($rapatTerakhir)): ?>
        <div class="empty-state">
            <i class="fas fa-calendar-times"></i>
            <h3>Belum Ada Rapat</h3>
            <p>Pengurus bisa membuat rapat melalui link publik.</p>
        </div>
    <?php else: ?>
        <div class="table-wrapper">
            <table class="table">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Jenis</th>
                        <th>Pembuat</th>
                        <th>Kehadiran</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rapatTerakhir as $rapat): ?>
                    <tr>
                        <td>
                            <div style="font-weight: 600;"><?= formatTanggal($rapat['tanggal'], 'short') ?></div>
                            <small style="color: var(--text-muted);"><?= date('H:i', strtotime($rapat['waktu_mulai'])) ?> WITA</small>
                        </td>
                        <td><span class="badge badge-primary"><?= getJenisRapatLabel($rapat['jenis_rapat']) ?></span></td>
                        <td><?= htmlspecialchars($rapat['creator_name']) ?></td>
                        <td><strong style="color: var(--success);"><?= $rapat['jumlah_hadir'] ?></strong>/<?= $rapat['total_peserta'] ?></td>
                        <td><?= getMeetingStatusBadge($rapat['status']) ?></td>
                        <td>
                            <a href="<?= BASE_URL ?>/meeting/detail.php?id=<?= $rapat['id'] ?>" class="btn btn-ghost btn-sm"><i class="fas fa-eye"></i></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
