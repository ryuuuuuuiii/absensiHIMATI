<?php
/**
 * Hasil Buat Rapat — Public Page
 * Menampilkan link scanner, absent, dan online attendance
 * Bisa di-bookmark dan diakses kembali
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

$token = sanitize($_GET['token'] ?? '');
if (empty($token)) {
    die('Link tidak valid.');
}

$db = getDB();

$stmt = $db->prepare("
    SELECT m.*, u.nama_lengkap as creator_name, u.departemen as creator_dept
    FROM meetings m 
    JOIN users u ON m.created_by = u.id 
    WHERE m.unique_link = ?
");
$stmt->execute([$token]);
$meeting = $stmt->fetch();

if (!$meeting) {
    die('Rapat tidak ditemukan.');
}

// Get attendance counts
$stmt = $db->prepare("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'hadir' THEN 1 ELSE 0 END) as hadir
    FROM attendances WHERE meeting_id = ?
");
$stmt->execute([$meeting['id']]);
$stats = $stmt->fetch();

// URLs
$scanUrl = BASE_URL . '/rapat/scanner.php?token=' . $meeting['unique_link'];
$absentUrl = BASE_URL . '/meeting/absent_form.php?token=' . $meeting['absent_link'];
$onlineUrl = BASE_URL . '/meeting/online_attend.php?token=' . $meeting['unique_link'];

// Get logos
$logo1 = getSetting('logo_1', '');
$logo2 = getSetting('logo_2', '');
$logo3 = getSetting('logo_3', '');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rapat Dibuat — <?= APP_NAME ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
    <div class="bg-animated"></div>
    
    <div class="public-page">
        <div class="public-container" style="max-width: 600px;">
            
            <!-- Success Banner -->
            <div class="public-card" style="text-align: center; margin-bottom: var(--space-6);">
                <div style="font-size: 3rem; color: var(--success); margin-bottom: var(--space-3);">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h2 style="color: var(--success); margin-bottom: var(--space-2);">Rapat Berhasil Dibuat!</h2>
                <p style="color: var(--text-muted);">Bookmark halaman ini agar bisa diakses kembali nanti</p>
            </div>
            
            <!-- Meeting Info -->
            <div class="public-card" style="margin-bottom: var(--space-6);">
                <div class="meeting-info-grid">
                    <div class="meeting-info-item">
                        <label>Jenis Rapat</label>
                        <p><?= getJenisRapatLabel($meeting['jenis_rapat']) ?></p>
                    </div>
                    <div class="meeting-info-item">
                        <label>Pembuat</label>
                        <p><?= htmlspecialchars($meeting['creator_name']) ?></p>
                    </div>
                    <div class="meeting-info-item">
                        <label>Tanggal</label>
                        <p><?= formatTanggal($meeting['tanggal'], 'short') ?></p>
                    </div>
                    <div class="meeting-info-item">
                        <label>Waktu</label>
                        <p><?= formatWaktu($meeting['waktu_mulai']) ?></p>
                    </div>
                </div>
                
                <?php if ($meeting['fokus_bahasan'] || $meeting['nama_event']): ?>
                <div style="margin-top: var(--space-4); padding-top: var(--space-4); border-top: 1px solid var(--dark-border);">
                    <label style="font-size: var(--font-xs); color: var(--text-muted);">Agenda / Fokus</label>
                    <p style="margin-top: var(--space-1);"><?= htmlspecialchars($meeting['fokus_bahasan'] ?: $meeting['nama_event']) ?></p>
                </div>
                <?php endif; ?>
                
                <div style="margin-top: var(--space-4); text-align: center;">
                    <div style="font-size: var(--font-2xl); font-weight: 800; color: var(--success);">
                        <?= $stats['hadir'] ?>/<?= $stats['total'] ?>
                    </div>
                    <small style="color: var(--text-muted);">Kehadiran</small>
                </div>
            </div>
            
            <!-- Action Links -->
            <div class="public-card" style="margin-bottom: var(--space-4);">
                <h3 style="margin-bottom: var(--space-4); font-size: var(--font-base);">
                    <i class="fas fa-share-alt"></i> Link Aksi
                </h3>
                
                <!-- Scanner Link -->
                <?php if ($meeting['mode_rapat'] === 'offline'): ?>
                <a href="<?= $scanUrl ?>" class="btn btn-primary btn-lg btn-block mb-4" style="display: flex; align-items: center; justify-content: center; gap: 8px;">
                    <i class="fas fa-qrcode"></i> Buka Scanner QR Code
                </a>
                <?php endif; ?>
                
                <!-- Online Attend Link -->
                <?php if ($meeting['mode_rapat'] === 'online'): ?>
                <div style="margin-bottom: var(--space-4);">
                    <label class="form-label" style="font-size: var(--font-xs);">
                        <i class="fas fa-link" style="color: var(--info);"></i> Link Absensi Online (share ke peserta)
                    </label>
                    <div class="share-link-box">
                        <input type="text" value="<?= $onlineUrl ?>" readonly>
                        <button onclick="copyLink('<?= $onlineUrl ?>', this)"><i class="fas fa-copy"></i> Salin</button>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Absent Link -->
                <div>
                    <label class="form-label" style="font-size: var(--font-xs);">
                        <i class="fas fa-user-slash" style="color: var(--danger);"></i> Link Tidak Hadir (share ke yg tidak bisa hadir)
                    </label>
                    <div class="share-link-box">
                        <input type="text" value="<?= $absentUrl ?>" readonly>
                        <button onclick="copyLink('<?= $absentUrl ?>', this)"><i class="fas fa-copy"></i> Salin</button>
                    </div>
                </div>
            </div>
            
            <!-- Back to Create -->
            <div style="text-align: center; margin-top: var(--space-4);">
                <a href="<?= BASE_URL ?>/rapat" style="color: var(--primary); text-decoration: none; font-size: var(--font-sm);">
                    <i class="fas fa-plus-circle"></i> Buat Rapat Lagi
                </a>
            </div>
            
            <div style="text-align: center; margin-top: var(--space-6);">
                <p style="font-size: var(--font-xs); color: var(--text-muted);">
                    &copy; <?= date('Y') ?> <?= APP_FULL_NAME ?>
                </p>
            </div>
        </div>
    </div>
    
    <div id="toastContainer" class="toast-container"></div>
    <script src="<?= BASE_URL ?>/assets/js/app.js"></script>
    <script>
    function copyLink(text, btn) {
        navigator.clipboard.writeText(text).then(() => {
            const orig = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-check"></i> Tersalin!';
            btn.style.background = 'var(--success)';
            setTimeout(() => { btn.innerHTML = orig; btn.style.background = ''; }, 2000);
        }).catch(() => {
            // Fallback
            const ta = document.createElement('textarea');
            ta.value = text; document.body.appendChild(ta);
            ta.select(); document.execCommand('copy');
            document.body.removeChild(ta);
            showToast('success', 'Tersalin!', 'Link berhasil disalin.');
        });
    }
    </script>
</body>
</html>
