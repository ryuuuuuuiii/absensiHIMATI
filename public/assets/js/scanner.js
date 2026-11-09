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
let isProcessingScan = false;
let isScannerPaused = false;
let isTorchOn = false;
let torchSupported = false;
let lastScannedCode = null;
let lastScannedTime = 0;
let scanResultTimeout = null;
const recentlyScannedCodes = new Set();
const recentlyScannedUserIds = new Set();
const scannedQrCodes = new Set(
    (typeof SCANNED_QR_CODES !== 'undefined' && Array.isArray(SCANNED_QR_CODES)) ? SCANNED_QR_CODES : []
);

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
    
    // Initialize documentation photo state if present
    initDocPhotoState();
    
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
        const qrConfig = {
            verbose: false
        };
        if (typeof Html5QrcodeSupportedFormats !== 'undefined') {
            qrConfig.formatsToSupport = [Html5QrcodeSupportedFormats.QR_CODE];
        }
        html5QrCode = new Html5Qrcode("qr-reader", qrConfig);
        startScanning();
    } catch (err) {
        console.error("Scanner init error:", err);
        showToast('error', 'Error', 'Gagal inisialisasi scanner. Pastikan browser mendukung kamera.');
    }
}

function startScanning() {
    if (isScanning) return;
    
    const config = {
        fps: 10, // Optimal 10 FPS: eliminates CPU throttling, lag, and queue buildup on mobile devices
        qrbox: function(viewfinderWidth, viewfinderHeight) {
            const minEdge = Math.min(viewfinderWidth, viewfinderHeight);
            // Generous scanning area (85%) so users can scan effortlessly without pinpoint centering
            const edge = Math.max(240, Math.floor(minEdge * 0.85));
            return { width: edge, height: edge };
        },
        disableFlip: false,
        experimentalFeatures: {
            useBarCodeDetectorIfSupported: true
        }
    };
    
    html5QrCode.start(
        { facingMode: "environment" },
        config,
        onScanSuccess,
        onScanError
    ).then(() => {
        isScanning = true;
        isScannerPaused = false;
        setTimeout(checkAndSetupTorch, 1000);
    }).catch(err => {
        console.warn("Camera environment failed, falling back to user facing:", err);
        // Try with user-facing camera
        html5QrCode.start(
            { facingMode: "user" },
            config,
            onScanSuccess,
            onScanError
        ).then(() => {
            isScanning = true;
            isScannerPaused = false;
        }).catch(err2 => {
            showToast('error', 'Kamera Error', 'Tidak dapat mengakses kamera. Pastikan izin kamera diberikan.');
        });
    });
}

function stopScanning() {
    if (html5QrCode && isScanning) {
        if (isTorchOn) {
            toggleTorch();
        }
        html5QrCode.stop().then(() => {
            isScanning = false;
            isScannerPaused = false;
        }).catch(console.error);
    }
}

// ============================================
// TORCH / FLASHLIGHT CONTROLS
// ============================================
function checkAndSetupTorch() {
    try {
        if (!html5QrCode) return;
        const capabilities = html5QrCode.getRunningTrackCameraCapabilities();
        if (capabilities && typeof capabilities.torchFeature === 'function') {
            const torch = capabilities.torchFeature();
            if (torch && torch.isSupported()) {
                torchSupported = true;
                const btnTorch = document.getElementById('btnTorch');
                if (btnTorch) {
                    btnTorch.style.display = 'inline-flex';
                }
            }
        }
    } catch (e) {
        // Torch capability check not supported or failed
    }
}

async function toggleTorch() {
    if (!html5QrCode) return;
    try {
        const capabilities = html5QrCode.getRunningTrackCameraCapabilities();
        if (capabilities && typeof capabilities.torchFeature === 'function') {
            const torch = capabilities.torchFeature();
            if (torch && torch.isSupported()) {
                isTorchOn = !isTorchOn;
                await torch.apply(isTorchOn);
                const btnTorch = document.getElementById('btnTorch');
                const torchText = document.getElementById('torchText');
                if (btnTorch) {
                    if (isTorchOn) {
                        btnTorch.classList.add('active');
                        if (torchText) torchText.textContent = 'Senter Aktif';
                    } else {
                        btnTorch.classList.remove('active');
                        if (torchText) torchText.textContent = 'Senter';
                    }
                }
            }
        }
    } catch (e) {
        console.warn('Torch toggle error:', e);
    }
}

// ============================================
// SCAN HANDLERS
// ============================================
function onScanSuccess(decodedText, decodedResult) {
    // 1. If scanner is currently processing or in cooldown, ignore frames
    if (isProcessingScan || isScannerPaused) {
        return;
    }

    const rawText = (typeof decodedText === 'string') ? decodedText.trim() : '';
    if (!rawText) return;

    const now = Date.now();

    // 2. Parse payload: supports JSON object or plain QR code string
    let data = null;
    try {
        data = JSON.parse(rawText);
    } catch (e) {
        data = {
            qr_code: rawText
        };
    }

    if (!data || (!data.id && !data.qr_code)) {
        return;
    }

    const userIdNum = data.id ? Number(data.id) : null;
    const qrStr = data.qr_code ? String(data.qr_code).trim() : '';

    // 3. SILENT IGNORE: If this person's QR was just scanned in the last 15 seconds,
    // do NOT re-submit and do NOT spam duplicate alerts or beeps while holding the phone!
    if ((qrStr && recentlyScannedCodes.has(qrStr)) || (userIdNum && recentlyScannedUserIds.has(userIdNum))) {
        return;
    }

    // 4. Check if already attended earlier in this meeting (preloaded or previous scan)
    const isAlreadyAttended = (qrStr && scannedQrCodes.has(qrStr)) ||
        (userIdNum && (
            SCANNED_USER_IDS.some(id => Number(id) === userIdNum) ||
            scannedUsers.some(id => Number(id) === userIdNum)
        ));

    if (isAlreadyAttended) {
        if (qrStr) recentlyScannedCodes.add(qrStr);
        if (userIdNum) recentlyScannedUserIds.add(userIdNum);
        setTimeout(() => {
            if (qrStr) recentlyScannedCodes.delete(qrStr);
            if (userIdNum) recentlyScannedUserIds.delete(userIdNum);
        }, 10000);

        showScanResult('duplicate', 'Sudah Di-Scan Sebelumnya!', 
            `${escapeHTML(data.nama || 'Peserta')} (${escapeHTML(data.departemen || '')}) sudah tercatat hadir.`);
        playSound('duplicate');

        // Lock briefly (2.5s) to avoid buzzing
        isScannerPaused = true;
        setTimeout(() => {
            isScannerPaused = false;
        }, 2500);
        return;
    }

    // 5. Lock scanner immediately to prevent duplicate requests from subsequent frames
    isProcessingScan = true;
    isScannerPaused = true;
    lastScannedCode = rawText;
    lastScannedTime = now;
    if (qrStr) recentlyScannedCodes.add(qrStr);
    if (userIdNum) recentlyScannedUserIds.add(userIdNum);

    // Process scan via API
    processAttendance(data);
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
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': CSRF_TOKEN
            },
            body: JSON.stringify({
                meeting_id: MEETING_ID,
                user_id: userData.id || null,
                qr_code: userData.qr_code,
                csrf_token: CSRF_TOKEN
            })
        });

        const result = await response.json();

        if (result.success) {
            const user = result.user || userData;
            const uId = user.id ? Number(user.id) : (userData.id ? Number(userData.id) : null);
            const uQr = user.qr_code || userData.qr_code;

            // Track scanned ID & QR Code
            if (uId) {
                if (!scannedUsers.some(x => Number(x) === uId)) {
                    scannedUsers.push(uId);
                }
                if (!SCANNED_USER_IDS.some(x => Number(x) === uId)) {
                    SCANNED_USER_IDS.push(uId);
                }
                recentlyScannedUserIds.add(uId);
            }
            if (uQr) {
                scannedQrCodes.add(uQr);
                recentlyScannedCodes.add(uQr);
            }

            // Update UI
            addToScannedList(user, result.scanned_at);
            updateCounters();

            const badgeText = user.is_bph_inti ? ' (Pengurus Inti)' : '';
            let spInfoText = '';
            if (user.sp_level > 0) {
                const spLabelText = user.sp_label || ('SP ' + user.sp_level);
                const spDetailText = user.sp_source === 'alpha'
                    ? 'Tidak hadir 3x berturut-turut'
                    : (user.sp_reason ? `Pelanggaran: ${user.sp_reason}` : 'Pelanggaran disiplin');
                spInfoText = ` • Status Akun: ${spLabelText} (${spDetailText})`;
            }

            showScanResult('success', 'Berhasil! ✓' + (user.sp_level > 0 ? ` [${user.sp_label || ('SP ' + user.sp_level)}]` : ''), 
                `<strong>${escapeHTML(user.nama || 'Peserta')}</strong> (${escapeHTML(user.departemen || '')}${badgeText}) berhasil tercatat hadir${spInfoText}.`);
            playSound('success');

            // Save state
            saveState();

            // Retain in recentlyScanned for 15 seconds: prevents repeated triggers if phone stays in front of lens
            setTimeout(() => {
                if (uId) recentlyScannedUserIds.delete(uId);
                if (uQr) recentlyScannedCodes.delete(uQr);
            }, 15000);

            // Cooldown of 2.5s before ready for the NEXT person to scan
            setTimeout(() => {
                isProcessingScan = false;
                isScannerPaused = false;
            }, 2500);

        } else if (result.duplicate) {
            const uId = userData.id ? Number(userData.id) : null;
            if (uId && !SCANNED_USER_IDS.some(x => Number(x) === uId)) {
                SCANNED_USER_IDS.push(uId);
            }
            if (userData.qr_code) {
                scannedQrCodes.add(userData.qr_code);
            }

            showScanResult('duplicate', 'Sudah Di-Scan!', result.message);
            playSound('duplicate');

            setTimeout(() => {
                isProcessingScan = false;
                isScannerPaused = false;
            }, 2500);

        } else {
            showScanResult('error', 'Presensi Ditolak', result.message || 'Gagal mencatat kehadiran.');
            playSound('error');

            setTimeout(() => {
                isProcessingScan = false;
                isScannerPaused = false;
            }, 2000);
        }
    } catch (error) {
        console.error('Process error:', error);
        showScanResult('error', 'Error', 'Gagal memproses data absensi.');

        setTimeout(() => {
            isProcessingScan = false;
            isScannerPaused = false;
        }, 2000);
    }
}

// ============================================
// UI UPDATES
// ============================================
function showScanResult(type, title, message) {
    const resultEl = document.getElementById('scanResult');
    if (!resultEl) return;

    if (scanResultTimeout) {
        clearTimeout(scanResultTimeout);
        scanResultTimeout = null;
    }

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
    scanResultTimeout = setTimeout(() => {
        resultEl.style.display = 'none';
        scanResultTimeout = null;
    }, 3000);
}

function addToScannedList(userData, scannedAt) {
    const list = document.getElementById('scannedList');
    if (!list) return;

    // Prevent duplicate entries in DOM
    const numId = userData.id ? Number(userData.id) : null;
    if (numId && list.querySelector(`.scanned-item[data-user-id="${numId}"]`)) {
        return;
    }
    const cleanQr = userData.qr_code ? String(userData.qr_code).trim() : '';
    if (cleanQr) {
        const existingByQr = Array.from(list.querySelectorAll('.scanned-item')).some(el => {
            return (el.getAttribute('data-qr') || '').trim() === cleanQr;
        });
        if (existingByQr) return;
    }

    const emptyState = document.getElementById('emptyState');
    // Remove empty state
    if (emptyState) emptyState.remove();

    let time = '';
    if (scannedAt && typeof scannedAt === 'string') {
        if (scannedAt.includes(':')) {
            time = scannedAt.trim();
        } else {
            try {
                const parsed = new Date(scannedAt);
                if (!isNaN(parsed.getTime())) {
                    time = parsed.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
                } else {
                    time = scannedAt;
                }
            } catch (e) {
                time = scannedAt;
            }
        }
    } else {
        const now = new Date();
        time = String(now.getHours()).padStart(2, '0') + ':' + String(now.getMinutes()).padStart(2, '0');
    }

    const userIdAttr = userData.id ? `data-user-id="${userData.id}"` : '';
    const qrAttr = userData.qr_code ? `data-qr="${userData.qr_code}"` : '';
    const badgeDept = userData.is_bph_inti
        ? '<span class="badge badge-warning" style="font-size: 11px; padding: 2px 7px;"><i class="fas fa-crown"></i> Pengurus Inti</span>'
        : (userData.departemen ? `<span class="badge badge-dept" style="font-size: 11px; padding: 2px 7px;">${escapeHTML(userData.departemen)}</span>` : '');
    const badgeBph = userData.is_bph
        ? '<span class="badge badge-warning" style="font-size: 11px; padding: 2px 7px;"><i class="fas fa-shield-alt"></i> BPH</span>'
        : '';
    const jabatanText = userData.jabatan ? `<span>${escapeHTML(userData.jabatan)}</span>` : '';
    let spBadge = '';
    if (userData.sp_level === 1) {
        spBadge = '<span class="badge badge-sp-1" style="font-size: 10px; padding: 1px 6px;"><i class="fas fa-exclamation-triangle"></i> SP 1</span>';
    } else if (userData.sp_level === 2) {
        spBadge = '<span class="badge badge-sp-2" style="font-size: 10px; padding: 1px 6px;"><i class="fas fa-exclamation-circle"></i> SP 2</span>';
    } else if (userData.sp_level >= 3) {
        spBadge = '<span class="badge badge-sp-3" style="font-size: 10px; padding: 1px 6px;"><i class="fas fa-user-times"></i> SP 3</span>';
    }

    const itemHTML = `
        <div class="scanned-item" ${userIdAttr} ${qrAttr} style="animation: slideDown 0.3s ease;">
            <div class="scanned-item-avatar">
                <i class="fas fa-check"></i>
            </div>
            <div class="scanned-item-info">
                <h4 style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                    <span>${escapeHTML(userData.nama || 'Peserta')}</span>
                    ${spBadge}
                </h4>
                <p>
                    ${badgeDept}
                    ${badgeBph}
                    ${jabatanText}
                </p>
            </div>
            <span class="scanned-item-time">
                <i class="fas fa-clock" style="color: var(--text-muted); font-size: 10px;"></i>
                ${time} WITA
            </span>
        </div>
    `;

    // Prepend to list
    list.insertAdjacentHTML('afterbegin', itemHTML);
}

function updateCounters() {
    const hadirEl = document.getElementById('hadirCounter');
    const totalEl = document.getElementById('totalCounter');
    const countEl = document.getElementById('scannedCount');

    const count = SCANNED_USER_IDS.length;

    if (hadirEl) hadirEl.textContent = count;
    if (countEl) countEl.innerHTML = `<i class="fas fa-check-circle"></i> ${count} hadir`;

    if (totalEl) {
        let currentTotal = parseInt(totalEl.textContent) || 0;
        if (count > currentTotal) {
            currentTotal = count;
            totalEl.textContent = currentTotal;
        }
    }
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
        scannedQrCodes: Array.from(scannedQrCodes),
        timestamp: new Date().toISOString()
    };
    localStorage.setItem('scan_state_' + MEETING_ID, JSON.stringify(state));
}

function restoreState() {
    // Index existing DOM items rendered from server
    document.querySelectorAll('#scannedList .scanned-item').forEach(el => {
        const qr = el.getAttribute('data-qr');
        const uid = el.getAttribute('data-user-id');
        if (qr && qr.trim()) {
            scannedQrCodes.add(qr.trim());
        }
        if (uid) {
            const numId = parseInt(uid, 10);
            if (!isNaN(numId) && !SCANNED_USER_IDS.some(x => Number(x) === numId)) {
                SCANNED_USER_IDS.push(numId);
            }
        }
    });

    const saved = localStorage.getItem('scan_state_' + MEETING_ID);
    if (saved) {
        try {
            const state = JSON.parse(saved);
            if (state.meetingId === MEETING_ID) {
                scannedUsers = state.scannedUsers || [];
                // Merge with server-side scanned IDs
                state.scannedUserIds?.forEach(id => {
                    const numId = parseInt(id, 10);
                    if (!isNaN(numId) && !SCANNED_USER_IDS.some(x => Number(x) === numId)) {
                        SCANNED_USER_IDS.push(numId);
                    }
                });
                state.scannedQrCodes?.forEach(qr => {
                    if (qr && String(qr).trim()) {
                        scannedQrCodes.add(String(qr).trim());
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
// PHOTO CAPTURE & AUTO-SAVE
// ============================================
function setupPhotoCapture() {
    const photoInput = document.getElementById('photoInput');
    if (photoInput) {
        photoInput.addEventListener('change', handlePhotoCapture);
    }
}

function initDocPhotoState() {
    if (typeof DOC_PHOTOS !== 'undefined' && Array.isArray(DOC_PHOTOS) && DOC_PHOTOS.length > 0) {
        updateDocPhotoUI(DOC_PHOTOS[DOC_PHOTOS.length - 1], DOC_PHOTOS, false);
    }
}

function capturePhoto() {
    const photoInput = document.getElementById('photoInput');
    if (photoInput) {
        photoInput.click();
    }
}

async function handlePhotoCapture(event) {
    const file = event.target.files[0];
    if (!file) return;
    
    const btnPhoto = document.getElementById('btnPhoto');
    const originalHtml = btnPhoto ? btnPhoto.innerHTML : '';
    
    if (btnPhoto) {
        btnPhoto.disabled = true;
        btnPhoto.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan...';
    }
    
    showToast('info', 'Menyimpan Foto...', 'Foto dokumentasi sedang diupload dan disimpan otomatis...');
    
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
            showToast('success', 'Foto Tersimpan! ✓', 'Dokumentasi berhasil disimpan langsung ke server.');
            playSound('success');
            
            const photoList = (result.photos && Array.isArray(result.photos) && result.photos.length > 0)
                ? result.photos
                : (result.url ? [result.url] : []);
            
            updateDocPhotoUI(result.url, photoList, true);
        } else {
            showToast('error', 'Gagal', result.message || 'Gagal menyimpan foto.');
            if (btnPhoto) {
                btnPhoto.innerHTML = originalHtml;
            }
        }
    } catch (error) {
        console.error('Photo upload error:', error);
        showToast('error', 'Koneksi Error', 'Gagal mengupload foto. Periksa koneksi internet.');
        if (btnPhoto) {
            btnPhoto.innerHTML = originalHtml;
        }
    } finally {
        if (btnPhoto) {
            btnPhoto.disabled = false;
        }
        // Reset input value so re-taking with the same device/camera triggers change event properly
        event.target.value = '';
    }
}

function updateDocPhotoUI(latestUrl, allPhotos = [], shouldPulse = false) {
    // 1. Update action button concisely (no double verbose text)
    const btnPhoto = document.getElementById('btnPhoto');
    const btnPhotoText = document.getElementById('btnPhotoText');
    if (btnPhoto) {
        btnPhoto.classList.add('btn-has-doc');
        if (btnPhotoText) {
            btnPhotoText.textContent = 'Ambil Ulang';
        } else {
            btnPhoto.innerHTML = '<i class="fas fa-camera"></i> <span id="btnPhotoText">Ambil Ulang</span>';
        }
    }

    // 2. Show minimal chip
    const section = document.getElementById('docPhotoSection');
    if (section) {
        section.style.display = 'flex';
        if (shouldPulse) {
            section.classList.remove('doc-pulse-update');
            void section.offsetWidth; // trigger reflow
            section.classList.add('doc-pulse-update');
        }
    }

    // 3. Resolve latest photo
    const photos = (allPhotos && allPhotos.length > 0) ? allPhotos : (latestUrl ? [latestUrl] : []);
    const currentPhotoUrl = photos[photos.length - 1] || latestUrl;
    window.LATEST_DOC_PHOTO_URL = currentPhotoUrl;

    // 4. Update thumbnail
    const thumbnailsContainer = document.getElementById('docPhotoThumbnails');
    if (thumbnailsContainer && currentPhotoUrl) {
        thumbnailsContainer.innerHTML = `<img src="${currentPhotoUrl}" alt="Dokumentasi" class="doc-chip-thumb">`;
    }

    // 5. Update subtitle
    const sub = document.getElementById('docChipSub');
    if (sub) {
        if (photos.length > 1) {
            sub.textContent = `${photos.length} foto tersimpan • Ketuk untuk lihat`;
        } else {
            sub.textContent = 'Ketuk untuk melihat ukuran penuh';
        }
    }
}

function openLatestDocPhoto() {
    const url = window.LATEST_DOC_PHOTO_URL
        || (typeof DOC_PHOTOS !== 'undefined' && Array.isArray(DOC_PHOTOS) && DOC_PHOTOS.length > 0 ? DOC_PHOTOS[DOC_PHOTOS.length - 1] : null);
    if (url) {
        previewDocPhoto(url);
    }
}

function previewDocPhoto(url) {
    const modal = document.getElementById('docPhotoModal');
    const img = document.getElementById('docModalImage');
    if (modal && img) {
        img.src = url;
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
}

function closeDocPhotoModalDirect() {
    const modal = document.getElementById('docPhotoModal');
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = '';
    }
}

function closeDocPhotoModal(e) {
    if (e.target.id === 'docPhotoModal') {
        closeDocPhotoModalDirect();
    }
}

window.capturePhoto = capturePhoto;
window.previewDocPhoto = previewDocPhoto;
window.openLatestDocPhoto = openLatestDocPhoto;
window.closeDocPhotoModalDirect = closeDocPhotoModalDirect;
window.closeDocPhotoModal = closeDocPhotoModal;
window.toggleTorch = toggleTorch;

// ============================================
// FINISH ATTENDANCE
// ============================================
async function finishAttendance() {
    const confirmMessage = 'Apakah Anda yakin ingin menyelesaikan absensi? Peserta yang belum scan akan dicatat sebagai Alpha.';

    const executeFinish = async () => {
        try {
            const response = await fetch(BASE_URL + '/meeting/process/process_scan.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': CSRF_TOKEN
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
                showToast('success', 'Selesai!', 'Absensi berhasil diselesaikan.');
                
                let targetUrl = result.redirect_url;
                if (!targetUrl) {
                    if (typeof PUBLIC_HASIL_URL !== 'undefined' && PUBLIC_HASIL_URL) {
                        targetUrl = PUBLIC_HASIL_URL;
                    } else if (typeof MEETING_DATA !== 'undefined' && MEETING_DATA.unique_link) {
                        targetUrl = BASE_URL + '/rapat/' + MEETING_DATA.unique_link + '/hasil';
                    } else {
                        targetUrl = BASE_URL + '/meeting/detail.php?id=' + MEETING_ID;
                    }
                }
                
                setTimeout(() => {
                    window.location.href = targetUrl;
                }, 1200);
            } else {
                showToast('error', 'Error', result.message || 'Gagal menyelesaikan absensi.');
            }
        } catch (error) {
            showToast('error', 'Error', 'Gagal menghubungi server.');
        }
    };

    if (typeof window.showConfirmPopup === 'function') {
        window.showConfirmPopup({
            title: 'Selesaikan Absensi',
            message: confirmMessage,
            confirmText: 'Ya, Selesaikan',
            cancelText: 'Batal',
            onConfirm: executeFinish
        });
        return;
    }

    if (confirm(confirmMessage)) {
        executeFinish();
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
