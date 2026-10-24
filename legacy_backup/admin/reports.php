<?php
/**
 * Admin: Reports
 */

require_once __DIR__ . '/../includes/auth_check.php';

if (!isAdmin()) { redirect(BASE_URL . '/dashboard/index.php'); }

$pageTitle = 'Laporan Absensi';
$pageIcon = 'fas fa-chart-bar';
$db = getDB();

$filterMonth = intval($_GET['month'] ?? date('n'));
$filterYear = intval($_GET['year'] ?? date('Y'));
$filterDept = sanitize($_GET['dept'] ?? '');
$departemenList = json_decode(DEPARTEMEN_LIST, true);

// Per-user attendance
$deptWhere = $filterDept ? " AND u.departemen = ?" : "";
$deptParams = $filterDept ? [$filterMonth, $filterYear, $filterDept] : [$filterMonth, $filterYear];

$stmt = $db->prepare("
    SELECT u.nama_lengkap, u.departemen, u.jabatan,
        COUNT(DISTINCT a.meeting_id) as total_rapat,
        SUM(CASE WHEN a.status = 'hadir' THEN 1 ELSE 0 END) as hadir,
        SUM(CASE WHEN a.status = 'izin' THEN 1 ELSE 0 END) as izin,
        SUM(CASE WHEN a.status = 'sakit' THEN 1 ELSE 0 END) as sakit,
        SUM(CASE WHEN a.status = 'alpha' THEN 1 ELSE 0 END) as alpha
    FROM users u
    LEFT JOIN attendances a ON a.user_id = u.id
    LEFT JOIN meetings m ON a.meeting_id = m.id AND MONTH(m.tanggal) = ? AND YEAR(m.tanggal) = ? AND m.status = 'completed'
    WHERE 1=1 $deptWhere
    GROUP BY u.id
    ORDER BY u.departemen, u.nama_lengkap
");
$stmt->execute($deptParams);
$reportData = $stmt->fetchAll();

// Per-department summary
$stmt = $db->prepare("
    SELECT u.departemen,
        COUNT(DISTINCT u.id) as total_anggota,
        ROUND(AVG(CASE WHEN a.status = 'hadir' THEN 100 ELSE 0 END), 1) as avg_hadir
    FROM users u
    LEFT JOIN attendances a ON a.user_id = u.id
    LEFT JOIN meetings m ON a.meeting_id = m.id AND MONTH(m.tanggal) = ? AND YEAR(m.tanggal) = ? AND m.status = 'completed'
    GROUP BY u.departemen
    ORDER BY avg_hadir DESC
");
$stmt->execute([$filterMonth, $filterYear]);
$deptSummary = $stmt->fetchAll();

$bulanList = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];

include __DIR__ . '/../includes/header.php';
?>

<!-- Filter -->
<div class="filter-bar">
    <select class="form-control" style="max-width: 150px;" onchange="applyReportFilter('month', this.value)">
        <?php for ($m = 1; $m <= 12; $m++): ?>
            <option value="<?= $m ?>" <?= $filterMonth === $m ? 'selected' : '' ?>><?= $bulanList[$m] ?></option>
        <?php endfor; ?>
    </select>
    
    <select class="form-control" style="max-width: 120px;" onchange="applyReportFilter('year', this.value)">
        <?php for ($y = date('Y'); $y >= date('Y') - 2; $y--): ?>
            <option value="<?= $y ?>" <?= $filterYear === $y ? 'selected' : '' ?>><?= $y ?></option>
        <?php endfor; ?>
    </select>
    
    <select class="form-control" style="max-width: 200px;" onchange="applyReportFilter('dept', this.value)">
        <option value="">Semua Departemen</option>
        <?php foreach ($departemenList as $key => $label): ?>
            <option value="<?= $key ?>" <?= $filterDept === $key ? 'selected' : '' ?>><?= $label ?></option>
        <?php endforeach; ?>
    </select>
</div>

<!-- Department Summary -->
<div class="stat-cards mb-6">
    <?php foreach ($deptSummary as $ds): ?>
    <div class="stat-card">
        <div class="stat-icon orange"><i class="fas fa-building"></i></div>
        <div class="stat-info">
            <h3><?= $ds['avg_hadir'] ?: 0 ?>%</h3>
            <p><?= $ds['departemen'] ?> (<?= $ds['total_anggota'] ?> org)</p>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Detail Table -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-table"></i> Rekap Per Anggota — <?= $bulanList[$filterMonth] ?> <?= $filterYear ?></h3>
    </div>
    
    <div class="table-wrapper">
        <table class="table">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama</th>
                    <th>Departemen</th>
                    <th>Jabatan</th>
                    <th>Total Rapat</th>
                    <th><i class="fas fa-check text-success"></i> Hadir</th>
                    <th><i class="fas fa-info-circle text-warning"></i> Izin</th>
                    <th><i class="fas fa-medkit" style="color: var(--info);"></i> Sakit</th>
                    <th><i class="fas fa-times text-danger"></i> Alpha</th>
                    <th>%</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reportData as $i => $r): ?>
                <?php $pct = $r['total_rapat'] > 0 ? round(($r['hadir'] / $r['total_rapat']) * 100) : 0; ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td><strong><?= htmlspecialchars($r['nama_lengkap']) ?></strong></td>
                    <td><span class="badge badge-dept"><?= $r['departemen'] ?></span></td>
                    <td><?= htmlspecialchars($r['jabatan']) ?></td>
                    <td><?= $r['total_rapat'] ?></td>
                    <td style="color: var(--success); font-weight: 600;"><?= $r['hadir'] ?></td>
                    <td style="color: var(--warning);"><?= $r['izin'] ?></td>
                    <td style="color: var(--info);"><?= $r['sakit'] ?></td>
                    <td style="color: var(--danger);"><?= $r['alpha'] ?></td>
                    <td>
                        <span class="badge <?= $pct >= 80 ? 'badge-success' : ($pct >= 60 ? 'badge-warning' : 'badge-danger') ?>">
                            <?= $pct ?>%
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function applyReportFilter(key, value) {
    const url = new URL(window.location.href);
    if (value) {
        url.searchParams.set(key, value);
    } else {
        url.searchParams.delete(key);
    }
    window.location.href = url.toString();
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
