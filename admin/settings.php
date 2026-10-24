<?php
/**
 * Admin: Settings
 */

require_once __DIR__ . '/../includes/auth_check.php';

if (!isAdmin()) { redirect(BASE_URL . '/dashboard/index.php'); }

$pageTitle = 'Pengaturan';
$pageIcon = 'fas fa-cogs';
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRF($_POST['csrf_token'] ?? '')) {
        setFlash('danger', 'Token tidak valid.');
        redirect(BASE_URL . '/admin/settings.php');
    }
    
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_google') {
        updateSetting('google_script_url', sanitize($_POST['google_script_url']));
        updateSetting('google_drive_folder_id', sanitize($_POST['google_drive_folder_id']));
        setFlash('success', 'Konfigurasi Google berhasil diperbarui.');
    }
    
    if ($action === 'update_general') {
        updateSetting('online_link_expiry_hours', intval($_POST['online_link_expiry_hours']));
        setFlash('success', 'Pengaturan umum berhasil diperbarui.');
    }
    
    if ($action === 'upload_logo') {
        $logoSlot = sanitize($_POST['logo_slot'] ?? '');
        if (in_array($logoSlot, ['logo_1', 'logo_2', 'logo_3']) && isset($_FILES['logo_file'])) {
            $uploadDir = ROOT_PATH . 'uploads' . DIRECTORY_SEPARATOR . 'logos' . DIRECTORY_SEPARATOR;
            $result = uploadFile($_FILES['logo_file'], $uploadDir);
            if ($result['success']) {
                updateSetting($logoSlot, $result['filename']);
                setFlash('success', 'Logo berhasil diupload.');
            } else {
                setFlash('danger', $result['message']);
            }
        }
    }
    
    redirect(BASE_URL . '/admin/settings.php');
}

// Get current settings
$googleScriptUrl = getSetting('google_script_url');
$googleDriveFolderId = getSetting('google_drive_folder_id');
$expiryHours = getSetting('online_link_expiry_hours', '3');
$logo1 = getSetting('logo_1');
$logo2 = getSetting('logo_2');
$logo3 = getSetting('logo_3');

include __DIR__ . '/../includes/header.php';
?>

<div style="max-width: 700px; margin: 0 auto;">
    <!-- Logo Upload -->
    <div class="card mb-6">
        <h3 class="card-title mb-6"><i class="fas fa-image"></i> Upload Logo</h3>
        
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: var(--space-4);">
            <?php for ($slot = 1; $slot <= 3; $slot++): ?>
            <?php $currentLogo = ${'logo' . $slot}; ?>
            <div style="text-align: center;">
                <div style="width: 100%; aspect-ratio: 1; border-radius: var(--radius-md); border: 2px dashed var(--dark-border-light); display: flex; align-items: center; justify-content: center; margin-bottom: var(--space-3); overflow: hidden; background: var(--dark-surface-2);">
                    <?php if ($currentLogo): ?>
                        <img src="<?= BASE_URL ?>/uploads/logos/<?= $currentLogo ?>" alt="Logo <?= $slot ?>" style="width: 100%; height: 100%; object-fit: contain; padding: 8px;">
                    <?php else: ?>
                        <i class="fas fa-image" style="font-size: 2rem; color: var(--text-muted);"></i>
                    <?php endif; ?>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" name="action" value="upload_logo">
                    <input type="hidden" name="logo_slot" value="logo_<?= $slot ?>">
                    <label class="btn btn-sm btn-secondary btn-block" style="cursor: pointer;">
                        <i class="fas fa-upload"></i> Logo <?= $slot ?>
                        <input type="file" name="logo_file" accept="image/*" style="display: none;" onchange="this.form.submit()">
                    </label>
                </form>
            </div>
            <?php endfor; ?>
        </div>
    </div>
    
    <!-- Google Integration -->
    <div class="card mb-6">
        <h3 class="card-title mb-6"><i class="fab fa-google"></i> Google Integration</h3>
        
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="action" value="update_google">
            
            <div class="form-group">
                <label class="form-label">Google Apps Script Web App URL</label>
                <input type="url" name="google_script_url" class="form-control" value="<?= htmlspecialchars($googleScriptUrl) ?>" placeholder="https://script.google.com/macros/s/.../exec">
                <span class="form-text">Deploy Apps Script sebagai Web App, paste URL di sini</span>
            </div>
            
            <div class="form-group">
                <label class="form-label">Google Drive Folder ID</label>
                <input type="text" name="google_drive_folder_id" class="form-control" value="<?= htmlspecialchars($googleDriveFolderId) ?>" placeholder="Contoh: 1ABC...xyz">
                <span class="form-text">ID folder dari URL Google Drive untuk simpan foto</span>
            </div>
            
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan</button>
        </form>
    </div>
    
    <!-- General Settings -->
    <div class="card">
        <h3 class="card-title mb-6"><i class="fas fa-sliders-h"></i> Pengaturan Umum</h3>
        
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="action" value="update_general">
            
            <div class="form-group">
                <label class="form-label">Durasi Link Absensi Online (jam)</label>
                <input type="number" name="online_link_expiry_hours" class="form-control" value="<?= $expiryHours ?>" min="1" max="24" style="max-width: 150px;">
                <span class="form-text">Berapa jam link absensi online aktif setelah waktu mulai rapat</span>
            </div>
            
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan</button>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
