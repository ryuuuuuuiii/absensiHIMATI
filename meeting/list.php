<?php
/**
 * Meeting List Page
 */

require_once __DIR__ . '/../includes/auth_check.php';

$pageTitle = 'Riwayat Rapat';
$pageIcon = 'fas fa-calendar-alt';
$db = getDB();

// Filters
$filterJenis = sanitize($_GET['jenis'] ?? '');
$filterStatus = sanitize($_GET['status'] ?? '');
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Build query
$where = "1=1";
$params = [];

if ($filterJenis) {
    $where .= " AND m.jenis_rapat = ?";
    $params[] = $filterJenis;
}
if ($filterStatus) {
    $where .= " AND m.status = ?";
    $params[] = $filterStatus;
}

// Count total
$countStmt = $db->prepare("SELECT COUNT(*) as total FROM meetings m WHERE $where");
$countStmt->execute($params);
$totalRecords = $countStmt->fetch()['total'];
$totalPages = ceil($totalRecords / $perPage);

// Get meetings
$stmt = $db->prepare("
    SELECT m.*, u.nama_lengkap as creator_name,
        (SELECT COUNT(*) FROM attendances WHERE meeting_id = m.id AND status = 'hadir') as jumlah_hadir,
        (SELECT COUNT(*) FROM attendances WHERE meeting_id = m.id) as total_peserta
    FROM meetings m 
    JOIN users u ON m.created_by = u.id 
    WHERE $where
    ORDER BY m.tanggal DESC, m.waktu_mulai DESC
    LIMIT $perPage OFFSET $offset
");
$stmt->execute($params);
$meetings = $stmt->fetchAll();

$jenisRapatOptions = json_decode(JENIS_RAPAT, true);

include __DIR__ . '/../includes/header.php';
?>

<!-- Filter Bar -->
<div class="filter-bar">
    <div class="search-input-wrapper">
        <i class="fas fa-search"></i>
        <input type="text" class="form-control" placeholder="Cari rapat..." id="searchMeeting" onkeyup="filterTable()">
    </div>
    
    <select class="form-control" style="max-width: 200px;" onchange="applyFilter('jenis', this.value)">
        <option value="">Semua Jenis</option>
        <?php foreach ($jenisRapatOptions as $key => $label): ?>
            <option value="<?= $key ?>" <?= $filterJenis === $key ? 'selected' : '' ?>><?= $label ?></option>
        <?php endforeach; ?>
    </select>
    
    <select class="form-control" style="max-width: 160px;" onchange="applyFilter('status', this.value)">
        <option value="">Semua Status</option>
        <option value="scanning" <?= $filterStatus === 'scanning' ? 'selected' : '' ?>>Scanning</option>
        <option value="completed" <?= $filterStatus === 'completed' ? 'selected' : '' ?>>Selesai</option>
        <option value="cancelled" <?= $filterStatus === 'cancelled' ? 'selected' : '' ?>>Dibatalkan</option>
    </select>
    
    <a href="<?= BASE_URL ?>/meeting/create.php" class="btn btn-primary">
        <i class="fas fa-plus"></i> Buat Rapat
    </a>
</div>

<!-- Meeting Table -->
<div class="card">
    <?php if (empty($meetings)): ?>
        <div class="empty-state">
            <i class="fas fa-calendar-times"></i>
            <h3>Tidak Ada Rapat</h3>
            <p>Belum ada rapat yang ditemukan dengan filter ini.</p>
        </div>
    <?php else: ?>
        <div class="table-wrapper">
            <table class="table" id="meetingTable">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Jenis</th>
                        <th>Agenda</th>
                        <th>Mode</th>
                        <th>Kehadiran</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($meetings as $m): ?>
                    <tr>
                        <td>
                            <div style="font-weight: 600;"><?= formatTanggal($m['tanggal'], 'short') ?></div>
                            <small style="color: var(--text-muted);"><?= date('H:i', strtotime($m['waktu_mulai'])) ?> WITA</small>
                        </td>
                        <td><span class="badge badge-primary"><?= getJenisRapatLabel($m['jenis_rapat']) ?></span></td>
                        <td style="max-width: 200px;">
                            <?php 
                            $agenda = $m['fokus_bahasan'] ?: $m['nama_event'] ?: '-';
                            echo htmlspecialchars(mb_substr($agenda, 0, 40));
                            if (mb_strlen($agenda) > 40) echo '...';
                            ?>
                        </td>
                        <td><?= $m['mode_rapat'] === 'offline' ? '🏢' : '🌐' ?></td>
                        <td>
                            <span style="color: var(--success); font-weight: 600;"><?= $m['jumlah_hadir'] ?></span>
                            <span style="color: var(--text-muted);">/ <?= $m['total_peserta'] ?></span>
                        </td>
                        <td><?= getMeetingStatusBadge($m['status']) ?></td>
                        <td>
                            <div class="btn-group">
                                <a href="<?= BASE_URL ?>/meeting/detail.php?id=<?= $m['id'] ?>" class="btn btn-ghost btn-sm" title="Detail">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <?php if ($m['status'] === 'scanning'): ?>
                                <a href="<?= BASE_URL ?>/meeting/scan.php?id=<?= $m['id'] ?>" class="btn btn-ghost btn-sm" title="Scan">
                                    <i class="fas fa-qrcode"></i>
                                </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <?php 
                $queryParams = $_GET;
                $queryParams['page'] = $i;
                $url = '?' . http_build_query($queryParams);
                ?>
                <a href="<?= $url ?>" class="<?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<script>
function applyFilter(key, value) {
    const url = new URL(window.location.href);
    if (value) {
        url.searchParams.set(key, value);
    } else {
        url.searchParams.delete(key);
    }
    url.searchParams.delete('page');
    window.location.href = url.toString();
}

function filterTable() {
    const search = document.getElementById('searchMeeting').value.toLowerCase();
    const rows = document.querySelectorAll('#meetingTable tbody tr');
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(search) ? '' : 'none';
    });
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
