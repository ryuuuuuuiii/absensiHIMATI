@if (auth('member')->check())
<style>
.webpush-card {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    flex-wrap: wrap;
    background: var(--bg-surface, #1E293B);
    border: 1.5px solid rgba(48, 175, 255, 0.35);
    border-radius: var(--radius-xl, 16px);
    padding: 14px 16px;
    margin-bottom: 16px;
    box-shadow: 0 4px 16px rgba(15, 23, 42, 0.08);
    transition: border-color 0.2s ease, background 0.2s ease;
}

.webpush-card.is-subscribed {
    border-color: rgba(16, 185, 129, 0.45);
}

.webpush-card.is-denied,
.webpush-card.is-unsupported {
    border-color: rgba(245, 158, 11, 0.4);
}

.webpush-card-left {
    display: flex;
    align-items: center;
    gap: 12px;
    flex: 1 1 240px;
    min-width: 0;
}

.webpush-icon-box {
    width: 42px;
    height: 42px;
    border-radius: 12px;
    background: rgba(48, 175, 255, 0.16);
    color: #30AFFF;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    flex-shrink: 0;
}

.webpush-card.is-subscribed .webpush-icon-box {
    background: rgba(16, 185, 129, 0.16);
    color: #10B981;
}

.webpush-card.is-denied .webpush-icon-box,
.webpush-card.is-unsupported .webpush-icon-box {
    background: rgba(245, 158, 11, 0.16);
    color: #F59E0B;
}

.webpush-text-box {
    min-width: 0;
    flex: 1;
}

.webpush-title-row {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
    margin-bottom: 3px;
}

.webpush-title {
    font-size: 0.94rem;
    font-weight: 700;
    color: var(--text-primary);
}

.webpush-status-pill {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 2px 8px;
    border-radius: 999px;
    font-size: 0.72rem;
    font-weight: 700;
    background: rgba(48, 175, 255, 0.15);
    color: #30AFFF;
}

.webpush-card.is-subscribed .webpush-status-pill {
    background: rgba(16, 185, 129, 0.16);
    color: #10B981;
}

.webpush-card.is-denied .webpush-status-pill,
.webpush-card.is-unsupported .webpush-status-pill {
    background: rgba(245, 158, 11, 0.16);
    color: #F59E0B;
}

.webpush-desc {
    margin: 0;
    font-size: 0.8rem;
    color: var(--text-muted);
    line-height: 1.45;
}

.webpush-actions {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
    flex-shrink: 0;
}

.webpush-dismiss-btn {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    border: 1px solid var(--border-color, rgba(148, 163, 184, 0.3));
    background: transparent;
    color: var(--text-muted);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s ease;
}

.webpush-dismiss-btn:hover {
    background: rgba(148, 163, 184, 0.15);
    color: var(--text-primary);
}

@media (max-width: 600px) {
    .webpush-card {
        padding: 12px 14px;
    }
    .webpush-actions {
        width: 100%;
        justify-content: flex-end;
    }
}
</style>

<script>
(function () {
    if (window.HimatiWebPush) {
        return;
    }

    const CONFIG_URL = @json(route('portal.push.config'));
    const SUBSCRIBE_URL = @json(route('portal.push.subscribe'));
    const UNSUBSCRIBE_URL = @json(route('portal.push.unsubscribe'));
    const TEST_URL = @json(route('portal.push.test'));
    const SW_URL = @json(asset('sw.js'));
    const DISMISS_KEY = 'himati_webpush_banner_dismissed_v1';
    const SYNC_KEY = 'himati_webpush_synced_member_{{ auth('member')->id() }}';

    let swRegistration = null;
    let cachedPublicKey = null;

    function getCsrfToken() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    function showFeedback(type, message) {
        if (typeof window.showPopupAlert === 'function') {
            const title = type === 'success' ? 'Berhasil' : (type === 'warning' ? 'Perhatian' : 'Gagal');
            window.showPopupAlert(type, title, message);
            return;
        }
        alert(message);
    }

    function isIosDevice() {
        const ua = navigator.userAgent || '';
        return /iPad|iPhone|iPod/.test(ua) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
    }

    function isStandalonePwa() {
        return window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
    }

    function urlBase64ToUint8Array(base64String) {
        const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
        const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
        const rawData = window.atob(base64);
        const outputArray = new Uint8Array(rawData.length);
        for (let i = 0; i < rawData.length; ++i) {
            outputArray[i] = rawData.charCodeAt(i);
        }
        return outputArray;
    }

    async function ensureServiceWorker() {
        if (!('serviceWorker' in navigator)) {
            return null;
        }
        if (swRegistration) {
            return swRegistration;
        }
        try {
            swRegistration = await navigator.serviceWorker.register(SW_URL, { scope: '/' });
            await navigator.serviceWorker.ready;
            return swRegistration;
        } catch (err) {
            console.warn('WebPush SW register failed:', err);
            return null;
        }
    }

    async function fetchServerConfig() {
        if (cachedPublicKey) {
            return { enabled: true, public_key: cachedPublicKey };
        }
        const res = await fetch(CONFIG_URL, {
            headers: { 'Accept': 'application/json' },
            credentials: 'same-origin',
        });
        if (!res.ok) {
            throw new Error('Gagal memuat konfigurasi notifikasi dari server.');
        }
        const data = await res.json();
        if (data.public_key) {
            cachedPublicKey = data.public_key;
        }
        return data;
    }

    async function syncSubscriptionToServer(subscription) {
        const json = subscription.toJSON();
        if (!json || !json.endpoint || !json.keys || !json.keys.p256dh || !json.keys.auth) {
            throw new Error('Data langganan browser tidak lengkap.');
        }

        const res = await fetch(SUBSCRIBE_URL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken(),
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                endpoint: json.endpoint,
                keys: {
                    p256dh: json.keys.p256dh,
                    auth: json.keys.auth,
                },
                content_encoding: 'aes128gcm',
            }),
        });

        const data = await res.json();
        if (!res.ok || !data.success) {
            throw new Error(data.message || 'Gagal menyimpan langganan notifikasi.');
        }
        return data;
    }

    async function getCurrentState() {
        const supported = ('serviceWorker' in navigator) && ('PushManager' in window) && ('Notification' in window);

        if (isIosDevice() && !isStandalonePwa()) {
            return { status: 'ios_needs_pwa', subscription: null };
        }

        if (!window.isSecureContext) {
            return { status: 'insecure', subscription: null };
        }

        if (!supported) {
            return { status: 'unsupported', subscription: null };
        }

        if (Notification.permission === 'denied') {
            return { status: 'denied', subscription: null };
        }

        const reg = await ensureServiceWorker();
        if (!reg || !reg.pushManager) {
            return { status: 'unsupported', subscription: null };
        }

        const existingSub = await reg.pushManager.getSubscription();
        if (existingSub && Notification.permission === 'granted') {
            // Sinkronkan diam-diam sekali per sesi agar perubahan akun di perangkat yang sama tetap terhubung
            try {
                if (!sessionStorage.getItem(SYNC_KEY)) {
                    await syncSubscriptionToServer(existingSub);
                    sessionStorage.setItem(SYNC_KEY, '1');
                }
            } catch (e) {}
            return { status: 'subscribed', subscription: existingSub };
        }

        return { status: 'prompt', subscription: null };
    }

    function applyStateToCard(card, state) {
        const isCompact = card.dataset.compact === '1';
        const badge = card.querySelector('.js-webpush-badge');
        const desc = card.querySelector('.js-webpush-desc');
        const btnEnable = card.querySelector('.js-webpush-enable');
        const btnTest = card.querySelector('.js-webpush-test');
        const btnDisable = card.querySelector('.js-webpush-disable');
        const icon = card.querySelector('.js-webpush-icon i');

        card.classList.remove('is-subscribed', 'is-denied', 'is-unsupported');

        if (state.status === 'subscribed') {
            if (isCompact) {
                card.style.display = 'none';
                return;
            }
            card.style.display = 'flex';
            card.classList.add('is-subscribed');
            if (icon) icon.className = 'fas fa-bell';
            if (badge) {
                badge.textContent = 'Aktif di Perangkat Ini';
                badge.style.display = 'inline-flex';
            }
            if (desc) {
                desc.textContent = 'Notifikasi rapat, kegiatan, dan pengumuman baru akan langsung muncul di layar HP/browser ini.';
            }
            if (btnEnable) btnEnable.style.display = 'none';
            if (btnTest) btnTest.style.display = 'inline-flex';
            if (btnDisable) btnDisable.style.display = 'inline-flex';
            return;
        }

        if (state.status === 'ios_needs_pwa') {
            if (isCompact && localStorage.getItem(DISMISS_KEY) === '1') {
                card.style.display = 'none';
                return;
            }
            card.style.display = 'flex';
            card.classList.add('is-unsupported');
            if (icon) icon.className = 'fas fa-mobile-alt';
            if (badge) {
                badge.textContent = 'Khusus iPhone / iPad';
                badge.style.display = 'inline-flex';
            }
            if (desc) {
                desc.textContent = 'Di Safari iOS, tekan tombol Share lalu pilih "Add to Home Screen" (Tambah ke Layar Utama), kemudian buka aplikasi dari ikon tersebut untuk mengaktifkan notifikasi HP.';
            }
            if (btnEnable) btnEnable.style.display = 'none';
            if (btnTest) btnTest.style.display = 'none';
            if (btnDisable) btnDisable.style.display = 'none';
            return;
        }

        if (state.status === 'denied') {
            if (isCompact) {
                card.style.display = 'none';
                return;
            }
            card.style.display = 'flex';
            card.classList.add('is-denied');
            if (icon) icon.className = 'fas fa-bell-slash';
            if (badge) {
                badge.textContent = 'Diblokir Browser';
                badge.style.display = 'inline-flex';
            }
            if (desc) {
                desc.textContent = 'Izin notifikasi saat ini diblokir di browser Anda. Buka Pengaturan Situs (Site Settings) pada browser untuk mengizinkan notifikasi.';
            }
            if (btnEnable) btnEnable.style.display = 'none';
            if (btnTest) btnTest.style.display = 'none';
            if (btnDisable) btnDisable.style.display = 'none';
            return;
        }

        if (state.status === 'unsupported' || state.status === 'insecure') {
            if (isCompact) {
                card.style.display = 'none';
                return;
            }
            card.style.display = 'flex';
            card.classList.add('is-unsupported');
            if (icon) icon.className = 'fas fa-info-circle';
            if (badge) {
                badge.textContent = state.status === 'insecure' ? 'Butuh HTTPS' : 'Tidak Didukung';
                badge.style.display = 'inline-flex';
            }
            if (desc) {
                desc.textContent = state.status === 'insecure'
                    ? 'Notifikasi push hanya bekerja pada koneksi HTTPS (seperti absensi-himati.vercel.app) atau localhost.'
                    : 'Browser Anda saat ini belum mendukung Web Push Notification. Gunakan Chrome, Edge, atau Safari iOS 16.4+ (PWA).';
            }
            if (btnEnable) btnEnable.style.display = 'none';
            if (btnTest) btnTest.style.display = 'none';
            if (btnDisable) btnDisable.style.display = 'none';
            return;
        }

        // Status: prompt (siap diaktifkan)
        if (isCompact && localStorage.getItem(DISMISS_KEY) === '1') {
            card.style.display = 'none';
            return;
        }
        card.style.display = 'flex';
        if (icon) icon.className = 'fas fa-bell';
        if (badge) {
            badge.textContent = 'Belum Aktif';
            badge.style.display = 'inline-flex';
        }
        if (desc) {
            desc.textContent = 'Aktifkan agar pemberitahuan rapat & kegiatan baru langsung muncul di status bar HP Anda meski browser sedang ditutup.';
        }
        if (btnEnable) btnEnable.style.display = 'inline-flex';
        if (btnTest) btnTest.style.display = 'none';
        if (btnDisable) btnDisable.style.display = 'none';
    }

    async function refreshUI() {
        const cards = document.querySelectorAll('.js-webpush-card');
        if (!cards.length) {
            return;
        }
        const state = await getCurrentState();
        cards.forEach((card) => applyStateToCard(card, state));
    }

    async function enable(buttonEl) {
        const originalHtml = buttonEl ? buttonEl.innerHTML : null;
        if (buttonEl) {
            buttonEl.disabled = true;
            buttonEl.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <span>Mengaktifkan...</span>';
        }

        try {
            const permission = await Notification.requestPermission();
            if (permission !== 'granted') {
                await refreshUI();
                showFeedback('warning', 'Izin notifikasi belum diberikan. Klik "Izinkan / Allow" pada popup browser agar notifikasi HP bisa masuk.');
                return;
            }

            const reg = await ensureServiceWorker();
            if (!reg || !reg.pushManager) {
                throw new Error('Service Worker tidak tersedia di browser ini.');
            }

            const cfg = await fetchServerConfig();
            if (!cfg.enabled || !cfg.public_key) {
                throw new Error('Layanan Web Push server belum siap.');
            }

            let sub = await reg.pushManager.getSubscription();
            if (!sub) {
                sub = await reg.pushManager.subscribe({
                    userVisibleOnly: true,
                    applicationServerKey: urlBase64ToUint8Array(cfg.public_key),
                });
            }

            const saved = await syncSubscriptionToServer(sub);
            sessionStorage.setItem(SYNC_KEY, '1');
            localStorage.removeItem(DISMISS_KEY);
            await refreshUI();
            showFeedback('success', saved.message || 'Notifikasi HP berhasil diaktifkan!');
        } catch (err) {
            console.error('WebPush enable error:', err);
            showFeedback('danger', err.message || 'Gagal mengaktifkan notifikasi HP.');
        } finally {
            if (buttonEl) {
                buttonEl.disabled = false;
                if (originalHtml) buttonEl.innerHTML = originalHtml;
            }
        }
    }

    async function disable(buttonEl) {
        const originalHtml = buttonEl ? buttonEl.innerHTML : null;
        if (buttonEl) {
            buttonEl.disabled = true;
            buttonEl.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <span>Memproses...</span>';
        }

        try {
            const reg = await ensureServiceWorker();
            const sub = reg ? await reg.pushManager.getSubscription() : null;
            if (sub) {
                const endpoint = sub.endpoint;
                await sub.unsubscribe();
                await fetch(UNSUBSCRIBE_URL, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': getCsrfToken(),
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({ endpoint }),
                });
            }
            sessionStorage.removeItem(SYNC_KEY);
            await refreshUI();
            showFeedback('success', 'Notifikasi HP pada perangkat ini telah dimatikan.');
        } catch (err) {
            console.error('WebPush disable error:', err);
            showFeedback('danger', 'Gagal mematikan notifikasi HP.');
        } finally {
            if (buttonEl) {
                buttonEl.disabled = false;
                if (originalHtml) buttonEl.innerHTML = originalHtml;
            }
        }
    }

    async function sendTest(buttonEl) {
        const originalHtml = buttonEl ? buttonEl.innerHTML : null;
        if (buttonEl) {
            buttonEl.disabled = true;
            buttonEl.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <span>Mengirim...</span>';
        }

        try {
            const res = await fetch(TEST_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken(),
                },
                credentials: 'same-origin',
            });
            const data = await res.json();
            if (!res.ok || !data.success) {
                throw new Error(data.message || 'Gagal mengirim notifikasi tes.');
            }
            showFeedback('success', data.message);
        } catch (err) {
            showFeedback('warning', err.message || 'Gagal mengirim notifikasi tes.');
        } finally {
            if (buttonEl) {
                buttonEl.disabled = false;
                if (originalHtml) buttonEl.innerHTML = originalHtml;
            }
        }
    }

    function dismissBanner() {
        localStorage.setItem(DISMISS_KEY, '1');
        document.querySelectorAll('.js-webpush-card[data-compact="1"]').forEach((el) => {
            el.style.display = 'none';
        });
    }

    window.HimatiWebPush = {
        enable,
        disable,
        sendTest,
        dismissBanner,
        refreshUI,
    };

    document.addEventListener('DOMContentLoaded', function () {
        refreshUI();

        const contentLayer = document.getElementById('portal-content-layer');
        if (contentLayer && typeof MutationObserver !== 'undefined') {
            const observer = new MutationObserver(() => {
                if (contentLayer.querySelector('.js-webpush-card')) {
                    refreshUI();
                }
            });
            observer.observe(contentLayer, { childList: true });
        }
    });
})();
</script>
@endif

