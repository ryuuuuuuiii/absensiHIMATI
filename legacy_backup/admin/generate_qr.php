<?php
/**
 * Admin: Generate QR Codes
 * Generates QR codes for all members using online API
 */

require_once __DIR__ . '/../includes/auth_check.php';

if (!isAdmin()) { redirect(BASE_URL . '/dashboard/index.php'); }

$pageTitle = 'Generate QR Code';
$pageIcon = 'fas fa-qrcode';
$db = getDB();

$message = '';
$generated = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && validateCSRF($_POST['csrf_token'] ?? '')) {
    $stmt = $db->query("SELECT id, nama_lengkap, departemen, jabatan, qr_code FROM users ORDER BY departemen, nama_lengkap");
    $users = $stmt->fetchAll();
    
    foreach ($users as $user) {
        $res = ensureMemberQrImage($user);
        if ($res) {
            $generated++;
        }
    }
    
    $message = "Berhasil generate $generated QR Code dari " . count($users) . " anggota.";
    setFlash('success', $message);
    redirect(BASE_URL . '/admin/generate_qr.php');
}

// Get all members with QR status
$stmt = $db->query("SELECT id, nama_lengkap, departemen, jabatan, qr_code, qr_image FROM users ORDER BY departemen, nama_lengkap");
$members = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div style="max-width: 800px; margin: 0 auto;">
    <div class="card mb-6">
        <h3 class="card-title mb-4"><i class="fas fa-qrcode"></i> Generate & Download QR Code Anggota</h3>
        
        <div class="alert alert-info mb-6">
            <i class="fas fa-info-circle"></i>
            <div>
                <strong>Informasi:</strong><br>
                <small>Kamu bisa generate ulang QR Code untuk semua anggota atau langsung men-download seluruh file QR Code anggota dalam format file ZIP.</small>
            </div>
        </div>
        
        <div style="display: flex; gap: var(--space-4); flex-wrap: wrap; align-items: center;">
            <form method="POST" style="margin: 0;">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <button type="submit" class="btn btn-primary" onclick="this.innerHTML='<span class=spinner></span> Generating...'; this.disabled=true; this.form.submit();">
                    <i class="fas fa-magic"></i> Generate Semua QR Code
                </button>
            </form>
            
            <a href="<?= BASE_URL ?>/admin/download_qr.php?action=zip" class="btn btn-success">
                <i class="fas fa-file-archive"></i> Download Semua QR (.ZIP)
            </a>
            
            <a href="<?= BASE_URL ?>/admin/members.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Kembali ke Anggota
            </a>
        </div>
    </div>
    
    <!-- QR Status List -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-list-check"></i> Status & File QR Code</h3>
            <span class="badge badge-primary"><?= count($members) ?> anggota</span>
        </div>
        
        <div class="table-wrapper">
            <table class="table">
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Departemen</th>
                        <th>QR Code</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($members as $m): ?>
                    <?php $hasFile = ($m['qr_image'] && file_exists(QR_PATH . $m['qr_image'])); ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($m['nama_lengkap']) ?></strong></td>
                        <td><span class="badge badge-dept"><?= $m['departemen'] ?></span></td>
                        <td style="font-size: var(--font-xs); font-family: monospace;"><?= $m['qr_code'] ?></td>
                        <td>
                            <?php if ($hasFile): ?>
                                <span class="badge badge-success"><i class="fas fa-check"></i> Siap</span>
                            <?php else: ?>
                                <span class="badge badge-warning"><i class="fas fa-clock"></i> Belum dibuat</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="<?= BASE_URL ?>/admin/download_qr.php?id=<?= $m['id'] ?>" class="btn btn-sm btn-outline-primary" download title="Download file QR">
                                <i class="fas fa-download"></i> Download
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

