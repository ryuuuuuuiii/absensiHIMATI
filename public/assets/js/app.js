/**
 * Main Application JavaScript
 * Absensi HIMA TI Politeknik Hasnur
 */

// ============================================
// SIDEBAR TOGGLE
// ============================================
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    sidebar.classList.toggle('active');
    overlay.classList.toggle('active');
}

// Close sidebar on outside click (mobile)
document.addEventListener('click', (e) => {
    const sidebar = document.getElementById('sidebar');
    const toggle = document.querySelector('.menu-toggle');
    if (sidebar && sidebar.classList.contains('active') && 
        !sidebar.contains(e.target) && !toggle.contains(e.target)) {
        toggleSidebar();
    }
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
    // Auto-dismiss alerts after 5 seconds
    document.querySelectorAll('.alert').forEach(alert => {
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
