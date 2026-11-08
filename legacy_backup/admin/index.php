<?php
/**
 * Admin Dashboard
 * Absensi HIMA TI Politeknik Hasnur
 */

require_once __DIR__.'/../includes/auth_check.php';

if (! isAdmin()) {
    setFlash('danger', 'Anda tidak memiliki akses ke halaman ini.');
    redirect(BASE_URL.'/dashboard/index.php');
}

$pageTitle = 'Admin Dashboard';
$pageIcon = 'fas fa-tachometer-alt';
$db = getDB();

// Stats
$totalMembers = countRecords('users');
$totalMeetings = countRecords('meetings');
$completedMeetings = countRecords('meetings', "status = 'completed'");
$activeMeetings = countRecords('meetings', "status = 'scanning'");

// Average attendance rate
$stmt = $db->query("
    SELECT 
        ROUND(AVG(hadir_pct), 1) as avg_pct
    FROM (
        SELECT m.id,
            (SUM(CASE WHEN a.status = 'hadir' THEN 1 ELSE 0 END) * 100.0 / COUNT(*)) as hadir_pct
        FROM meetings m
        JOIN attendances a ON a.meeting_id = m.id
        WHERE m.status = 'completed'
        GROUP BY m.id
    ) sub
");
$avgAttendance = $stmt->fetch()['avg_pct'] ?? 0;

// Monthly meeting counts (last 6 months)
$monthlyData = [];
for ($i = 5; $i >= 0; $i--) {
    $month = date('n', strtotime("-$i months"));
    $year = date('Y', strtotime("-$i months"));
    $label = date('M Y', strtotime("-$i months"));

    $stmt = $db->prepare("SELECT COUNT(*) as total FROM meetings WHERE MONTH(tanggal) = ? AND YEAR(tanggal) = ? AND status = 'completed'");
    $stmt->execute([$month, $year]);
    $count = $stmt->fetch()['total'];

    $monthlyData[] = ['label' => $label, 'count' => $count];
}

// Top attendees
$stmt = $db->query("
    SELECT u.nama_lengkap, u.departemen, 
        COUNT(CASE WHEN a.status = 'hadir' THEN 1 END) as total_hadir,
        COUNT(*) as total_rapat,
        ROUND(COUNT(CASE WHEN a.status = 'hadir' THEN 1 END) * 100.0 / COUNT(*), 1) as pct
    FROM users u
    LEFT JOIN attendances a ON a.user_id = u.id
    LEFT JOIN meetings m ON a.meeting_id = m.id AND m.status = 'completed'
    GROUP BY u.id
    HAVING total_rapat > 0
    ORDER BY pct DESC
    LIMIT 10
");
$topAttendees = $stmt->fetchAll();

// Recent meetings
$stmt = $db->prepare("
    SELECT m.*, u.nama_lengkap as creator_name,
        (SELECT COUNT(*) FROM attendances WHERE meeting_id = m.id AND status = 'hadir') as jumlah_hadir,
        (SELECT COUNT(*) FROM attendances WHERE meeting_id = m.id) as total_peserta
    FROM meetings m JOIN users u ON m.created_by = u.id
    ORDER BY m.created_at DESC LIMIT 5
");
$stmt->execute();
$recentMeetings = $stmt->fetchAll();

$extraJS = ['https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js'];

include __DIR__.'/../includes/header.php';
?>

<!-- Stats -->
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
            <h3><?= $completedMeetings ?></h3>
            <p>Rapat Selesai</p>
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

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: var(--space-6);">
    <!-- Chart -->
    <div class="card">
        <h3 class="card-title mb-4"><i class="fas fa-chart-bar"></i> Rapat Per Bulan</h3>
        <div class="chart-container">
            <canvas id="monthlyChart"></canvas>
        </div>
    </div>
    
    <!-- Top Attendees -->
    <div class="card">
        <h3 class="card-title mb-4"><i class="fas fa-trophy"></i> Kehadiran Terbaik</h3>
        <?php foreach (array_slice($topAttendees, 0, 5) as $i => $att) { ?>
        <div class="d-flex align-center gap-3 mb-4">
            <div style="width: 28px; height: 28px; border-radius: 50%; background: <?= $i === 0 ? 'var(--primary)' : 'var(--dark-surface-3)' ?>; display: flex; align-items: center; justify-content: center; font-size: var(--font-xs); font-weight: 700; color: white; flex-shrink: 0;">
                <?= $i + 1 ?>
            </div>
            <div style="flex: 1; min-width: 0;">
                <div style="font-size: var(--font-sm); font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                    <?= htmlspecialchars($att['nama_lengkap']) ?>
                </div>
                <div style="font-size: var(--font-xs); color: var(--text-muted);"><?= $att['departemen'] ?></div>
            </div>
            <span class="badge badge-success"><?= $att['pct'] ?>%</span>
        </div>
        <?php } ?>
    </div>
</div>

<!-- Recent Meetings -->
<div class="card mt-6">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-clock"></i> Rapat Terbaru</h3>
        <a href="<?= BASE_URL ?>/admin/meetings.php" class="btn btn-ghost btn-sm">Lihat Semua <i class="fas fa-arrow-right"></i></a>
    </div>
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
                <?php foreach ($recentMeetings as $m) { ?>
                <tr>
                    <td><?= formatTanggal($m['tanggal'], 'short') ?></td>
                    <td><span class="badge badge-primary"><?= getJenisRapatLabel($m['jenis_rapat']) ?></span></td>
                    <td><?= htmlspecialchars($m['creator_name']) ?></td>
                    <td><strong style="color: var(--success);"><?= $m['jumlah_hadir'] ?></strong>/<?= $m['total_peserta'] ?></td>
                    <td><?= getMeetingStatusBadge($m['status']) ?></td>
                    <td>
                        <a href="<?= BASE_URL ?>/meeting/detail.php?id=<?= $m['id'] ?>" class="btn btn-ghost btn-sm">
                            <i class="fas fa-eye"></i>
                        </a>
                    </td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const ctx = document.getElementById('monthlyChart');
    if (ctx) {
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_column($monthlyData, 'label')) ?>,
                datasets: [{
                    label: 'Jumlah Rapat',
                    data: <?= json_encode(array_column($monthlyData, 'count')) ?>,
                    backgroundColor: 'rgba(245, 146, 27, 0.6)',
                    borderColor: '#F5921B',
                    borderWidth: 2,
                    borderRadius: 8,
                    barThickness: 40
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { color: 'rgba(255,255,255,0.5)', stepSize: 1 },
                        grid: { color: 'rgba(255,255,255,0.05)' }
                    },
                    x: {
                        ticks: { color: 'rgba(255,255,255,0.5)' },
                        grid: { display: false }
                    }
                }
            }
        });
    }
});
</script>

<style>
@media (max-width: 768px) {
    div[style*="grid-template-columns: 2fr 1fr"] {
        grid-template-columns: 1fr !important;
    }
}
</style>

<?php include __DIR__.'/../includes/footer.php'; ?>
