<?php
/**
 * Admin: Edit Member (tanpa password)
 */

require_once __DIR__ . '/../includes/auth_check.php';

$pageTitle = 'Edit Anggota';
$pageIcon = 'fas fa-user-edit';
$db = getDB();
$departemenList = json_decode(DEPARTEMEN_LIST, true);

$userId = intval($_GET['id'] ?? 0);
if (!$userId) { redirect(BASE_URL . '/admin/members.php'); }

$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$member = $stmt->fetch();

if (!$member) {
    setFlash('danger', 'Anggota tidak ditemukan.');
    redirect(BASE_URL . '/admin/members.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRF($_POST['csrf_token'] ?? '')) {
        setFlash('danger', 'Token tidak valid.');
        redirect(BASE_URL . '/admin/edit_member.php?id=' . $userId);
    }
    
    $nama = sanitize($_POST['nama_lengkap']);
    $email = sanitize($_POST['email']) ?: null;
    $departemen = sanitize($_POST['departemen']);
    $jabatan = sanitize($_POST['jabatan']);
    $isBph = isset($_POST['is_bph']) ? 1 : 0;
    
    if ($departemen === 'Inti' || $jabatan === 'Kepala Departemen') {
        $isBph = 1;
    }
    
    try {
        $stmt = $db->prepare("UPDATE users SET nama_lengkap=?, email=?, departemen=?, jabatan=?, is_bph=?, updated_at=NOW() WHERE id=?");
        $stmt->execute([$nama, $email, $departemen, $jabatan, $isBph, $userId]);
        
        setFlash('success', 'Data anggota berhasil diperbarui.');
        redirect(BASE_URL . '/admin/members.php');
    } catch (PDOException $e) {
        setFlash('danger', 'Error: ' . $e->getMessage());
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div style="max-width: 600px; margin: 0 auto;">
    <div class="card">
        <h3 class="card-title mb-6"><i class="fas fa-user-edit"></i> Edit Anggota</h3>
        
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            
            <div class="form-group">
                <label class="form-label">Nama Lengkap <span class="required">*</span></label>
                <input type="text" name="nama_lengkap" class="form-control" required value="<?= htmlspecialchars($member['nama_lengkap']) ?>">
            </div>
            
            <div class="form-group">
                <label class="form-label">Email <small style="color: var(--text-muted);">(opsional)</small></label>
                <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($member['email'] ?? '') ?>">
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-4);">
                <div class="form-group">
                    <label class="form-label">Departemen <span class="required">*</span></label>
                    <select name="departemen" class="form-control" required>
                        <?php foreach ($departemenList as $key => $label): ?>
                            <option value="<?= $key ?>" <?= $member['departemen'] === $key ? 'selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Jabatan <span class="required">*</span></label>
                    <input type="text" name="jabatan" class="form-control" required value="<?= htmlspecialchars($member['jabatan']) ?>">
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">QR Code Anggota</label>
                <div style="display: flex; gap: var(--space-2); align-items: center;">
                    <input type="text" class="form-control" value="<?= htmlspecialchars($member['qr_code']) ?>" readonly style="opacity: 0.8; font-family: monospace;">
                    <a href="<?= BASE_URL ?>/admin/download_qr.php?id=<?= $member['id'] ?>" class="btn btn-secondary" download title="Download QR Code PNG">
                        <i class="fas fa-download"></i> Download QR
                    </a>
                </div>
            </div>
            
            <div style="margin-bottom: var(--space-6);">
                <label class="form-check">
                    <input type="checkbox" name="is_bph" value="1" <?= $member['is_bph'] ? 'checked' : '' ?>>
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
