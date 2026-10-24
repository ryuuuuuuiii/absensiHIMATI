<?php
/**
 * Absent Form (Public Page)
 * For participants who can't attend — submit reason + proof
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

$token = sanitize($_GET['token'] ?? '');

if (empty($token)) {
    die('Link tidak valid.');
}

$db = getDB();

// Get meeting by absent token
$stmt = $db->prepare("SELECT m.*, u.nama_lengkap as creator_name FROM meetings m JOIN users u ON m.created_by = u.id WHERE m.absent_link = ?");
$stmt->execute([$token]);
$meeting = $stmt->fetch();

if (!$meeting) {
    die('Link tidak valid atau rapat tidak ditemukan.');
}

// Get participants who haven't scanned
$stmt = $db->prepare("
    SELECT a.*, u.nama_lengkap, u.departemen 
    FROM attendances a 
    JOIN users u ON a.user_id = u.id 
    WHERE a.meeting_id = ? AND a.status = 'alpha'
    ORDER BY u.nama_lengkap
");
$stmt->execute([$meeting['id']]);
$absentParticipants = $stmt->fetchAll();

$successMessage = '';
$errorMessage = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = intval($_POST['user_id'] ?? 0);
    $status = sanitize($_POST['status'] ?? '');
    $keterangan = sanitize($_POST['keterangan'] ?? '');
    
    if (!$userId || !in_array($status, ['izin', 'sakit']) || empty($keterangan)) {
        $errorMessage = 'Semua field harus diisi.';
    } else {
        try {
            $buktiFilename = null;
            
            // Handle photo upload
            if (isset($_FILES['bukti_foto']) && $_FILES['bukti_foto']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = ROOT_PATH . 'uploads' . DIRECTORY_SEPARATOR . 'bukti' . DIRECTORY_SEPARATOR;
                $result = uploadFile($_FILES['bukti_foto'], $uploadDir);
                if ($result['success']) {
                    $buktiFilename = $result['filename'];
                }
            }
            
            // Update attendance
            $stmt = $db->prepare("
                UPDATE attendances 
                SET status = ?, keterangan = ?, bukti_foto = ?, scanned_at = NOW(), scan_method = 'absent_form'
                WHERE meeting_id = ? AND user_id = ?
            ");
            $stmt->execute([$status, $keterangan, $buktiFilename, $meeting['id'], $userId]);
            
            $successMessage = 'Keterangan ketidakhadiran berhasil dikirim. Terima kasih.';
            
            // Refresh
            $stmt = $db->prepare("SELECT a.*, u.nama_lengkap, u.departemen FROM attendances a JOIN users u ON a.user_id = u.id WHERE a.meeting_id = ? AND a.status = 'alpha' ORDER BY u.nama_lengkap");
            $stmt->execute([$meeting['id']]);
            $absentParticipants = $stmt->fetchAll();
            
        } catch (Exception $e) {
            $errorMessage = 'Terjadi kesalahan. Silakan coba lagi.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Ketidakhadiran — <?= APP_NAME ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
    <div class="bg-animated"></div>
    
    <div class="public-page">
        <div class="public-container">
            <div class="public-card">
                <div class="public-card-header">
                    <h2><i class="fas fa-user-slash" style="color: var(--danger);"></i> Form Ketidakhadiran</h2>
                    <p><?= getJenisRapatLabel($meeting['jenis_rapat']) ?> — <?= formatTanggal($meeting['tanggal'], 'short') ?></p>
                </div>
                
                <?php if ($successMessage): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <span><?= $successMessage ?></span>
                    </div>
                <?php endif; ?>
                
                <?php if ($errorMessage): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i>
                        <span><?= $errorMessage ?></span>
                    </div>
                <?php endif; ?>
                
                <?php if (empty($absentParticipants) && !$successMessage): ?>
                    <div class="empty-state">
                        <i class="fas fa-check-circle" style="color: var(--success);"></i>
                        <h3>Semua Sudah Absen</h3>
                        <p>Semua peserta sudah tercatat kehadirannya.</p>
                    </div>
                <?php elseif (!$successMessage): ?>
                    <form method="POST" enctype="multipart/form-data">
                        <div class="form-group">
                            <label class="form-label">Pilih Nama Anda <span class="required">*</span></label>
                            <select name="user_id" class="form-control" required>
                                <option value="">— Pilih Nama —</option>
                                <?php foreach ($absentParticipants as $p): ?>
                                    <option value="<?= $p['user_id'] ?>">
                                        <?= htmlspecialchars($p['nama_lengkap']) ?> (<?= $p['departemen'] ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Status <span class="required">*</span></label>
                            <div style="display: flex; gap: var(--space-4);">
                                <label class="form-check">
                                    <input type="radio" name="status" value="izin" required>
                                    <span class="form-check-label"><i class="fas fa-info-circle text-warning"></i> Izin</span>
                                </label>
                                <label class="form-check">
                                    <input type="radio" name="status" value="sakit">
                                    <span class="form-check-label"><i class="fas fa-medkit" style="color: var(--info);"></i> Sakit</span>
                                </label>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Keterangan <span class="required">*</span></label>
                            <textarea name="keterangan" class="form-control" required 
                                      placeholder="Jelaskan alasan kenapa Anda tidak bisa hadir..." rows="4"></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Upload Bukti Foto <span class="required">*</span></label>
                            <div class="file-upload">
                                <input type="file" name="bukti_foto" accept="image/*" id="buktiInput" required>
                                <i class="fas fa-cloud-upload-alt"></i>
                                <p>Klik untuk upload bukti</p>
                                <small style="color: var(--text-muted);">Maks 5MB (JPG, PNG)</small>
                            </div>
                            <div class="file-preview" id="buktiPreview"></div>
                        </div>
                        
                        <button type="submit" class="btn btn-danger btn-lg btn-block mt-4">
                            <i class="fas fa-paper-plane"></i> Kirim Keterangan
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
    document.getElementById('buktiInput')?.addEventListener('change', function() {
        const preview = document.getElementById('buktiPreview');
        preview.innerHTML = '';
        if (this.files[0]) {
            const reader = new FileReader();
            reader.onload = (e) => {
                const img = document.createElement('img');
                img.src = e.target.result;
                preview.appendChild(img);
            };
            reader.readAsDataURL(this.files[0]);
        }
    });
    </script>
</body>
</html>
