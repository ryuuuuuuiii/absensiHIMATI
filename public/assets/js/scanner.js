/**
 * QR Scanner Logic
 * Absensi HIMA TI Politeknik Hasnur
 * 
 * Uses html5-qrcode library for scanning
 * Implements localStorage persistence for state recovery
 */

let html5QrCode = null;
let scannedUsers = [];
let isScanning = false;

// ============================================
// INITIALIZATION
// ============================================
document.addEventListener('DOMContentLoaded', () => {
    // Restore state from localStorage
    restoreState();
    
    // Initialize scanner
    initScanner();
    
    // Setup photo input listener
    setupPhotoCapture();
    
    // Handle visibility change (app switch / screen lock)
    document.addEventListener('visibilitychange', handleVisibilityChange);
    
    // Save state periodically
    setInterval(saveState, 5000);
});

// ============================================
// QR SCANNER INIT
// ============================================
function initScanner() {
    try {
        html5QrCode = new Html5Qrcode("qr-reader");
        startScanning();
    } catch (err) {
        console.error("Scanner init error:", err);
        showToast('error', 'Error', 'Gagal inisialisasi scanner. Pastikan browser mendukung kamera.');
    }
}

function startScanning() {
    if (isScanning) return;
    
    const config = {
        fps: 10,
        qrbox: { width: 220, height: 220 },
        aspectRatio: 1.0,
        disableFlip: false
    };
    
    html5QrCode.start(
        { facingMode: "environment" },
        config,
        onScanSuccess,
        onScanError
    ).then(() => {
        isScanning = true;
    }).catch(err => {
        console.error("Camera start error:", err);
        // Try with user-facing camera
        html5QrCode.start(
            { facingMode: "user" },
            config,
            onScanSuccess,
            onScanError
        ).then(() => {
            isScanning = true;
        }).catch(err2 => {
            showToast('error', 'Kamera Error', 'Tidak dapat mengakses kamera. Pastikan izin kamera diberikan.');
        });
    });
}

function stopScanning() {
    if (html5QrCode && isScanning) {
        html5QrCode.stop().then(() => {
            isScanning = false;
        }).catch(console.error);
    }
}

// ============================================
// SCAN HANDLERS
// ============================================
function onScanSuccess(decodedText, decodedResult) {
    try {
        const data = JSON.parse(decodedText);
        
        if (!data.id || !data.qr_code) {
            showScanResult('error', 'QR Code tidak valid', 'Data QR tidak sesuai format.');
            return;
        }
        
        // Check if already scanned
        if (SCANNED_USER_IDS.includes(data.id) || scannedUsers.includes(data.id)) {
            showScanResult('duplicate', 'Sudah Di-Scan!', 
                `${data.nama} (${data.departemen}) sudah tercatat hadir.`);
            playSound('duplicate');
            return;
        }
        
        // Process scan
        processAttendance(data);
        
    } catch (e) {
        showScanResult('error', 'QR Tidak Dikenali', 'Pastikan QR Code benar dari pengurus HIMA TI.');
    }
}

function onScanError(errorMessage) {
    // Silently ignore scan errors (normal when no QR in view)
}

// ============================================
// PROCESS ATTENDANCE
// ============================================
async function processAttendance(userData) {
    try {
        const response = await fetch(BASE_URL + '/meeting/process/process_scan.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                meeting_id: MEETING_ID,
                user_id: userData.id,
                qr_code: userData.qr_code,
                csrf_token: CSRF_TOKEN
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            // Add to scanned list
            scannedUsers.push(userData.id);
            SCANNED_USER_IDS.push(userData.id);
            
            // Update UI
            addToScannedList(userData, result.scanned_at);
            updateCounters();
            showScanResult('success', 'Berhasil!', 
                `${userData.nama} (${userData.departemen}) berhasil tercatat hadir.`);
            playSound('success');
            
            // Save state
            saveState();
        } else {
            showScanResult('error', 'Gagal', result.message || 'Gagal mencatat kehadiran.');
        }
    } catch (error) {
        console.error('Process error:', error);
        showScanResult('error', 'Error', 'Gagal menghubungi server. Periksa koneksi internet.');
    }
}

// ============================================
// UI UPDATES
// ============================================
function showScanResult(type, title, message) {
    const resultEl = document.getElementById('scanResult');
    resultEl.style.display = 'block';
    
    const icons = {
        success: 'fas fa-check-circle',
        duplicate: 'fas fa-exclamation-triangle',
        error: 'fas fa-times-circle'
    };
    
    const colors = {
        success: 'var(--success)',
        duplicate: 'var(--warning)',
        error: 'var(--danger)'
    };
    
    resultEl.className = `scan-result ${type} mt-4`;
    resultEl.innerHTML = `
        <div class="d-flex align-center gap-3">
            <i class="${icons[type]}" style="font-size: var(--font-2xl); color: ${colors[type]};"></i>
            <div>
                <strong style="color: ${colors[type]};">${title}</strong>
                <p style="font-size: var(--font-sm); color: var(--text-secondary); margin-top: 2px;">${message}</p>
            </div>
        </div>
    `;
    
    // Auto-hide after 3 seconds
    setTimeout(() => {
        resultEl.style.display = 'none';
    }, 3000);
}

function addToScannedList(userData, scannedAt) {
    const list = document.getElementById('scannedList');
    const emptyState = document.getElementById('emptyState');
    
    // Remove empty state
    if (emptyState) emptyState.remove();
    
    const time = scannedAt ? new Date(scannedAt).toLocaleTimeString('id-ID', {
        hour: '2-digit', minute: '2-digit'
    }) : new Date().toLocaleTimeString('id-ID', {
        hour: '2-digit', minute: '2-digit'
    });
    
    const itemHTML = `
        <div class="scanned-item" data-user-id="${userData.id}" style="animation: slideDown 0.3s ease;">
            <div class="scanned-item-avatar">
                <i class="fas fa-check"></i>
            </div>
            <div class="scanned-item-info">
                <h4>${escapeHTML(userData.nama)}</h4>
                <p>${escapeHTML(userData.departemen)} • ${escapeHTML(userData.jabatan || '')}</p>
            </div>
            <span class="scanned-item-time">${time}</span>
        </div>
    `;
    
    // Prepend to list
    list.insertAdjacentHTML('afterbegin', itemHTML);
}

function updateCounters() {
    const hadirEl = document.getElementById('hadirCounter');
    const countEl = document.getElementById('scannedCount');
    
    const count = SCANNED_USER_IDS.length;
    
    if (hadirEl) hadirEl.textContent = count;
    if (countEl) countEl.textContent = count + ' hadir';
}

// ============================================
// SOUND FEEDBACK
// ============================================
function playSound(type) {
    try {
        const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
        const oscillator = audioCtx.createOscillator();
        const gainNode = audioCtx.createGain();
        
        oscillator.connect(gainNode);
        gainNode.connect(audioCtx.destination);
        
        if (type === 'success') {
            oscillator.frequency.value = 800;
            gainNode.gain.value = 0.3;
            oscillator.start();
            setTimeout(() => {
                oscillator.frequency.value = 1200;
            }, 100);
            setTimeout(() => oscillator.stop(), 200);
        } else {
            oscillator.frequency.value = 400;
            gainNode.gain.value = 0.2;
            oscillator.start();
            setTimeout(() => oscillator.stop(), 200);
        }
        
        // Vibrate on mobile
        if (navigator.vibrate) {
            navigator.vibrate(type === 'success' ? [100, 50, 100] : [200]);
        }
    } catch (e) {
        // Silent fail - audio not supported
    }
}

// ============================================
// STATE PERSISTENCE (localStorage)
// ============================================
function saveState() {
    const state = {
        meetingId: MEETING_ID,
        scannedUsers: scannedUsers,
        scannedUserIds: SCANNED_USER_IDS,
        timestamp: new Date().toISOString()
    };
    localStorage.setItem('scan_state_' + MEETING_ID, JSON.stringify(state));
}

function restoreState() {
    const saved = localStorage.getItem('scan_state_' + MEETING_ID);
    if (saved) {
        try {
            const state = JSON.parse(saved);
            if (state.meetingId === MEETING_ID) {
                scannedUsers = state.scannedUsers || [];
                // Merge with server-side scanned IDs
                state.scannedUserIds?.forEach(id => {
                    if (!SCANNED_USER_IDS.includes(id)) {
                        SCANNED_USER_IDS.push(id);
                    }
                });
            }
        } catch (e) {
            console.error('State restore error:', e);
        }
    }
}

function clearState() {
    localStorage.removeItem('scan_state_' + MEETING_ID);
}

// ============================================
// VISIBILITY CHANGE HANDLER
// ============================================
function handleVisibilityChange() {
    if (document.visibilityState === 'visible') {
        // Page is visible again — restart scanner
        if (!isScanning) {
            setTimeout(() => {
                startScanning();
            }, 500);
        }
    } else {
        // Page hidden — save state
        saveState();
    }
}

// ============================================
// PHOTO CAPTURE
// ============================================
function setupPhotoCapture() {
    const photoInput = document.getElementById('photoInput');
    if (photoInput) {
        photoInput.addEventListener('change', handlePhotoCapture);
    }
}

function capturePhoto() {
    document.getElementById('photoInput').click();
}

async function handlePhotoCapture(event) {
    const file = event.target.files[0];
    if (!file) return;
    
    showToast('info', 'Mengupload...', 'Mengirim foto dokumentasi...');
    
    const formData = new FormData();
    formData.append('photo', file);
    formData.append('meeting_id', MEETING_ID);
    formData.append('csrf_token', CSRF_TOKEN);
    
    try {
        const response = await fetch(BASE_URL + '/meeting/process/save_photo.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showToast('success', 'Berhasil!', 'Foto dokumentasi berhasil disimpan.');
        } else {
            showToast('error', 'Gagal', result.message || 'Gagal menyimpan foto.');
        }
    } catch (error) {
        showToast('error', 'Error', 'Gagal mengupload foto. Periksa koneksi.');
    }
    
    // Reset input
    event.target.value = '';
}

// ============================================
// FINISH ATTENDANCE
// ============================================
async function finishAttendance() {
    if (!confirm('Apakah Anda yakin ingin menyelesaikan absensi? Peserta yang belum scan akan dicatat sebagai Alpha.')) {
        return;
    }
    
    try {
        const response = await fetch(BASE_URL + '/meeting/process/process_scan.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                action: 'finish',
                meeting_id: MEETING_ID,
                csrf_token: CSRF_TOKEN
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            stopScanning();
            clearState();
            showToast('success', 'Selesai!', 'Absensi berhasil diselesaikan dan disinkronkan.');
            
            setTimeout(() => {
                window.location.href = BASE_URL + '/meeting/detail.php?id=' + MEETING_ID;
            }, 1500);
        } else {
            showToast('error', 'Error', result.message || 'Gagal menyelesaikan absensi.');
        }
    } catch (error) {
        showToast('error', 'Error', 'Gagal menghubungi server.');
    }
}

// ============================================
// HELPERS
// ============================================
function escapeHTML(str) {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

// Cleanup on page unload
window.addEventListener('beforeunload', () => {
    saveState();
});
