<?php
/**
 * My QR Code Page
 * Absensi HIMA TI Politeknik Hasnur
 */

require_once __DIR__ . '/../includes/auth_check.php';

$pageTitle = 'QR Code Saya';
$pageIcon = 'fas fa-qrcode';
$currentUser = getCurrentUser();

// Get QR code data
$db = getDB();
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$currentUser['id']]);
$user = $stmt->fetch();

// QR Code data (encoded as JSON)
$qrData = json_encode([
    'id'         => $user['id'],
    'qr_code'    => $user['qr_code'],
    'nama'       => $user['nama_lengkap'],
    'departemen' => $user['departemen'],
    'jabatan'    => $user['jabatan']
]);

// Generate QR Code URL using online API
$qrImageUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($qrData) . '&bgcolor=ffffff&color=1A1A2E';

// Check if local QR exists
$localQR = '';
if ($user['qr_image'] && file_exists(QR_PATH . $user['qr_image'])) {
    $localQR = BASE_URL . '/assets/img/qrcodes/' . $user['qr_image'];
}

include __DIR__ . '/../includes/header.php';
?>

<div style="max-width: 500px; margin: 0 auto;">
    <div class="card">
        <div class="qr-display">
            <!-- QR Code -->
            <div class="qr-card">
                <img src="<?= $localQR ?: $qrImageUrl ?>" 
                     alt="QR Code <?= htmlspecialchars($user['nama_lengkap']) ?>"
                     id="qrImage"
                     style="width: 250px; height: 250px;">
            </div>
            
            <!-- User Info -->
            <div class="qr-info">
                <h3><?= htmlspecialchars($user['nama_lengkap']) ?></h3>
                <p>
                    <span class="badge badge-primary" style="font-size: var(--font-sm);">
                        <?= getDepartemenLabel($user['departemen']) ?>
                    </span>
                </p>
                <p style="margin-top: var(--space-2);">
                    <i class="fas fa-id-badge"></i> <?= htmlspecialchars($user['jabatan']) ?>
                </p>
            </div>
            
            <!-- Actions -->
            <div class="btn-group" style="justify-content: center; margin-top: var(--space-6);">
                <button class="btn btn-primary" onclick="downloadQR()">
                    <i class="fas fa-download"></i> Download QR
                </button>
                <button class="btn btn-secondary" onclick="shareQR()">
                    <i class="fas fa-share-alt"></i> Share
                </button>
            </div>
        </div>
    </div>
    
    <!-- Info Card -->
    <div class="card" style="margin-top: var(--space-4);">
        <div class="alert alert-info" style="margin-bottom: 0;">
            <i class="fas fa-info-circle"></i>
            <div>
                <strong>Cara Penggunaan QR Code:</strong><br>
                <small>
                    Tunjukkan QR code ini saat absensi rapat. QR code akan di-scan oleh pembuat rapat 
                    menggunakan kamera HP. Pastikan layar HP cukup terang agar mudah terbaca.
                </small>
            </div>
        </div>
    </div>
</div>

<script>
function downloadQR() {
    const img = document.getElementById('qrImage');
    const canvas = document.createElement('canvas');
    const ctx = canvas.getContext('2d');
    
    // Create high-res canvas
    canvas.width = 400;
    canvas.height = 500;
    
    // Background
    ctx.fillStyle = '#FFFFFF';
    ctx.fillRect(0, 0, canvas.width, canvas.height);
    
    // Draw QR image
    const qrImg = new Image();
    qrImg.crossOrigin = 'anonymous';
    qrImg.onload = function() {
        ctx.drawImage(qrImg, 50, 30, 300, 300);
        
        // Draw name
        ctx.fillStyle = '#1A1A2E';
        ctx.font = 'bold 20px Poppins, sans-serif';
        ctx.textAlign = 'center';
        ctx.fillText('<?= addslashes($user['nama_lengkap']) ?>', 200, 370);
        
        // Draw department
        ctx.font = '14px Poppins, sans-serif';
        ctx.fillStyle = '#F5921B';
        ctx.fillText('<?= addslashes(getDepartemenLabel($user['departemen'])) ?>', 200, 395);
        
        // Draw jabatan
        ctx.font = '12px Poppins, sans-serif';
        ctx.fillStyle = '#757575';
        ctx.fillText('<?= addslashes($user['jabatan']) ?>', 200, 418);
        
        // Footer
        ctx.font = '10px Poppins, sans-serif';
        ctx.fillStyle = '#BDBDBD';
        ctx.fillText('HIMA TI - Politeknik Hasnur', 200, 460);
        
        // Download
        const link = document.createElement('a');
        link.download = 'QR_<?= preg_replace('/[^a-zA-Z0-9]/', '_', $user['nama_lengkap']) ?>.png';
        link.href = canvas.toDataURL('image/png');
        link.click();
    };
    qrImg.src = img.src;
}

function shareQR() {
    if (navigator.share) {
        navigator.share({
            title: 'QR Code - <?= addslashes($user['nama_lengkap']) ?>',
            text: 'QR Code Absensi HIMA TI - <?= addslashes($user['nama_lengkap']) ?>',
        }).catch(console.error);
    } else {
        showToast('info', 'Info', 'Gunakan tombol Download untuk menyimpan QR code.');
    }
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
