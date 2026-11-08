<?php
/**
 * Halaman Kelola Event / Kepanitiaan
 * Absensi HIMA TI Politeknik Hasnur
 */

require_once __DIR__.'/../includes/auth_check.php';

$pageTitle = 'Kelola Event';
$pageIcon = 'fas fa-calendar-alt';

$db = getDB();

// Get active events
$stmtActive = $db->query("
    SELECT e.*, 
           (SELECT COUNT(*) FROM event_divisions WHERE event_id = e.id) as total_divisi,
           (SELECT COUNT(*) FROM event_members WHERE event_id = e.id) as total_panitia
    FROM events e 
    WHERE e.status = 'active'
    ORDER BY e.created_at DESC
");
$activeEvents = $stmtActive->fetchAll();

// Get archived events
$stmtArchived = $db->query("
    SELECT e.*, 
           (SELECT COUNT(*) FROM event_divisions WHERE event_id = e.id) as total_divisi,
           (SELECT COUNT(*) FROM event_members WHERE event_id = e.id) as total_panitia
    FROM events e 
    WHERE e.status = 'archived'
    ORDER BY e.created_at DESC
");
$archivedEvents = $stmtArchived->fetchAll();

include __DIR__.'/../includes/header.php';
?>

<div class="d-flex align-center justify-between mb-6">
    <h1 class="page-title"><i class="fas fa-calendar-alt"></i> Kelola Event</h1>
    <button class="btn btn-primary" onclick="openModal('addEventModal')">
        <i class="fas fa-plus"></i> Tambah Event Baru
    </button>
</div>

<!-- Tabs -->
<div class="tabs mb-6">
    <button class="tab-btn active" onclick="switchTab('active')">Event Aktif (<?= count($activeEvents) ?>)</button>
    <button class="tab-btn" onclick="switchTab('archived')">Arsip (<?= count($archivedEvents) ?>)</button>
</div>

<!-- Active Events -->
<div id="tab-active" class="tab-content active">
    <?php if (empty($activeEvents)) { ?>
    <div class="empty-state">
        <i class="fas fa-calendar-times"></i>
        <h3>Belum ada event aktif</h3>
        <p>Klik tombol "Tambah Event Baru" untuk mulai membuat kepanitiaan.</p>
    </div>
    <?php } else { ?>
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: var(--space-4);">
        <?php foreach ($activeEvents as $event) { ?>
        <div class="card event-card" style="display: flex; flex-direction: column; h-100">
            <div class="card-header border-bottom-0 pb-0 d-flex justify-between align-center">
                <h3 class="card-title text-primary" style="font-size: var(--font-lg); font-weight: 700;">
                    <?= htmlspecialchars($event['nama_event']) ?>
                </h3>
                <span class="badge badge-success">Aktif</span>
            </div>
            <div class="card-body" style="flex: 1;">
                <?php if ($event['deskripsi']) { ?>
                    <p style="color: var(--text-secondary); font-size: var(--font-sm); margin-bottom: var(--space-4); display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                        <?= htmlspecialchars($event['deskripsi']) ?>
                    </p>
                <?php } ?>
                
                <div class="d-flex justify-between" style="background: var(--dark-surface-2); padding: var(--space-3); border-radius: var(--radius-md); margin-bottom: var(--space-4);">
                    <div class="text-center" style="flex: 1;">
                        <div style="font-size: var(--font-lg); font-weight: 700; color: var(--text-primary);"><?= $event['total_divisi'] ?></div>
                        <div style="font-size: var(--font-xs); color: var(--text-muted);">Divisi</div>
                    </div>
                    <div style="width: 1px; background: var(--dark-border);"></div>
                    <div class="text-center" style="flex: 1;">
                        <div style="font-size: var(--font-lg); font-weight: 700; color: var(--text-primary);"><?= $event['total_panitia'] ?></div>
                        <div style="font-size: var(--font-xs); color: var(--text-muted);">Panitia</div>
                    </div>
                </div>
                
                <p style="font-size: var(--font-xs); color: var(--text-muted);">
                    <i class="far fa-clock"></i> Dibuat: <?= date('d M Y', strtotime($event['created_at'])) ?>
                </p>
            </div>
            <div class="card-footer bg-surface-2 mt-auto d-flex justify-between align-center border-top">
                <a href="<?= BASE_URL ?>/admin/event_detail.php?id=<?= $event['id'] ?>" class="btn btn-primary btn-sm">
                    <i class="fas fa-users-cog"></i> Kelola Panitia
                </a>
                <div class="dropdown">
                    <button class="btn btn-icon btn-sm" onclick="toggleDropdown('action-<?= $event['id'] ?>')">
                        <i class="fas fa-ellipsis-v"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-right" id="action-<?= $event['id'] ?>">
                        <a href="#" class="dropdown-item" onclick="editEvent(<?= htmlspecialchars(json_encode($event)) ?>)">
                            <i class="fas fa-edit text-warning"></i> Edit Detail
                        </a>
                        <form method="POST" action="<?= BASE_URL ?>/admin/process/event_process.php" style="display:inline;" onsubmit="return confirm('Arsipkan event ini? (Masih bisa dilihat, tapi tidak bisa dipakai buat rapat baru)');">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            <input type="hidden" name="action" value="archive_event">
                            <input type="hidden" name="id" value="<?= $event['id'] ?>">
                            <button type="submit" class="dropdown-item">
                                <i class="fas fa-archive text-info"></i> Arsipkan
                            </button>
                        </form>
                        <form method="POST" action="<?= BASE_URL ?>/admin/process/event_process.php" style="display:inline;" onsubmit="return confirm('Hapus permanen event ini beserta seluruh data panitianya? (Data absensi rapat yang sudah lewat mungkin terpengaruh)');">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            <input type="hidden" name="action" value="delete_event">
                            <input type="hidden" name="id" value="<?= $event['id'] ?>">
                            <button type="submit" class="dropdown-item text-danger">
                                <i class="fas fa-trash-alt"></i> Hapus
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <?php } ?>
    </div>
    <?php } ?>
</div>

<!-- Archived Events -->
<div id="tab-archived" class="tab-content" style="display: none;">
    <?php if (empty($archivedEvents)) { ?>
    <div class="empty-state">
        <i class="fas fa-archive"></i>
        <h3>Belum ada arsip</h3>
        <p>Event yang sudah selesai bisa diarsipkan di sini.</p>
    </div>
    <?php } else { ?>
    <div class="table-wrapper">
        <table class="table">
            <thead>
                <tr>
                    <th>Nama Event</th>
                    <th>Divisi</th>
                    <th>Panitia</th>
                    <th>Tgl Dibuat</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($archivedEvents as $event) { ?>
                <tr>
                    <td>
                        <div class="d-flex align-center gap-3">
                            <div class="avatar avatar-sm bg-surface-2"><i class="fas fa-calendar-alt text-muted"></i></div>
                            <div>
                                <div class="font-medium text-text-primary"><?= htmlspecialchars($event['nama_event']) ?></div>
                            </div>
                        </div>
                    </td>
                    <td><?= $event['total_divisi'] ?></td>
                    <td><?= $event['total_panitia'] ?></td>
                    <td><?= date('d M Y', strtotime($event['created_at'])) ?></td>
                    <td>
                        <div class="d-flex gap-2">
                            <a href="<?= BASE_URL ?>/admin/event_detail.php?id=<?= $event['id'] ?>" class="btn btn-secondary btn-sm" title="Lihat Detail">
                                <i class="fas fa-eye"></i>
                            </a>
                            <form method="POST" action="<?= BASE_URL ?>/admin/process/event_process.php" style="display:inline;" onsubmit="return confirm('Aktifkan kembali event ini?');">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                <input type="hidden" name="action" value="restore_event">
                                <input type="hidden" name="id" value="<?= $event['id'] ?>">
                                <button type="submit" class="btn btn-success btn-sm" title="Restore">
                                    <i class="fas fa-undo"></i>
                                </button>
                            </form>
                            <form method="POST" action="<?= BASE_URL ?>/admin/process/event_process.php" style="display:inline;" onsubmit="return confirm('Hapus permanen event ini beserta seluruh data panitianya? (Data absensi rapat yang sudah lewat mungkin terpengaruh)');">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                <input type="hidden" name="action" value="delete_event">
                                <input type="hidden" name="id" value="<?= $event['id'] ?>">
                                <button type="submit" class="btn btn-danger btn-sm" title="Hapus Permanen">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
    <?php } ?>
</div>

<!-- Modal Add/Edit Event -->
<div id="addEventModal" class="modal">
    <div class="modal-content" style="max-width: 500px;">
        <div class="modal-header">
            <h3 class="modal-title" id="modalTitle">Tambah Event Baru</h3>
            <span class="close" onclick="closeModal('addEventModal')">&times;</span>
        </div>
        <div class="modal-body">
            <form action="<?= BASE_URL ?>/admin/process/event_process.php" method="POST" id="eventForm">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <input type="hidden" name="action" value="add_event" id="formAction">
                <input type="hidden" name="id" value="" id="eventId">
                
                <div class="form-group">
                    <label class="form-label">Nama Event / Kepanitiaan <span class="required">*</span></label>
                    <input type="text" name="nama_event" id="namaEvent" class="form-control" required placeholder="Contoh: Seminar Nasional IT 2024">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Deskripsi Singkat (Opsional)</label>
                    <textarea name="deskripsi" id="deskripsiEvent" class="form-control" rows="3" placeholder="Contoh: Acara tahunan HIMA TI..."></textarea>
                </div>
                
                <div class="d-flex justify-end gap-3 mt-6">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('addEventModal')">Batal</button>
                    <button type="submit" class="btn btn-primary" id="btnSubmit">Simpan Event</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function switchTab(tabId) {
    document.querySelectorAll('.tab-content').forEach(el => el.style.display = 'none');
    document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
    
    document.getElementById('tab-' + tabId).style.display = 'block';
    event.currentTarget.classList.add('active');
}

function editEvent(eventObj) {
    document.getElementById('modalTitle').textContent = 'Edit Event';
    document.getElementById('formAction').value = 'edit_event';
    document.getElementById('eventId').value = eventObj.id;
    document.getElementById('namaEvent').value = eventObj.nama_event;
    document.getElementById('deskripsiEvent').value = eventObj.deskripsi;
    document.getElementById('btnSubmit').textContent = 'Update Event';
    
    openModal('addEventModal');
}

// Reset modal on close
document.getElementById('addEventModal').addEventListener('click', function(e) {
    if (e.target.classList.contains('close') || e.target.id === 'addEventModal') {
        setTimeout(() => {
            document.getElementById('modalTitle').textContent = 'Tambah Event Baru';
            document.getElementById('formAction').value = 'add_event';
            document.getElementById('eventId').value = '';
            document.getElementById('eventForm').reset();
            document.getElementById('btnSubmit').textContent = 'Simpan Event';
        }, 300);
    }
});
</script>

<?php include __DIR__.'/../includes/footer.php'; ?>
