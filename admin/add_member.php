<?php
/**
 * Admin: Add Member (tanpa password)
 */

require_once __DIR__ . '/../includes/auth_check.php';

$pageTitle = 'Tambah Anggota';
$pageIcon = 'fas fa-user-plus';
$db = getDB();
$departemenList = json_decode(DEPARTEMEN_LIST, true);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRF($_POST['csrf_token'] ?? '')) {
        setFlash('danger', 'Token tidak valid.');
        redirect(BASE_URL . '/admin/add_member.php');
    }
    
    $nama = sanitize($_POST['nama_lengkap']);
    $email = sanitize($_POST['email']) ?: null;
    $departemen = sanitize($_POST['departemen']);
    $jabatan = sanitize($_POST['jabatan']);
    $isBph = isset($_POST['is_bph']) ? 1 : 0;
    
    if ($departemen === 'Inti' || $jabatan === 'Kepala Departemen') {
        $isBph = 1;
    }
    
    // Generate QR code
    $qrCode = 'HIMATI-USR-' . str_pad(rand(100, 999), 3, '0', STR_PAD_LEFT) . '-' . strtoupper(substr(md5($nama . time()), 0, 6));
    
    try {
        $stmt = $db->prepare("INSERT INTO users (nama_lengkap, email, departemen, jabatan, qr_code, is_bph) VALUES (?,?,?,?,?,?)");
        $stmt->execute([$nama, $email, $departemen, $jabatan, $qrCode, $isBph]);
        $newId = $db->lastInsertId();
        
        // Otomatis buat file QR code
        ensureMemberQrImage([
            'id' => $newId,
            'nama_lengkap' => $nama,
            'departemen' => $departemen,
            'jabatan' => $jabatan,
            'qr_code' => $qrCode
        ]);
        
        setFlash('success', 'Anggota "' . $nama . '" berhasil ditambahkan beserta QR Code-nya.');
        redirect(BASE_URL . '/admin/members.php');
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            setFlash('danger', 'QR Code duplikat, coba lagi.');
        } else {
            setFlash('danger', 'Error: ' . $e->getMessage());
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div style="max-width: 600px; margin: 0 auto;">
    <div class="card">
        <h3 class="card-title mb-6"><i class="fas fa-user-plus"></i> Tambah Anggota Baru</h3>
        
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            
            <div class="form-group">
                <label class="form-label">Nama Lengkap <span class="required">*</span></label>
                <input type="text" name="nama_lengkap" class="form-control" required placeholder="Nama lengkap anggota">
            </div>
            
            <div class="form-group">
                <label class="form-label">Email <small style="color: var(--text-muted);">(opsional)</small></label>
                <input type="email" name="email" class="form-control" placeholder="email@himati.id">
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-4);">
                <div class="form-group">
                    <label class="form-label">Departemen <span class="required">*</span></label>
                    <select name="departemen" class="form-control" required>
                        <option value="">— Pilih —</option>
                        <?php foreach ($departemenList as $key => $label): ?>
                            <option value="<?= $key ?>"><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Jabatan <span class="required">*</span></label>
                    <input type="text" name="jabatan" class="form-control" required placeholder="Contoh: Anggota">
                </div>
            </div>
            
            <div style="margin-bottom: var(--space-6);">
                <label class="form-check">
                    <input type="checkbox" name="is_bph" value="1">
                    <span class="form-check-label">BPH (Badan Pengurus Harian)</span>
                </label>
            </div>
            
            <div class="btn-group">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan</button>
                <a href="<?= BASE_URL ?>/admin/members.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Kembali</a>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
