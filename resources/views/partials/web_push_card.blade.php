@php
    $compact = $compact ?? false;
@endphp

<div class="webpush-card js-webpush-card {{ $compact ? 'webpush-card--compact' : 'webpush-card--full' }}"
     data-compact="{{ $compact ? '1' : '0' }}"
     style="{{ $compact ? 'display: none;' : '' }}">
    <div class="webpush-card-left">
        <div class="webpush-icon-box js-webpush-icon">
            <i class="fas fa-bell"></i>
        </div>
        <div class="webpush-text-box">
            <div class="webpush-title-row">
                <strong class="webpush-title js-webpush-title">
                    {{ $compact ? 'Aktifkan Notifikasi Rapat di HP' : 'Notifikasi Layar HP / Browser' }}
                </strong>
                <span class="webpush-status-pill js-webpush-badge" style="display: none;">Memeriksa...</span>
            </div>
            <p class="webpush-desc js-webpush-desc">
                Dapatkan pemberitahuan langsung di layar HP setiap kali ada rapat, kegiatan, atau pengumuman baru meskipun browser ditutup.
            </p>
        </div>
    </div>
    <div class="webpush-actions js-webpush-actions">
        <button type="button" class="btn btn-primary btn-sm js-webpush-enable" onclick="window.HimatiWebPush && window.HimatiWebPush.enable(this)">
            <i class="fas fa-bell"></i> <span>Aktifkan</span>
        </button>
        <button type="button" class="btn btn-secondary btn-sm js-webpush-test" style="display: none;" onclick="window.HimatiWebPush && window.HimatiWebPush.sendTest(this)">
            <i class="fas fa-paper-plane"></i> <span>Tes Notif</span>
        </button>
        <button type="button" class="btn btn-ghost btn-sm js-webpush-disable" style="display: none;" onclick="window.HimatiWebPush && window.HimatiWebPush.disable(this)">
            <i class="fas fa-bell-slash"></i> <span>Matikan</span>
        </button>
        @if ($compact)
            <button type="button" class="webpush-dismiss-btn js-webpush-dismiss" title="Tutup sementara" aria-label="Tutup" onclick="window.HimatiWebPush && window.HimatiWebPush.dismissBanner()">
                <i class="fas fa-times"></i>
            </button>
        @endif
    </div>
</div>

