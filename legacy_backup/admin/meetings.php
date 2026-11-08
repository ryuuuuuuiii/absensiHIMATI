<?php
/**
 * Admin: Meetings Management
 */

require_once __DIR__.'/../includes/auth_check.php';

if (! isAdmin()) {
    redirect(BASE_URL.'/dashboard/index.php');
}

$pageTitle = 'Kelola Rapat';
$pageIcon = 'fas fa-clipboard-list';
$db = getDB();

// Handle delete/cancel
if (isset($_GET['cancel']) && isset($_GET['csrf'])) {
    if (validateCSRF($_GET['csrf'])) {
        $cancelId = intval($_GET['cancel']);
        $stmt = $db->prepare("UPDATE meetings SET status = 'cancelled' WHERE id = ?");
        $stmt->execute([$cancelId]);
        setFlash('success', 'Rapat berhasil dibatalkan.');
    }
    redirect(BASE_URL.'/admin/meetings.php');
}

if (isset($_GET['delete']) && isset($_GET['csrf'])) {
    if (validateCSRF($_GET['csrf'])) {
        $deleteId = intval($_GET['delete']);
        $stmt = $db->prepare('DELETE FROM meetings WHERE id = ?');
        $stmt->execute([$deleteId]);
        setFlash('success', 'Rapat berhasil dihapus.');
    }
    redirect(BASE_URL.'/admin/meetings.php');
}

// Get all meetings
$stmt = $db->prepare("
    SELECT m.*, u.nama_lengkap as creator_name,
        (SELECT COUNT(*) FROM attendances WHERE meeting_id = m.id AND status = 'hadir') as jumlah_hadir,
        (SELECT COUNT(*) FROM attendances WHERE meeting_id = m.id) as total_peserta
    FROM meetings m JOIN users u ON m.created_by = u.id
    ORDER BY m.tanggal DESC, m.waktu_mulai DESC
");
$stmt->execute();
$meetings = $stmt->fetchAll();

include __DIR__.'/../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-clipboard-list"></i> Semua Rapat</h3>
        <span class="badge badge-primary"><?= count($meetings) ?> rapat</span>
    </div>
    
    <div class="table-wrapper">
        <table class="table">
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Jenis</th>
                    <th>Mode</th>
                    <th>Pembuat</th>
                    <th>Kehadiran</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($meetings as $m) { ?>
                <tr>
                    <td>
                        <strong><?= formatTanggal($m['tanggal'], 'short') ?></strong>
                        <br><small style="color: var(--text-muted);"><?= date('H:i', strtotime($m['waktu_mulai'])) ?> WITA</small>
                    </td>
                    <td><span class="badge badge-primary"><?= getJenisRapatLabel($m['jenis_rapat']) ?></span></td>
                    <td><?= $m['mode_rapat'] === 'offline' ? '🏢' : '🌐' ?></td>
                    <td><?= htmlspecialchars($m['creator_name']) ?></td>
                    <td><strong style="color: var(--success);"><?= $m['jumlah_hadir'] ?></strong>/<?= $m['total_peserta'] ?></td>
                    <td><?= getMeetingStatusBadge($m['status']) ?></td>
                    <td>
                        <div class="btn-group">
                            <a href="<?= BASE_URL ?>/meeting/detail.php?id=<?= $m['id'] ?>" class="btn btn-ghost btn-sm" title="Detail">
                                <i class="fas fa-eye"></i>
                            </a>
                            <?php if ($m['status'] === 'scanning') { ?>
                            <button class="btn btn-ghost btn-sm" title="Batalkan"
                                    onclick="confirmDelete('Batalkan rapat ini?', '<?= BASE_URL ?>/admin/meetings.php?cancel=<?= $m['id'] ?>&csrf=<?= $_SESSION['csrf_token'] ?>')">
                                <i class="fas fa-ban" style="color: var(--warning);"></i>
                            </button>
                            <?php } ?>
                            <button class="btn btn-ghost btn-sm" title="Hapus"
                                    onclick="confirmDelete('Hapus rapat ini beserta semua data absensinya?', '<?= BASE_URL ?>/admin/meetings.php?delete=<?= $m['id'] ?>&csrf=<?= $_SESSION['csrf_token'] ?>')">
                                <i class="fas fa-trash" style="color: var(--danger);"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__.'/../includes/footer.php'; ?>
