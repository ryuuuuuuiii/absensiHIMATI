<?php
/**
 * Online Attendance Form (Public Page)
 * Accessed via unique link shared by meeting creator
 */

require_once __DIR__.'/../config/config.php';
require_once __DIR__.'/../includes/functions.php';

$token = sanitize($_GET['token'] ?? '');

if (empty($token)) {
    exit('Link tidak valid.');
}

$db = getDB();

// Get meeting by token
$stmt = $db->prepare('SELECT m.*, u.nama_lengkap as creator_name FROM meetings m JOIN users u ON m.created_by = u.id WHERE m.unique_link = ?');
$stmt->execute([$token]);
$meeting = $stmt->fetch();

if (! $meeting) {
    exit('Rapat tidak ditemukan.');
}

// Check expiry
$meetingStart = strtotime($meeting['tanggal'].' '.$meeting['waktu_mulai']);
$expiryHours = intval(getSetting('online_link_expiry_hours', ONLINE_LINK_EXPIRY));
$expiryTime = $meetingStart + ($expiryHours * 3600);

$isExpired = time() > $expiryTime && $meeting['status'] === 'completed';

// Get participants (not yet marked hadir)
if ($meeting['jenis_rapat'] === 'kepanitiaan') {
    $stmt = $db->prepare('
        SELECT u.id as user_id, u.nama_lengkap, u.departemen, u.jabatan, a.status, u.is_bph
        FROM users u 
        LEFT JOIN attendances a ON u.id = a.user_id AND a.meeting_id = ?
        WHERE (a.id IS NOT NULL OR u.is_bph = 1)
        ORDER BY u.nama_lengkap
    ');
} else {
    $stmt = $db->prepare('
        SELECT a.user_id, u.nama_lengkap, u.departemen, u.jabatan, a.status, u.is_bph
        FROM attendances a 
        JOIN users u ON a.user_id = u.id 
        WHERE a.meeting_id = ? 
        ORDER BY u.nama_lengkap
    ');
}
$stmt->execute([$meeting['id']]);
$participants = $stmt->fetchAll();

$successMessage = '';
$errorMessage = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = intval($_POST['user_id'] ?? 0);
    $status = sanitize($_POST['status'] ?? '');
    $keterangan = sanitize($_POST['keterangan'] ?? '');

    if (! $userId || ! in_array($status, ['hadir', 'izin', 'sakit'])) {
        $errorMessage = 'Data tidak valid. Silakan coba lagi.';
    } else {
        try {
            // Update attendance
            $stmt = $db->prepare("
                UPDATE attendances 
                SET status = ?, keterangan = ?, scanned_at = NOW(), scan_method = 'online_link'
                WHERE meeting_id = ? AND user_id = ?
            ");
            $stmt->execute([$status, $keterangan, $meeting['id'], $userId]);

            // Check if BPH inserting new record
            if ($stmt->rowCount() === 0 && $meeting['jenis_rapat'] === 'kepanitiaan') {
                $bphCheck = $db->prepare('SELECT is_bph FROM users WHERE id = ?');
                $bphCheck->execute([$userId]);
                if ($bphCheck->fetchColumn()) {
                    $iStmt = $db->prepare("INSERT INTO attendances (meeting_id, user_id, status, keterangan, scanned_at, scan_method) VALUES (?, ?, ?, ?, NOW(), 'online_link')");
                    $iStmt->execute([$meeting['id'], $userId, $status, $keterangan]);
                }
            }

            // Handle photo upload
            if (isset($_FILES['bukti_foto']) && $_FILES['bukti_foto']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = ROOT_PATH.'uploads'.DIRECTORY_SEPARATOR.'bukti'.DIRECTORY_SEPARATOR;
                $result = uploadFile($_FILES['bukti_foto'], $uploadDir);
                if ($result['success']) {
                    $stmt = $db->prepare('UPDATE attendances SET bukti_foto = ? WHERE meeting_id = ? AND user_id = ?');
                    $stmt->execute([$result['filename'], $meeting['id'], $userId]);
                }
            }

            $successMessage = 'Absensi berhasil dicatat! Terima kasih.';

            // Refresh participants
            if ($meeting['jenis_rapat'] === 'kepanitiaan') {
                $stmt = $db->prepare('
                    SELECT u.id as user_id, u.nama_lengkap, u.departemen, u.jabatan, a.status, u.is_bph
                    FROM users u 
                    LEFT JOIN attendances a ON u.id = a.user_id AND a.meeting_id = ?
                    WHERE (a.id IS NOT NULL OR u.is_bph = 1)
                    ORDER BY u.nama_lengkap
                ');
            } else {
                $stmt = $db->prepare('
                    SELECT a.user_id, u.nama_lengkap, u.departemen, u.jabatan, a.status, u.is_bph
                    FROM attendances a 
                    JOIN users u ON a.user_id = u.id 
                    WHERE a.meeting_id = ? 
                    ORDER BY u.nama_lengkap
                ');
            }
            $stmt->execute([$meeting['id']]);
            $participants = $stmt->fetchAll();

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
    <title>Absensi Online — <?= APP_NAME ?></title>
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
                <!-- Header -->
                <div class="public-card-header">
                    <h2><i class="fas fa-clipboard-check"></i> Absensi Online</h2>
                    <p><?= APP_FULL_NAME ?></p>
                </div>
                
                <!-- Meeting Info -->
                <div class="meeting-info-grid" style="margin-bottom: var(--space-6);">
                    <div class="meeting-info-item">
                        <label>Jenis Rapat</label>
                        <p><?= getJenisRapatLabel($meeting['jenis_rapat']) ?></p>
                    </div>
                    <div class="meeting-info-item">
                        <label>Tanggal</label>
                        <p><?= formatTanggal($meeting['tanggal'], 'short') ?></p>
                    </div>
                    <div class="meeting-info-item">
                        <label>Waktu</label>
                        <p><?= formatWaktu($meeting['waktu_mulai']) ?></p>
                    </div>
                    <div class="meeting-info-item">
                        <label>Pembuat</label>
                        <p><?= htmlspecialchars($meeting['creator_name']) ?></p>
                    </div>
                </div>
                
                <?php if ($meeting['fokus_bahasan'] || $meeting['nama_event']) { ?>
                <div class="alert alert-info mb-6">
                    <i class="fas fa-info-circle"></i>
                    <span><?= htmlspecialchars($meeting['fokus_bahasan'] ?: $meeting['nama_event']) ?></span>
                </div>
                <?php } ?>
                
                <?php if ($isExpired) { ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-clock"></i>
                        <span>Absensi sudah ditutup. Link ini sudah expired.</span>
                    </div>
                <?php } elseif ($successMessage) { ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <span><?= $successMessage ?></span>
                    </div>
                <?php } else { ?>
                    <?php if ($errorMessage) { ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle"></i>
                            <span><?= $errorMessage ?></span>
                        </div>
                    <?php } ?>
                    
                    <!-- Attendance Form -->
                    <form method="POST" enctype="multipart/form-data">
                        <div class="form-group">
                            <label class="form-label">Nama Anda <span class="required">*</span></label>
                            <select name="user_id" class="form-control" id="selectUser" required onchange="autoFillDept()">
                                <option value="">— Pilih Nama Anda —</option>
                                <?php foreach ($participants as $p) { ?>
                                    <?php if ($p['status'] !== 'hadir') { ?>
                                    <option value="<?= $p['user_id'] ?>" data-dept="<?= htmlspecialchars($p['departemen']) ?>">
                                        <?= htmlspecialchars($p['nama_lengkap']) ?> (<?= $p['departemen'] ?>)
                                    </option>
                                    <?php } ?>
                                <?php } ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Departemen</label>
                            <input type="text" class="form-control" id="deptDisplay" readonly placeholder="Auto-fill">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Status Kehadiran <span class="required">*</span></label>
                            <div style="display: flex; gap: var(--space-4); flex-wrap: wrap;">
                                <label class="form-check">
                                    <input type="radio" name="status" value="hadir" required onchange="toggleKeterangan()">
                                    <span class="form-check-label"><i class="fas fa-check-circle text-success"></i> Hadir</span>
                                </label>
                                <label class="form-check">
                                    <input type="radio" name="status" value="izin" onchange="toggleKeterangan()">
                                    <span class="form-check-label"><i class="fas fa-info-circle text-warning"></i> Izin</span>
                                </label>
                                <label class="form-check">
                                    <input type="radio" name="status" value="sakit" onchange="toggleKeterangan()">
                                    <span class="form-check-label"><i class="fas fa-medkit text-primary"></i> Sakit</span>
                                </label>
                            </div>
                        </div>
                        
                        <div id="keteranganFields" style="display: none;">
                            <div class="form-group">
                                <label class="form-label">Keterangan <span class="required">*</span></label>
                                <textarea name="keterangan" class="form-control" placeholder="Jelaskan alasan ketidakhadiran Anda..." rows="3"></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Upload Bukti (opsional)</label>
                                <div class="file-upload" id="uploadZone">
                                    <input type="file" name="bukti_foto" accept="image/*" id="buktiInput">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                    <p>Klik atau drag foto bukti</p>
                                    <small style="color: var(--text-muted);">Maks 5MB (JPG, PNG)</small>
                                </div>
                                <div class="file-preview" id="buktiPreview"></div>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary btn-lg btn-block mt-6">
                            <i class="fas fa-paper-plane"></i> Kirim Absensi
                        </button>
                    </form>
                <?php } ?>
            </div>
        </div>
    </div>
    
    <script>
    function autoFillDept() {
        const select = document.getElementById('selectUser');
        const deptInput = document.getElementById('deptDisplay');
        const option = select.options[select.selectedIndex];
        deptInput.value = option?.dataset?.dept || '';
    }
    
    function toggleKeterangan() {
        const status = document.querySelector('input[name="status"]:checked')?.value;
        const fields = document.getElementById('keteranganFields');
        fields.style.display = (status === 'izin' || status === 'sakit') ? 'block' : 'none';
    }
    
    // Image preview
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
