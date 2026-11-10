/**
 * Main Application JavaScript
 * Absensi HIMA TI Politeknik Hasnur
 */

// ============================================
// SIDEBAR TOGGLE
// ============================================
function toggleSidebar(e) {
    if (e && typeof e.stopPropagation === 'function') {
        e.stopPropagation();
    }
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    if (!sidebar) return;
    const isActive = sidebar.classList.toggle('active');
    if (overlay) {
        overlay.classList.toggle('active', isActive);
    }
    if (window.innerWidth <= 768) {
        document.body.style.overflow = isActive ? 'hidden' : '';
    }
}

// Close sidebar on outside click (mobile)
document.addEventListener('click', (e) => {
    const sidebar = document.getElementById('sidebar');
    if (!sidebar || !sidebar.classList.contains('active')) return;

    // Ignore clicks inside sidebar or on any toggle element
    if (sidebar.contains(e.target) || 
        e.target.closest('.menu-toggle') || 
        e.target.closest('.tab-menu') || 
        e.target.closest('.sidebar-close-btn')) {
        return;
    }

    toggleSidebar();
});

// ============================================
// TOAST NOTIFICATIONS
// ============================================
function showToast(type, title, message, duration = 4000) {
    const container = document.getElementById('toastContainer');
    if (!container) return;

    const icons = {
        success: 'fas fa-check-circle',
        error: 'fas fa-exclamation-circle',
        warning: 'fas fa-exclamation-triangle',
        info: 'fas fa-info-circle'
    };

    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.innerHTML = `
        <div class="toast-icon"><i class="${icons[type] || icons.info}"></i></div>
        <div class="toast-body">
            <div class="toast-title">${title}</div>
            <div class="toast-message">${message}</div>
        </div>
        <button class="toast-close" onclick="closeToast(this.parentElement)">
            <i class="fas fa-times"></i>
        </button>
    `;

    container.appendChild(toast);

    // Auto remove
    setTimeout(() => closeToast(toast), duration);
}

function closeToast(toast) {
    if (!toast || toast.classList.contains('toast-out')) return;
    toast.classList.add('toast-out');
    setTimeout(() => toast.remove(), 300);
}

// ============================================
// USER MENU TOGGLE
// ============================================
function toggleUserMenu() {
    // Simple implementation - can be extended with dropdown
    const userEl = document.querySelector('.header-user');
    if (userEl) {
        userEl.classList.toggle('active');
    }
}

// ============================================
// FORM HELPERS
// ============================================
function toggleFieldVisibility(fieldId, show) {
    const field = document.getElementById(fieldId);
    if (field) {
        field.style.display = show ? 'block' : 'none';
        if (!show) {
            // Clear inputs when hiding
            const inputs = field.querySelectorAll('input, textarea, select');
            inputs.forEach(input => {
                if (input.type === 'checkbox' || input.type === 'radio') {
                    input.checked = false;
                } else {
                    input.value = '';
                }
            });
        }
    }
}

// ============================================
// COPY TO CLIPBOARD
// ============================================
function copyToClipboard(text, button) {
    navigator.clipboard.writeText(text).then(() => {
        const originalText = button.innerHTML;
        button.innerHTML = '<i class="fas fa-check"></i> Tersalin!';
        button.style.background = 'var(--success)';
        setTimeout(() => {
            button.innerHTML = originalText;
            button.style.background = '';
        }, 2000);
    }).catch(() => {
        // Fallback for older browsers
        const textarea = document.createElement('textarea');
        textarea.value = text;
        document.body.appendChild(textarea);
        textarea.select();
        document.execCommand('copy');
        document.body.removeChild(textarea);
        showToast('success', 'Berhasil', 'Link berhasil disalin!');
    });
}

// ============================================
// CONFIRM DELETE
// ============================================
function confirmDelete(message, url) {
    if (typeof window.showConfirmPopup === 'function') {
        window.showConfirmPopup({
            title: 'Konfirmasi Hapus Data',
            message: message || 'Apakah Anda yakin ingin menghapus data ini?',
            confirmText: 'Ya, Hapus',
            cancelText: 'Batal',
            onConfirm: function() {
                window.location.href = url;
            }
        });
        return;
    }
    if (confirm(message || 'Apakah Anda yakin ingin menghapus?')) {
        window.location.href = url;
    }
}

// ============================================
// FORMAT DATE HELPER
// ============================================
function formatDate(dateStr) {
    const months = [
        'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    const date = new Date(dateStr);
    return `${date.getDate()} ${months[date.getMonth()]} ${date.getFullYear()}`;
}

function formatTime(date) {
    return date.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
}

// ============================================
// FETCH HELPER
// ============================================
async function fetchAPI(url, options = {}) {
    try {
        const defaultOptions = {
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        };

        const response = await fetch(url, { ...defaultOptions, ...options });
        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.message || 'Terjadi kesalahan');
        }

        return data;
    } catch (error) {
        console.error('API Error:', error);
        showToast('error', 'Error', error.message);
        throw error;
    }
}

// ============================================
// FILE SIZE FORMAT
// ============================================
function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

// ============================================
// IMAGE PREVIEW ON FILE INPUT
// ============================================
function setupImagePreview(inputId, previewId) {
    const input = document.getElementById(inputId);
    const preview = document.getElementById(previewId);
    
    if (!input || !preview) return;
    
    input.addEventListener('change', function() {
        preview.innerHTML = '';
        
        Array.from(this.files).forEach(file => {
            if (!file.type.startsWith('image/')) return;
            
            const reader = new FileReader();
            reader.onload = (e) => {
                const img = document.createElement('img');
                img.src = e.target.result;
                preview.appendChild(img);
            };
            reader.readAsDataURL(file);
        });
    });
}

// ============================================
// DRAG & DROP FILE UPLOAD
// ============================================
function setupDragDrop(dropZoneId) {
    const dropZone = document.getElementById(dropZoneId);
    if (!dropZone) return;
    
    ['dragenter', 'dragover'].forEach(event => {
        dropZone.addEventListener(event, (e) => {
            e.preventDefault();
            dropZone.classList.add('dragover');
        });
    });
    
    ['dragleave', 'drop'].forEach(event => {
        dropZone.addEventListener(event, (e) => {
            e.preventDefault();
            dropZone.classList.remove('dragover');
        });
    });
}

// ============================================
// AUTO-DISMISS ALERTS
// ============================================
document.addEventListener('DOMContentLoaded', () => {
    // Auto-dismiss flash alerts after 5 seconds (skip permanent & static alerts)
    document.querySelectorAll('.alert').forEach(alert => {
        if (
            alert.classList.contains('alert-permanent') ||
            alert.classList.contains('alert-static') ||
            alert.hasAttribute('data-permanent') ||
            alert.closest('.card') ||
            alert.closest('.modal') ||
            alert.closest('form') ||
            alert.closest('.auth-card') ||
            alert.closest('.portal-card') ||
            alert.closest('.login-card')
        ) {
            return;
        }

        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-10px)';
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    });
    
    // Setup drag & drop zones
    document.querySelectorAll('.file-upload').forEach(zone => {
        setupDragDrop(zone.id);
    });
});

// ============================================
// DEBOUNCE HELPER
// ============================================
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// ============================================
// CLEAN MOBILE BOTTOM NAVIGATION BAR
// ============================================
function initBottomNav() {
    const nav = document.querySelector('.portal-bottom-nav');
    if (!nav) return;

    const tabs = nav.querySelectorAll('.portal-nav-tab');
    if (!tabs.length) return;

    window.updateFluidNavPosition = function(targetTab) {
        if (!targetTab) return;
        tabs.forEach(t => t.classList.remove('active'));
        targetTab.classList.add('active');
    };

    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            tabs.forEach(t => t.classList.remove('active'));
            this.classList.add('active');
        });
    });
}

document.addEventListener('DOMContentLoaded', initBottomNav);

// ============================================
// THEME SYSTEM (Pure Auto Follow Device)
// ============================================
(function() {
    try {
        localStorage.removeItem('himati_theme');
    } catch(e) {}

    function syncDeviceTheme(e) {
        var isDark = (e && typeof e.matches === 'boolean') 
            ? e.matches 
            : (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches);
        document.documentElement.setAttribute('data-theme', isDark ? 'dark' : 'light');
    }

    try {
        var mq = window.matchMedia('(prefers-color-scheme: dark)');
        if (mq.addEventListener) {
            mq.addEventListener('change', syncDeviceTheme);
        } else if (mq.addListener) {
            mq.addListener(syncDeviceTheme);
        }
    } catch(e) {}
})();

// ============================================
// PROFILE & FEEDBACK INTERACTION HELPERS (Global)
// ============================================
let lastProfileToggle = 0;
window.toggleEditSection = function(type) {
    const now = Date.now();
    if (now - lastProfileToggle < 250) {
        return;
    }
    lastProfileToggle = now;

    const editCollapse = document.getElementById('editProfileCollapse');
    const pwdCollapse = document.getElementById('changePasswordCollapse');
    const btnEdit = document.getElementById('btnToggleEdit');
    const btnPwd = document.getElementById('btnTogglePwd');

    if (!editCollapse && !pwdCollapse) return;

    if (typeof window.toggleFeedbackSection === 'function') {
        window.toggleFeedbackSection('close');
    }

    if (type === 'edit' && editCollapse) {
        const isOpen = editCollapse.classList.contains('is-open') || 
                       (editCollapse.style.display === 'block');

        if (isOpen) {
            editCollapse.style.display = 'none';
            editCollapse.classList.remove('is-open');
            if (btnEdit) btnEdit.classList.remove('active');
        } else {
            editCollapse.style.display = 'block';
            editCollapse.classList.add('is-open');
            if (pwdCollapse) {
                pwdCollapse.style.display = 'none';
                pwdCollapse.classList.remove('is-open');
            }

            if (btnEdit) btnEdit.classList.add('active');
            if (btnPwd) btnPwd.classList.remove('active');

            setTimeout(() => {
                editCollapse.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }, 60);
        }
    } else if (type === 'password' && pwdCollapse) {
        const isOpen = pwdCollapse.classList.contains('is-open') || 
                       (pwdCollapse.style.display === 'block');

        if (isOpen) {
            pwdCollapse.style.display = 'none';
            pwdCollapse.classList.remove('is-open');
            if (btnPwd) btnPwd.classList.remove('active');
        } else {
            pwdCollapse.style.display = 'block';
            pwdCollapse.classList.add('is-open');
            if (editCollapse) {
                editCollapse.style.display = 'none';
                editCollapse.classList.remove('is-open');
            }

            if (btnPwd) btnPwd.classList.add('active');
            if (btnEdit) btnEdit.classList.remove('active');

            setTimeout(() => {
                pwdCollapse.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }, 60);
        }
    } else if (type === 'close') {
        if (editCollapse) {
            editCollapse.style.display = 'none';
            editCollapse.classList.remove('is-open');
        }
        if (pwdCollapse) {
            pwdCollapse.style.display = 'none';
            pwdCollapse.classList.remove('is-open');
        }
        if (btnEdit) btnEdit.classList.remove('active');
        if (btnPwd) btnPwd.classList.remove('active');
    }
};

let lastFeedbackToggle = 0;
window.toggleFeedbackSection = function(action) {
    const now = Date.now();
    if (now - lastFeedbackToggle < 250) {
        return;
    }
    lastFeedbackToggle = now;

    const feedbackCollapse = document.getElementById('feedbackCollapse');
    const btnToggle = document.getElementById('btnToggleFeedback');
    const chevron = document.getElementById('feedbackChevron');

    if (!feedbackCollapse) return;

    const isOpen = feedbackCollapse.classList.contains('is-open') || 
                   (feedbackCollapse.style.display === 'block');

    if (action === 'close' || isOpen) {
        feedbackCollapse.style.display = 'none';
        feedbackCollapse.classList.remove('is-open');
        if (btnToggle) btnToggle.classList.remove('active');
        if (chevron) {
            chevron.style.transform = 'rotate(0deg)';
            chevron.style.color = '';
        }
    } else {
        window.toggleEditSection('close');

        feedbackCollapse.style.display = 'block';
        feedbackCollapse.classList.add('is-open');
        if (btnToggle) btnToggle.classList.add('active');
        if (chevron) {
            chevron.style.transform = 'rotate(180deg)';
            chevron.style.color = 'var(--blue-primary)';
        }
        setTimeout(() => {
            feedbackCollapse.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }, 60);
    }
};

window.previewSelectedPhoto = function(input) {
    const previewWrap = document.getElementById('photoPreviewWrap');
    const previewImg = document.getElementById('photoPreviewImg');

    if (input && input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            if (previewImg) previewImg.src = e.target.result;
            if (previewWrap) previewWrap.style.display = 'flex';
        };
        reader.readAsDataURL(input.files[0]);
    } else {
        if (previewWrap) previewWrap.style.display = 'none';
    }
};

window.previewFeedbackScreenshot = function(input) {
    const previewBox = document.getElementById('feedbackPreviewBox');
    const previewImg = document.getElementById('feedbackPreviewImg');
    const previewName = document.getElementById('feedbackPreviewName');
    const previewSize = document.getElementById('feedbackPreviewSize');
    const uploadZone = document.getElementById('feedbackUploadZone');

    if (input && input.files && input.files[0]) {
        const file = input.files[0];

        if (!file.type.startsWith('image/')) {
            alert('File yang dipilih harus berupa gambar (JPG, PNG, WEBP).');
            input.value = '';
            return;
        }

        if (file.size > 5 * 1024 * 1024) {
            alert('Ukuran file gambar maksimal 5MB.');
            input.value = '';
            return;
        }

        const reader = new FileReader();
        reader.onload = function(e) {
            if (previewImg) previewImg.src = e.target.result;
            if (previewName) previewName.textContent = file.name;
            const sizeKB = (file.size / 1024).toFixed(1);
            if (previewSize) previewSize.textContent = sizeKB > 1024 ? (sizeKB / 1024).toFixed(2) + ' MB' : sizeKB + ' KB';

            if (previewBox) previewBox.style.display = 'flex';
            if (uploadZone) uploadZone.style.display = 'none';
        };
        reader.readAsDataURL(file);
    } else {
        window.clearFeedbackScreenshot();
    }
};

window.clearFeedbackScreenshot = function() {
    const fileInput = document.getElementById('feedbackScreenshot');
    const previewBox = document.getElementById('feedbackPreviewBox');
    const previewImg = document.getElementById('feedbackPreviewImg');
    const uploadZone = document.getElementById('feedbackUploadZone');

    if (fileInput) fileInput.value = '';
    if (previewImg) previewImg.src = '';
    if (previewBox) previewBox.style.display = 'none';
    if (uploadZone) uploadZone.style.display = 'flex';
};

window.openScreenshotModal = function(imgUrl, title) {
    const modal = document.getElementById('feedbackModal');
    const img = document.getElementById('feedbackModalImg');
    const titleEl = document.getElementById('feedbackModalTitle');
    const dlBtn = document.getElementById('feedbackModalDownload');

    if (!modal || !img) return;

    img.src = imgUrl;
    if (titleEl && title) {
        titleEl.innerHTML = '<i class="fas fa-image text-primary"></i> ' + title;
    }
    if (dlBtn) {
        dlBtn.href = imgUrl;
    }

    modal.style.display = 'flex';
};

window.closeScreenshotModalDirect = function() {
    const modal = document.getElementById('feedbackModal');
    if (modal) modal.style.display = 'none';
};

window.closeScreenshotModal = function(event) {
    if (event.target.id === 'feedbackModal') {
        window.closeScreenshotModalDirect();
    }
};



