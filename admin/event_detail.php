<?php
/**
 * Halaman Detail Event & Kelola Panitia
 * Absensi HIMA TI Politeknik Hasnur
 */

require_once __DIR__ . '/../includes/auth_check.php';

$eventId = intval($_GET['id'] ?? 0);
if (!$eventId) {
    setFlash('danger', 'ID Event tidak valid.');
    redirect(BASE_URL . '/admin/events.php');
}

$db = getDB();

// Get event
$stmt = $db->prepare("SELECT * FROM events WHERE id = ?");
$stmt->execute([$eventId]);
$event = $stmt->fetch();

if (!$event) {
    setFlash('danger', 'Event tidak ditemukan.');
    redirect(BASE_URL . '/admin/events.php');
}

$pageTitle = htmlspecialchars($event['nama_event']);
$pageIcon = 'fas fa-calendar-star';

// Get divisions and members count
$stmtDivisions = $db->prepare("
    SELECT d.*, 
           (SELECT COUNT(*) FROM event_members WHERE division_id = d.id) as total_member
    FROM event_divisions d 
    WHERE d.event_id = ?
    ORDER BY d.nama_divisi ASC
");
$stmtDivisions->execute([$eventId]);
$divisions = $stmtDivisions->fetchAll();

// Get all members grouped by division
$stmtMembers = $db->prepare("
    SELECT em.*, u.nama_lengkap, u.departemen, d.nama_divisi
    FROM event_members em
    JOIN users u ON em.user_id = u.id
    JOIN event_divisions d ON em.division_id = d.id
    WHERE em.event_id = ?
    ORDER BY d.nama_divisi ASC, u.nama_lengkap ASC
");
$stmtMembers->execute([$eventId]);
$membersRaw = $stmtMembers->fetchAll();

$membersByDiv = [];
foreach ($membersRaw as $m) {
    $membersByDiv[$m['division_id']][] = $m;
}

// Get all users for assignment dropdown (excluding those already in the specific division)
$stmtUsers = $db->query("SELECT id, nama_lengkap, departemen FROM users ORDER BY departemen ASC, nama_lengkap ASC");
$allUsers = $stmtUsers->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<!-- Header Info -->
<div class="card mb-6" style="border-left: 4px solid var(--primary);">
    <div class="d-flex align-center justify-between flex-wrap gap-4 mb-2">
        <h2 style="color: var(--primary); font-size: var(--font-xl); margin: 0;">
            <?= htmlspecialchars($event['nama_event']) ?>
        </h2>
        <a href="<?= BASE_URL ?>/admin/events.php" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>
    <?php if ($event['deskripsi']): ?>
        <p style="color: var(--text-secondary); margin-bottom: 0;"><?= htmlspecialchars($event['deskripsi']) ?></p>
    <?php endif; ?>
</div>

<div style="display: grid; grid-template-columns: 1fr 2fr; gap: var(--space-6);">
    <!-- Left Column: Manage Divisions -->
    <div style="min-width: 0;">
        <div class="card h-100">
            <div class="card-header d-flex justify-between align-center">
                <h3 class="card-title"><i class="fas fa-sitemap"></i> Daftar Divisi</h3>
                <button class="btn btn-primary btn-sm" onclick="openModal('addDivModal')">
                    <i class="fas fa-plus"></i> Tambah
                </button>
            </div>
            
            <div class="card-body p-0">
                <?php if (empty($divisions)): ?>
                <div class="empty-state" style="padding: var(--space-6) var(--space-4);">
                    <i class="fas fa-sitemap text-muted"></i>
                    <p style="margin-top: var(--space-2); color: var(--text-muted);">Belum ada divisi.</p>
                </div>
                <?php else: ?>
                <ul class="list-group list-group-flush">
                    <?php foreach ($divisions as $div): ?>
                    <li class="list-group-item d-flex justify-between align-center" style="padding: var(--space-3) var(--space-4);">
                        <div>
                            <div style="font-weight: 600; color: var(--text-primary);">
                                <?= htmlspecialchars($div['nama_divisi']) ?>
                            </div>
                            <div style="font-size: var(--font-xs); color: var(--text-muted);">
                                <?= $div['total_member'] ?> panitia
                            </div>
                        </div>
                        <div class="dropdown">
                            <button class="btn btn-icon btn-sm" onclick="toggleDropdown('action-div-<?= $div['id'] ?>')">
                                <i class="fas fa-ellipsis-v"></i>
                            </button>
                            <div class="dropdown-menu dropdown-menu-right" id="action-div-<?= $div['id'] ?>">
                                <a href="#" class="dropdown-item" onclick="editDiv(<?= htmlspecialchars(json_encode($div)) ?>)">
                                    <i class="fas fa-edit text-warning"></i> Edit Nama
                                </a>
                                <form method="POST" action="<?= BASE_URL ?>/admin/process/event_process.php" style="display:inline;" onsubmit="return confirm('Hapus divisi ini beserta seluruh panitianya?');">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                    <input type="hidden" name="action" value="delete_division">
                                    <input type="hidden" name="id" value="<?= $div['id'] ?>">
                                    <input type="hidden" name="event_id" value="<?= $eventId ?>">
                                    <button type="submit" class="dropdown-item text-danger">
                                        <i class="fas fa-trash-alt"></i> Hapus
                                    </button>
                                </form>
                            </div>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Right Column: Manage Members -->
    <div style="min-width: 0;">
        <div class="card h-100">
            <div class="card-header d-flex justify-between align-center">
                <h3 class="card-title"><i class="fas fa-users"></i> Susunan Panitia</h3>
                <?php if (!empty($divisions)): ?>
                <button class="btn btn-success btn-sm" onclick="openModal('addMemberModal')">
                    <i class="fas fa-user-plus"></i> Tambah Panitia
                </button>
                <?php endif; ?>
            </div>
            
            <div class="card-body">
                <?php if (empty($divisions)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> Buat divisi terlebih dahulu sebelum menambahkan panitia.
                </div>
                <?php else: ?>
                    
                    <?php foreach ($divisions as $div): ?>
                    <div style="margin-bottom: var(--space-6);">
                        <div class="d-flex justify-between align-center mb-3" style="border-bottom: 1px solid var(--dark-border); padding-bottom: var(--space-2);">
                            <h4 style="color: var(--primary); font-size: var(--font-base);">
                                <?= htmlspecialchars($div['nama_divisi']) ?>
                            </h4>
                            <span class="badge badge-secondary"><?= $div['total_member'] ?></span>
                        </div>
                        
                        <?php if (empty($membersByDiv[$div['id']])): ?>
                        <p style="color: var(--text-muted); font-size: var(--font-sm); font-style: italic;">Belum ada panitia di divisi ini.</p>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Nama</th>
                                        <th>Dept.</th>
                                        <th>Jabatan di Divisi</th>
                                        <th style="width: 50px;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($membersByDiv[$div['id']] as $m): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($m['nama_lengkap']) ?></strong></td>
                                        <td><span class="badge badge-dept"><?= htmlspecialchars($m['departemen']) ?></span></td>
                                        <td><?= htmlspecialchars($m['jabatan_panitia']) ?></td>
                                        <td>
                                            <form method="POST" action="<?= BASE_URL ?>/admin/process/event_process.php" style="display:inline;" onsubmit="return confirm('Keluarkan anggota ini dari divisi?');">
                                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                                <input type="hidden" name="action" value="remove_member">
                                                <input type="hidden" name="id" value="<?= $m['id'] ?>">
                                                <input type="hidden" name="event_id" value="<?= $eventId ?>">
                                                <button type="submit" class="btn btn-icon btn-sm text-danger" title="Hapus">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                    
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal Add/Edit Divisi -->
<div id="addDivModal" class="modal">
    <div class="modal-content" style="max-width: 400px;">
        <div class="modal-header">
            <h3 class="modal-title" id="divModalTitle">Tambah Divisi</h3>
            <span class="close" onclick="closeModal('addDivModal')">&times;</span>
        </div>
        <div class="modal-body">
            <form action="<?= BASE_URL ?>/admin/process/event_process.php" method="POST" id="divForm">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <input type="hidden" name="action" value="add_division" id="divFormAction">
                <input type="hidden" name="event_id" value="<?= $eventId ?>">
                <input type="hidden" name="id" value="" id="divId">
                
                <div class="form-group">
                    <label class="form-label">Nama Divisi <span class="required">*</span></label>
                    <input type="text" name="nama_divisi" id="namaDivisi" class="form-control" required placeholder="Contoh: Acara, Publikasi, dsb">
                </div>
                
                <div class="d-flex justify-end gap-3 mt-6">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('addDivModal')">Batal</button>
                    <button type="submit" class="btn btn-primary" id="btnDivSubmit">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Add Member -->
<div id="addMemberModal" class="modal">
    <div class="modal-content" style="max-width: 600px;">
        <div class="modal-header">
            <h3 class="modal-title">Tambah Panitia</h3>
            <span class="close" onclick="closeModal('addMemberModal')">&times;</span>
        </div>
        <div class="modal-body">
            <form action="<?= BASE_URL ?>/admin/process/event_process.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <input type="hidden" name="action" value="add_member">
                <input type="hidden" name="event_id" value="<?= $eventId ?>">
                
                <div class="form-group">
                    <label class="form-label">Pilih Divisi <span class="required">*</span></label>
                    <select name="division_id" class="form-control" required>
                        <option value="">— Pilih Divisi —</option>
                        <?php foreach ($divisions as $div): ?>
                            <option value="<?= $div['id'] ?>"><?= htmlspecialchars($div['nama_divisi']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Pilih Anggota <span class="required">*</span></label>
                    <!-- Simple implementation: multiple select. For better UX, consider Select2 later -->
                    <select name="user_ids[]" class="form-control" multiple required style="height: 200px;">
                        <?php 
                        $currentDept = '';
                        foreach ($allUsers as $u) {
                            if ($currentDept !== $u['departemen']) {
                                if ($currentDept !== '') echo '</optgroup>';
                                $currentDept = $u['departemen'];
                                echo '<optgroup label="' . htmlspecialchars($currentDept) . '">';
                            }
                            echo '<option value="' . $u['id'] . '">' . htmlspecialchars($u['nama_lengkap']) . '</option>';
                        }
                        if ($currentDept !== '') echo '</optgroup>';
                        ?>
                    </select>
                    <small style="color: var(--text-muted); display: block; margin-top: 4px;">Tahan tombol Ctrl (Windows) atau Command (Mac) untuk memilih lebih dari satu anggota.</small>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Jabatan (Opsional)</label>
                    <input type="text" name="jabatan_panitia" class="form-control" placeholder="Contoh: Koordinator, Anggota (Default)" value="Anggota">
                </div>
                
                <div class="d-flex justify-end gap-3 mt-6">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('addMemberModal')">Batal</button>
                    <button type="submit" class="btn btn-success">Tambah ke Divisi</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editDiv(divObj) {
    document.getElementById('divModalTitle').textContent = 'Edit Divisi';
    document.getElementById('divFormAction').value = 'edit_division';
    document.getElementById('divId').value = divObj.id;
    document.getElementById('namaDivisi').value = divObj.nama_divisi;
    document.getElementById('btnDivSubmit').textContent = 'Update Divisi';
    
    openModal('addDivModal');
}

// Reset modal on close
document.getElementById('addDivModal').addEventListener('click', function(e) {
    if (e.target.classList.contains('close') || e.target.id === 'addDivModal') {
        setTimeout(() => {
            document.getElementById('divModalTitle').textContent = 'Tambah Divisi';
            document.getElementById('divFormAction').value = 'add_division';
            document.getElementById('divId').value = '';
            document.getElementById('divForm').reset();
            document.getElementById('btnDivSubmit').textContent = 'Simpan';
        }, 300);
    }
});

// Fix media query for layout
document.head.insertAdjacentHTML('beforeend', `<style>
    @media (max-width: 992px) {
        div[style*="grid-template-columns: 1fr 2fr"] {
            grid-template-columns: 1fr !important;
        }
    }
</style>`);
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
