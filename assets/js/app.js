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
// FLUID ANIMATED BOTTOM NAVIGATION (Mobile View)
// ============================================
function initFluidBottomNav() {
    const nav = document.querySelector('.portal-bottom-nav');
    if (!nav) return;

    const fillPath = nav.querySelector('.portal-nav-fluid-fill');
    const strokePath = nav.querySelector('.portal-nav-fluid-stroke');
    if (!fillPath || !strokePath) return;

    const tabs = nav.querySelectorAll('.portal-nav-tab');
    if (!tabs.length) return;

    let activeTab = nav.querySelector('.portal-nav-tab.active');
    if (!activeTab) activeTab = tabs[0];

    let currentX = 0;
    let animId = null;

    function getTabCenter(tab) {
        const navRect = nav.getBoundingClientRect();
        const tabRect = tab.getBoundingClientRect();
        if (tabRect.width === 0 && nav.clientWidth > 0) {
            const idx = Array.prototype.indexOf.call(tabs, tab);
            return (nav.clientWidth / tabs.length) * (idx + 0.5);
        }
        return (tabRect.left + tabRect.width / 2) - navRect.left;
    }

    function renderPath(x) {
        const w = nav.clientWidth;
        if (w <= 0) return;

        const baseline = 24;
        const peakY = 3;
        const r = 44;
        const cp = 24;

        const x0 = Math.max(0, x - r);
        const x1 = x - cp;
        const x2 = x - cp;
        const x3 = x;
        const x4 = x + cp;
        const x5 = x + cp;
        const x6 = Math.min(w, x + r);

        const strokeD = `M 0 ${baseline} L ${x0} ${baseline} C ${x1} ${baseline} ${x2} ${peakY} ${x3} ${peakY} C ${x4} ${peakY} ${x5} ${baseline} ${x6} ${baseline} L ${w} ${baseline}`;
        const fillD = `${strokeD} L ${w} 86 L 0 86 Z`;

        strokePath.setAttribute('d', strokeD);
        fillPath.setAttribute('d', fillD);
    }

    function animateTo(targetX) {
        const startX = currentX;
        const diff = targetX - startX;
        const startTime = performance.now();
        const duration = 320;

        if (animId) cancelAnimationFrame(animId);

        function step(now) {
            const elapsed = now - startTime;
            const progress = Math.min(elapsed / duration, 1);
            const ease = 1 - Math.pow(1 - progress, 3);
            currentX = startX + diff * ease;
            renderPath(currentX);

            if (progress < 1) {
                animId = requestAnimationFrame(step);
            }
        }
        animId = requestAnimationFrame(step);
    }

    function updatePosition(animate) {
        const targetX = getTabCenter(activeTab);
        if (currentX === 0 || !animate) {
            currentX = targetX;
            renderPath(currentX);
        } else {
            animateTo(targetX);
        }
    }

    updatePosition(false);
    requestAnimationFrame(() => updatePosition(false));
    setTimeout(() => updatePosition(false), 80);

    window.addEventListener('resize', debounce(() => {
        updatePosition(false);
    }, 100));

    window.updateFluidNavPosition = function(targetTab) {
        if (!targetTab) return;
        tabs.forEach(t => t.classList.remove('active'));
        targetTab.classList.add('active');
        activeTab = targetTab;
        updatePosition(true);
    };

    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            tabs.forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            activeTab = this;
            updatePosition(true);
        });
    });
}

document.addEventListener('DOMContentLoaded', initFluidBottomNav);

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



