{{-- Universal Pop-up Alert & Confirmation Modal --}}
@php
    $hasPopup = false;
    $popupType = 'info';
    $popupTitle = 'Pemberitahuan';
    $popupMessage = '';
    $popupErrors = [];

    // Modal pop-up HANYA aktif jika diminta secara eksplisit via session('modal_alert') atau session('modal_warning').
    // Notifikasi umum seperti flash sukses, info, warning, dan validasi form tetap tampil sebagai banner inline di halaman.
    if (session('modal_warning')) {
        $hasPopup = true;
        $popupType = 'warning';
        $popupTitle = 'Peringatan';
        $popupMessage = session('modal_warning');
    } elseif (session('modal_alert')) {
        $hasPopup = true;
        $popupType = session('modal_type', 'info');
        $popupTitle = session('modal_title', 'Pemberitahuan');
        $popupMessage = session('modal_alert');
    }
@endphp

<!-- Global Alert Pop-up Modal (Pengganti window.alert browser) -->
<div class="popup-modal-overlay {{ $hasPopup ? 'active' : '' }}" id="globalAlertModal" onclick="closeGlobalAlertModal(event)" aria-modal="true" role="dialog">
    <div class="popup-modal-dialog" onclick="event.stopPropagation()">
        <div class="popup-modal-icon icon-{{ $popupType }}" id="globalAlertIcon">
            @if($popupType === 'warning')
                <i class="fas fa-exclamation-triangle"></i>
            @elseif($popupType === 'danger')
                <i class="fas fa-exclamation-circle"></i>
            @elseif($popupType === 'success')
                <i class="fas fa-check-circle"></i>
            @else
                <i class="fas fa-info-circle"></i>
            @endif
        </div>
        <h3 class="popup-modal-title" id="globalAlertTitle">{{ $popupTitle }}</h3>
        <div class="popup-modal-desc" id="globalAlertMessage">
            @if(!empty($popupErrors))
                <ul class="popup-modal-error-list">
                    @foreach($popupErrors as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            @else
                {!! nl2br(e($popupMessage)) !!}
            @endif
        </div>
        <div class="popup-modal-actions">
            <button type="button" class="btn btn-primary popup-modal-btn-confirm" id="globalAlertBtn" onclick="closeGlobalAlertModal()">
                {{ $popupType === 'warning' || $popupType === 'danger' ? 'Saya Mengerti' : 'Tutup' }}
            </button>
        </div>
    </div>
</div>

<!-- Global Confirmation Pop-up Modal (Pengganti window.confirm / confirm() browser) -->
<div class="popup-modal-overlay" id="globalConfirmModal" onclick="closeGlobalConfirmModal(event)" aria-modal="true" role="dialog">
    <div class="popup-modal-dialog" onclick="event.stopPropagation()">
        <div class="popup-modal-icon icon-warning" id="globalConfirmIcon">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        <h3 class="popup-modal-title" id="globalConfirmTitle">Konfirmasi Peringatan</h3>
        <div class="popup-modal-desc" id="globalConfirmMessage">
            Apakah Anda yakin ingin melanjutkan tindakan ini?
        </div>
        <div class="popup-modal-actions">
            <button type="button" class="btn btn-secondary popup-modal-btn-cancel" onclick="closeGlobalConfirmModal()" style="flex: 1;">
                Batal
            </button>
            <button type="button" class="btn btn-danger popup-modal-btn-confirm" id="globalConfirmBtnSubmit" style="flex: 1;">
                Ya, Lanjutkan
            </button>
        </div>
    </div>
</div>

<script>
    if (!window.__POPUP_ALERT_INIT) {
        window.__POPUP_ALERT_INIT = true;

        window.closeGlobalAlertModal = function(event) {
            if (event && event.target && event.target.id !== 'globalAlertModal') {
                return;
            }
            const modal = document.getElementById('globalAlertModal');
            if (modal) {
                modal.classList.remove('active');
                document.body.style.overflow = '';
            }
        };

        window.showPopupAlert = function(type, title, message) {
            const modal = document.getElementById('globalAlertModal');
            const icon = document.getElementById('globalAlertIcon');
            const titleEl = document.getElementById('globalAlertTitle');
            const msgEl = document.getElementById('globalAlertMessage');
            const btn = document.getElementById('globalAlertBtn');

            if (!modal) return;

            const iconMap = {
                warning: 'fas fa-exclamation-triangle',
                danger: 'fas fa-exclamation-circle',
                error: 'fas fa-exclamation-circle',
                success: 'fas fa-check-circle',
                info: 'fas fa-info-circle'
            };

            const safeType = type === 'error' ? 'danger' : (type || 'info');

            if (icon) {
                icon.className = 'popup-modal-icon icon-' + safeType;
                icon.innerHTML = '<i class="' + (iconMap[safeType] || iconMap.info) + '"></i>';
            }
            if (titleEl) {
                titleEl.textContent = title || (safeType === 'warning' ? 'Peringatan' : (safeType === 'danger' ? 'Perhatian' : (safeType === 'success' ? 'Berhasil' : 'Informasi')));
            }
            if (msgEl) {
                msgEl.innerHTML = typeof message === 'string' ? message : '';
            }
            if (btn) {
                btn.textContent = (safeType === 'warning' || safeType === 'danger') ? 'Saya Mengerti' : 'Tutup';
            }

            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
            if (btn) btn.focus();
        };

        window.closeGlobalConfirmModal = function(event) {
            if (event && event.target && event.target.id !== 'globalConfirmModal') {
                return;
            }
            const modal = document.getElementById('globalConfirmModal');
            if (modal) {
                modal.classList.remove('active');
                document.body.style.overflow = '';
            }
        };

        window.showConfirmPopup = function(options) {
            const opts = options || {};
            const modal = document.getElementById('globalConfirmModal');
            const titleEl = document.getElementById('globalConfirmTitle');
            const msgEl = document.getElementById('globalConfirmMessage');
            const submitBtn = document.getElementById('globalConfirmBtnSubmit');
            const cancelBtn = modal ? modal.querySelector('.popup-modal-btn-cancel') : null;
            const iconEl = document.getElementById('globalConfirmIcon');

            if (!modal) return;

            const fullText = ((opts.title || '') + ' ' + (opts.message || '')).toLowerCase();
            const isDanger = opts.isDanger !== undefined 
                ? opts.isDanger 
                : /hapus|batal|cabut|nonaktif|reset|keluarkan|delete/.test(fullText);

            if (iconEl) {
                iconEl.className = 'popup-modal-icon ' + (isDanger ? 'icon-danger' : 'icon-warning');
                iconEl.innerHTML = isDanger ? '<i class="fas fa-exclamation-circle"></i>' : '<i class="fas fa-exclamation-triangle"></i>';
            }

            if (titleEl) titleEl.textContent = opts.title || (isDanger ? 'Konfirmasi Peringatan' : 'Konfirmasi Tindakan');
            if (msgEl) msgEl.innerHTML = opts.message || 'Apakah Anda yakin ingin melanjutkan tindakan ini?';
            
            if (cancelBtn) {
                cancelBtn.textContent = opts.cancelText || 'Batal';
            }

            if (submitBtn) {
                submitBtn.className = 'btn popup-modal-btn-confirm ' + (isDanger ? 'btn-danger' : 'btn-primary');
                submitBtn.textContent = opts.confirmText || (isDanger ? 'Ya, Lanjutkan' : 'Ya, Setuju');
                submitBtn.onclick = function() {
                    modal.classList.remove('active');
                    document.body.style.overflow = '';
                    if (typeof opts.onConfirm === 'function') {
                        opts.onConfirm();
                    }
                };
            }

            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
            if (submitBtn) submitBtn.focus();
        };

        // Override native window.alert to replace browser "absensihimati says:" popup
        if (!window._nativeAlert) {
            window._nativeAlert = window.alert;
            window.alert = function(msg) {
                if (typeof window.showPopupAlert === 'function') {
                    const cleanMsg = typeof msg === 'string' ? msg.replace(/\n/g, '<br>') : String(msg);
                    window.showPopupAlert('warning', 'Peringatan', cleanMsg);
                } else if (typeof window._nativeAlert === 'function') {
                    window._nativeAlert(msg);
                }
            };
        }

        // Listen for ESC key to close popups
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                window.closeGlobalAlertModal();
                window.closeGlobalConfirmModal();
            }
        });

        // Intercept inline onclick="return confirm(...)" on buttons/links
        document.addEventListener('click', function(e) {
            const el = e.target.closest('[onclick*="confirm("]');
            if (!el || el.getAttribute('data-confirm-bypassed') === 'true') {
                return;
            }

            const onclickAttr = el.getAttribute('onclick') || '';
            const match = onclickAttr.match(/confirm\(\s*(['"`])([\s\S]*?)\1\s*\)/);
            if (match && match[2]) {
                e.preventDefault();
                e.stopImmediatePropagation();

                const cleanMsg = match[2]
                    .replace(/\\n/g, '<br>')
                    .replace(/\\'/g, "'")
                    .replace(/\\"/g, '"');

                window.showConfirmPopup({
                    message: cleanMsg,
                    onConfirm: function() {
                        el.setAttribute('data-confirm-bypassed', 'true');
                        const form = el.closest('form');
                        if (el.type === 'submit' && form) {
                            form.setAttribute('data-confirm-handled', 'true');
                            el.disabled = true;
                            el.style.opacity = '0.85';
                            el.style.pointerEvents = 'none';
                            el.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses...';
                            form.submit();
                        } else if (el.tagName === 'A' && el.href) {
                            window.location.href = el.href;
                        } else {
                            el.click();
                        }
                        setTimeout(() => el.removeAttribute('data-confirm-bypassed'), 1000);
                    }
                });
                return false;
            }
        }, true);

        // Intercept inline onsubmit="return confirm(...)" on forms
        document.addEventListener('submit', function(e) {
            const form = e.target;
            if (!form || form.getAttribute('data-confirm-handled') === 'true') {
                return;
            }

            const onsubmitAttr = form.getAttribute('onsubmit') || '';
            if (onsubmitAttr.includes('confirm(')) {
                const match = onsubmitAttr.match(/confirm\(\s*(['"`])([\s\S]*?)\1\s*\)/);
                if (match && match[2]) {
                    e.preventDefault();
                    e.stopImmediatePropagation();
                    const cleanMsg = match[2]
                        .replace(/\\n/g, '<br>')
                        .replace(/\\'/g, "'")
                        .replace(/\\"/g, '"');

                    window.showConfirmPopup({
                        message: cleanMsg,
                        onConfirm: function() {
                            form.setAttribute('data-confirm-handled', 'true');
                            const submitBtn = form.querySelector('button[type="submit"]');
                            if (submitBtn) {
                                submitBtn.disabled = true;
                                submitBtn.style.opacity = '0.85';
                                submitBtn.style.pointerEvents = 'none';
                                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses...';
                            }
                            form.submit();
                        }
                    });
                    return false;
                }
            }
        }, true);
    }

    @if($hasPopup)
        document.body.style.overflow = 'hidden';
    @endif
</script>
