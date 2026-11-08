<?php
/**
 * Admin: Manage Members
 * CRUD operations for HIMA TI members
 */

require_once __DIR__.'/../includes/auth_check.php';

if (! isAdmin()) {
    setFlash('danger', 'Akses ditolak.');
    redirect(BASE_URL.'/dashboard/index.php');
}

$pageTitle = 'Kelola Anggota';
$pageIcon = 'fas fa-users-cog';
$db = getDB();

// Handle delete
if (isset($_GET['delete']) && isset($_GET['csrf'])) {
    if (validateCSRF($_GET['csrf'])) {
        $deleteId = intval($_GET['delete']);
        $stmt = $db->prepare('DELETE FROM users WHERE id = ?');
        $stmt->execute([$deleteId]);
        setFlash('success', 'Anggota berhasil dihapus.');
    }
    redirect(BASE_URL.'/admin/members.php');
}

// Filters
$filterDept = sanitize($_GET['dept'] ?? '');
$search = sanitize($_GET['q'] ?? '');

$where = '1=1';
$params = [];

if ($filterDept) {
    $where .= ' AND departemen = ?';
    $params[] = $filterDept;
}
if ($search) {
    $where .= ' AND (nama_lengkap LIKE ? OR email LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$stmt = $db->prepare("SELECT * FROM users WHERE $where ORDER BY departemen, nama_lengkap");
$stmt->execute($params);
$members = $stmt->fetchAll();

$departemenList = json_decode(DEPARTEMEN_LIST, true);

include __DIR__.'/../includes/header.php';
?>

<!-- Filter Bar -->
<div class="filter-bar">
    <form method="GET" class="search-input-wrapper">
        <i class="fas fa-search"></i>
        <input type="text" name="q" class="form-control" placeholder="Cari nama atau email..." value="<?= htmlspecialchars($search) ?>">
        <?php if ($filterDept) { ?><input type="hidden" name="dept" value="<?= $filterDept ?>"><?php } ?>
    </form>
    
    <select class="form-control" style="max-width: 200px;" onchange="location.href='?dept='+this.value+'<?= $search ? '&q='.urlencode($search) : '' ?>'">
        <option value="">Semua Departemen</option>
        <?php foreach ($departemenList as $key => $label) { ?>
            <option value="<?= $key ?>" <?= $filterDept === $key ? 'selected' : '' ?>><?= $label ?></option>
        <?php } ?>
    </select>
    
    <a href="<?= BASE_URL ?>/admin/add_member.php" class="btn btn-primary">
        <i class="fas fa-user-plus"></i> Tambah Anggota
    </a>
    
    <a href="<?= BASE_URL ?>/admin/generate_qr.php" class="btn btn-secondary">
        <i class="fas fa-qrcode"></i> Generate QR
    </a>

    <a href="<?= BASE_URL ?>/admin/download_qr.php?action=zip" class="btn btn-success" title="Download Semua QR Code Anggota dalam format ZIP">
        <i class="fas fa-file-archive"></i> Download Semua QR (ZIP)
    </a>
</div>

<!-- Members Table -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-users"></i> Daftar Anggota</h3>
        <span class="badge badge-primary"><?= count($members) ?> anggota</span>
    </div>
    
    <div class="table-wrapper">
        <table class="table">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama</th>
                    <th>Email</th>
                    <th>Departemen</th>
                    <th>Jabatan</th>
                    <th>BPH</th>
                    <th>QR Code</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($members as $i => $m) { ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td><strong><?= htmlspecialchars($m['nama_lengkap']) ?></strong></td>
                    <td style="font-size: var(--font-xs);"><?= htmlspecialchars($m['email']) ?></td>
                    <td><span class="badge badge-dept"><?= $m['departemen'] ?></span></td>
                    <td><?= htmlspecialchars($m['jabatan']) ?></td>
                    <td><?= $m['is_bph'] ? '<i class="fas fa-check text-success"></i>' : '-' ?></td>
                    <td>
                        <?php if ($m['qr_code']) { ?>
                            <button type="button" class="btn btn-xs btn-outline-primary" style="padding: 4px 10px; font-size: var(--font-xs); border-radius: var(--radius-sm); display: inline-flex; align-items: center; gap: 5px;"
                                    onclick='openQrModal(<?= json_encode([
                                        'id' => $m['id'],
                                        'nama' => $m['nama_lengkap'],
                                        'email' => $m['email'] ?? '',
                                        'departemen' => $m['departemen'],
                                        'jabatan' => $m['jabatan'],
                                        'qr_code' => $m['qr_code'],
                                    ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'>
                                <i class="fas fa-qrcode"></i> Lihat QR
                            </button>
                        <?php } else { ?>
                            <span class="badge badge-danger"><i class="fas fa-times"></i> Belum ada</span>
                        <?php } ?>
                    </td>
                    <td>
                        <div class="btn-group">
                            <a href="<?= BASE_URL ?>/admin/download_qr.php?id=<?= $m['id'] ?>" class="btn btn-ghost btn-sm" title="Download QR Code PNG" download>
                                <i class="fas fa-download" style="color: var(--primary);"></i>
                            </a>
                            <a href="<?= BASE_URL ?>/admin/edit_member.php?id=<?= $m['id'] ?>" class="btn btn-ghost btn-sm" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <button class="btn btn-ghost btn-sm" title="Hapus"
                                    onclick="confirmDelete('Hapus <?= addslashes($m['nama_lengkap']) ?>?', '<?= BASE_URL ?>/admin/members.php?delete=<?= $m['id'] ?>&csrf=<?= $_SESSION['csrf_token'] ?>')">
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

<!-- Modal QR Code Anggota -->
<div id="qrModal" class="modal-overlay">
    <div class="modal-dialog">
        <div class="modal-header">
            <h4 class="modal-title"><i class="fas fa-qrcode text-primary"></i> QR Code Anggota</h4>
            <button type="button" class="modal-close" onclick="closeQrModal()">&times;</button>
        </div>
        <div class="modal-body">
            <div class="qr-card-preview" id="qrCardPrintArea">
                <div class="qr-card-title">HIMA TI POLITEKNIK HASNUR</div>
                <div class="qr-card-subtitle">KARTU ABSENSI RESMI</div>
                
                <div class="qr-card-canvas-wrap">
                    <div id="qrcodeCanvas"></div>
                </div>
                
                <div class="qr-card-name" id="modalUserName">-</div>
                <div class="qr-card-meta">
                    <span class="badge badge-dept" id="modalUserDept">-</span>
                    <span id="modalUserRole">-</span>
                </div>
                <div class="qr-card-code" id="modalUserCode">-</div>
            </div>
        </div>
        <div class="modal-footer">
            <a id="modalDownloadBtn" href="#" class="btn btn-primary" download>
                <i class="fas fa-download"></i> Download PNG
            </a>
            <button type="button" class="btn btn-secondary" onclick="printQrCard()">
                <i class="fas fa-print"></i> Cetak
            </button>
            <button type="button" class="btn btn-ghost" onclick="closeQrModal()">Tutup</button>
        </div>
    </div>
</div>

<script src="<?= BASE_URL ?>/assets/js/qrcode.min.js"></script>
<script>
let currentQrCodeInstance = null;

function openQrModal(user) {
    document.getElementById('modalUserName').textContent = user.nama;
    document.getElementById('modalUserDept').textContent = user.departemen;
    document.getElementById('modalUserRole').textContent = user.jabatan;
    document.getElementById('modalUserCode').textContent = user.qr_code;
    
    // Set direct download link dari server
    const downloadUrl = '<?= BASE_URL ?>/admin/download_qr.php?id=' + user.id;
    const downloadBtn = document.getElementById('modalDownloadBtn');
    downloadBtn.href = downloadUrl;
    downloadBtn.setAttribute('download', 'QR_' + user.departemen + '_' + user.nama.replace(/\s+/g, '_') + '.png');
    
    // Generate QR Code di browser via qrcode.min.js
    const qrContainer = document.getElementById('qrcodeCanvas');
    qrContainer.innerHTML = '';
    
    const qrPayload = JSON.stringify({
        id: parseInt(user.id),
        qr_code: user.qr_code,
        nama: user.nama,
        departemen: user.departemen,
        jabatan: user.jabatan
    });
    
    currentQrCodeInstance = new QRCode(qrContainer, {
        text: qrPayload,
        width: 180,
        height: 180,
        colorDark: "#1A1A2E",
        colorLight: "#FFFFFF",
        correctLevel: QRCode.CorrectLevel.H
    });
    
    // Tampilkan modal
    const modal = document.getElementById('qrModal');
    modal.classList.add('active');
}

function closeQrModal() {
    const modal = document.getElementById('qrModal');
    modal.classList.remove('active');
}

// Tutup modal jika klik di luar box
document.getElementById('qrModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeQrModal();
    }
});

// Escape key to close modal
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeQrModal();
    }
});

// Fungsi cetak kartu
function printQrCard() {
    const printContent = document.getElementById('qrCardPrintArea').innerHTML;
    const printWindow = window.open('', '_blank', 'width=600,height=700');
    printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Cetak Kartu QR - ${document.getElementById('modalUserName').textContent}</title>
            <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
            <style>
                body {
                    font-family: 'Poppins', sans-serif;
                    background: #f0f0f0;
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    min-height: 100vh;
                    margin: 0;
                    padding: 20px;
                }
                .card {
                    background: white;
                    border: 2px solid #F5921B;
                    border-radius: 16px;
                    padding: 30px 24px;
                    text-align: center;
                    width: 320px;
                    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
                }
                .qr-card-title {
                    font-size: 13px;
                    font-weight: 700;
                    color: #F5921B;
                    letter-spacing: 1.5px;
                    margin-bottom: 2px;
                }
                .qr-card-subtitle {
                    font-size: 10px;
                    color: #888;
                    margin-bottom: 16px;
                }
                .qr-card-canvas-wrap {
                    display: inline-block;
                    padding: 10px;
                    border: 1px solid #eee;
                    border-radius: 12px;
                    margin-bottom: 16px;
                }
                .qr-card-canvas-wrap img, .qr-card-canvas-wrap canvas {
                    display: block;
                    margin: 0 auto;
                }
                .qr-card-name {
                    font-size: 18px;
                    font-weight: 700;
                    color: #1A1A2E;
                    margin-bottom: 4px;
                }
                .qr-card-meta {
                    font-size: 12px;
                    color: #555;
                    margin-bottom: 8px;
                }
                .badge-dept {
                    background: #f5921b;
                    color: white;
                    padding: 2px 8px;
                    border-radius: 10px;
                    font-weight: 600;
                }
                .qr-card-code {
                    font-family: monospace;
                    font-size: 11px;
                    color: #777;
                    background: #f5f5f5;
                    padding: 4px 8px;
                    border-radius: 6px;
                    display: inline-block;
                }
                @media print {
                    body { background: white; padding: 0; }
                    .card { box-shadow: none; border: 2px solid #000; }
                }
            </style>
        </head>
        <body>
            <div class="card">
                ${printContent}
            </div>
            <script>
                window.onload = function() {
                    window.print();
                    setTimeout(function() { window.close(); }, 500);
                };
            <\/script>
        </body>
        </html>
    `);
    printWindow.document.close();
}
</script>

<?php include __DIR__.'/../includes/footer.php'; ?>

