@extends('layouts.portal')

@section('title', 'Pengumuman & Pesan')
@section('page_icon')
    <i class="fas fa-bullhorn"></i>
@endsection

@push('styles')
<style>
.notif-page-wrap {
    max-width: 760px;
    margin: 0 auto;
}

.notif-topbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    margin-bottom: 14px;
    flex-wrap: wrap;
}

.notif-back-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 14px;
    border-radius: var(--radius-lg, 10px);
    background: var(--bg-surface, #1E293B);
    border: 1px solid var(--border-color, #334155);
    color: var(--text-primary, #F8FAFC);
    font-size: 0.84rem;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.2s ease;
    box-shadow: 0 2px 6px rgba(15, 23, 42, 0.06);
}

.notif-back-btn:hover {
    border-color: var(--blue-primary, #3B82F6);
    color: var(--blue-primary, #3B82F6);
    transform: translateX(-2px);
}

.notif-header-card {
    background: var(--bg-surface, #1E293B);
    border: 1px solid var(--border-color, #334155);
    border-radius: var(--radius-xl, 14px);
    padding: 16px 18px;
    margin-bottom: 14px;
    display: flex;
    align-items: center;
    gap: 14px;
}

.notif-header-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background: rgba(37, 99, 235, 0.14);
    color: var(--blue-primary, #3B82F6);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    flex-shrink: 0;
}

.notif-header-text h2 {
    margin: 0 0 3px 0;
    font-size: 1.05rem;
    font-weight: 700;
    color: var(--text-primary);
}

.notif-header-text p {
    margin: 0;
    font-size: 0.8rem;
    color: var(--text-muted);
    line-height: 1.4;
}

.notif-list-stack {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.notif-card {
    background: var(--bg-surface, #1E293B);
    border: 1px solid var(--border-color, #334155);
    border-radius: var(--radius-xl, 14px);
    overflow: hidden;
    transition: all 0.2s ease;
    position: relative;
}

.notif-card.unread-card {
    border-color: rgba(59, 130, 246, 0.45);
    box-shadow: 0 4px 14px rgba(37, 99, 235, 0.08);
}

.notif-card.type-mendesak {
    border-left: 4px solid #EF4444;
}

.notif-card.type-penting {
    border-left: 4px solid #F59E0B;
}

.notif-card.type-info {
    border-left: 4px solid #3B82F6;
}

.notif-card-trigger {
    width: 100%;
    background: transparent;
    border: none;
    padding: 14px 16px;
    display: flex;
    align-items: center;
    gap: 12px;
    text-align: left;
    cursor: pointer;
    color: inherit;
    font-family: inherit;
}

.notif-card-trigger:hover {
    background: rgba(148, 163, 184, 0.05);
}

.notif-type-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    flex-shrink: 0;
}

.notif-type-icon.icon-urgent {
    background: rgba(239, 68, 68, 0.14);
    color: #EF4444;
}

.notif-type-icon.icon-important {
    background: rgba(245, 158, 11, 0.14);
    color: #F59E0B;
}

.notif-type-icon.icon-info {
    background: rgba(59, 130, 246, 0.14);
    color: #3B82F6;
}

.notif-summary-main {
    flex: 1;
    min-width: 0;
}

.notif-meta-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    flex-wrap: wrap;
    margin-bottom: 5px;
}

.notif-badges-inline {
    display: flex;
    align-items: center;
    gap: 6px;
    flex-wrap: wrap;
}

.notif-time-text {
    font-size: 0.72rem;
    color: var(--text-muted);
    white-space: nowrap;
}

.notif-title-text {
    margin: 0;
    font-size: 0.92rem;
    font-weight: 700;
    color: var(--text-primary);
    line-height: 1.35;
}

.notif-hint-text {
    margin: 3px 0 0 0;
    font-size: 0.76rem;
    color: var(--text-muted);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.notif-chevron-box {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: rgba(148, 163, 184, 0.1);
    color: var(--text-muted);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.78rem;
    flex-shrink: 0;
    transition: transform 0.22s ease, background 0.22s ease, color 0.22s ease;
}

.notif-card.expanded .notif-chevron-box {
    transform: rotate(180deg);
    background: rgba(59, 130, 246, 0.15);
    color: var(--blue-primary, #3B82F6);
}

.notif-detail-panel {
    display: none;
    padding: 0 16px 16px 16px;
    border-top: 1px solid var(--border-color, #334155);
    background: rgba(15, 23, 42, 0.18);
}

.notif-card.expanded .notif-detail-panel {
    display: block;
}

.notif-detail-body {
    padding-top: 14px;
    font-size: 0.85rem;
    line-height: 1.65;
    color: var(--text-secondary);
    white-space: pre-line;
}

.notif-detail-footer {
    margin-top: 14px;
    padding-top: 10px;
    border-top: 1px dashed var(--border-color, #334155);
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    flex-wrap: wrap;
    font-size: 0.76rem;
    color: var(--text-muted);
}
</style>
@endpush

@section('content')
<div class="notif-page-wrap">
    <!-- Top Bar: Tombol Kembali di Kiri Atas & Tandai Semua Dibaca di Kanan Atas -->
    <div class="notif-topbar">
        <a href="{{ route('portal.dashboard') }}" class="notif-back-btn">
            <i class="fas fa-arrow-left"></i>
            <span>Kembali</span>
        </a>

        @if($announcements->count() > 0)
            <button type="button" class="btn btn-secondary btn-sm" onclick="markAllNotificationsRead(event)">
                <i class="fas fa-check-double"></i> Tandai Semua Dibaca
            </button>
        @endif
    </div>

    <!-- Header Ringkas -->
    <div class="notif-header-card">
        <div class="notif-header-icon">
            <i class="fas fa-bell"></i>
        </div>
        <div class="notif-header-text">
            <h2>Pengumuman & Pesan Admin</h2>
            <p>Klik pada salah satu pesan di bawah untuk membuka rincian lengkapnya.</p>
        </div>
    </div>

    <!-- Daftar Pesan Satu per Satu (Klik untuk Buka Rincian) -->
    <div class="notif-list-stack">
        @forelse($announcements as $item)
            @php
                $readRecord = $item->reads->first();
                $isRead = $readRecord && $readRecord->read_at !== null;
                $isSpNotif = str_starts_with((string) $item->judul, '[SURAT PERINGATAN]');
                $cleanTitle = $isSpNotif
                    ? trim(\Illuminate\Support\Str::replaceFirst('[SURAT PERINGATAN]', '', (string) $item->judul))
                    : $item->judul;
                $iconClass = ($isSpNotif || $item->tipe === 'mendesak')
                    ? 'icon-urgent'
                    : ($item->tipe === 'penting' ? 'icon-important' : 'icon-info');
                $iconName = $isSpNotif
                    ? 'fa-file-signature'
                    : ($item->tipe === 'mendesak' ? 'fa-exclamation-triangle' : ($item->tipe === 'penting' ? 'fa-bell' : 'fa-bullhorn'));
            @endphp

            <div class="notif-card type-{{ $item->tipe }} {{ ! $isRead ? 'unread-card' : '' }}" id="announcement-item-{{ $item->id }}">
                <!-- Baris Utama (Klik untuk Buka/Tutup Rincian) -->
                <button type="button" class="notif-card-trigger" onclick="toggleNotifItem({{ $item->id }})">
                    <div class="notif-type-icon {{ $iconClass }}">
                        <i class="fas {{ $iconName }}"></i>
                    </div>

                    <div class="notif-summary-main">
                        <div class="notif-meta-row">
                            <div class="notif-badges-inline">
                                @if($isSpNotif)
                                    <span class="banner-badge badge-urgent"><i class="fas fa-file-signature"></i> Surat Peringatan (SP)</span>
                                @elseif($item->tipe === 'mendesak')
                                    <span class="banner-badge badge-urgent"><i class="fas fa-exclamation-triangle"></i> Mendesak</span>
                                @elseif($item->tipe === 'penting')
                                    <span class="banner-badge badge-important"><i class="fas fa-bell"></i> Penting</span>
                                @else
                                    <span class="banner-badge badge-info"><i class="fas fa-bullhorn"></i> Informasi</span>
                                @endif

                                @if($item->target_type === 'personal')
                                    <span class="banner-badge badge-personal"><i class="fas fa-user-lock"></i> Pribadi</span>
                                @elseif($item->target_type === 'departemen')
                                    <span class="banner-badge badge-target"><i class="fas fa-building"></i> Dept. {{ $item->target_departemen }}</span>
                                @endif

                                @if(! $isRead)
                                    <span class="feed-unread-pill"><i class="fas fa-circle"></i> Baru</span>
                                @endif
                            </div>

                            <span class="notif-time-text">
                                <i class="far fa-clock"></i> {{ $item->created_at->translatedFormat('d M Y, H:i') }}
                            </span>
                        </div>

                        <h3 class="notif-title-text">{{ $cleanTitle }}</h3>
                        <p class="notif-hint-text">Klik untuk melihat rincian pesan...</p>
                    </div>

                    <div class="notif-chevron-box">
                        <i class="fas fa-chevron-down"></i>
                    </div>
                </button>

                <!-- Panel Rincian Pesan (Hanya Terbuka Saat Diklik) -->
                <div class="notif-detail-panel">
                    <div class="notif-detail-body">{{ $item->konten }}</div>

                    <div class="notif-detail-footer">
                        <div>
                            <i class="fas fa-user-shield"></i> Dikirim oleh: <strong>{{ $item->admin?->nama ?? 'Admin HIMA TI' }}</strong>
                        </div>

                        <div class="feed-card-status">
                            @if(! $isRead)
                                <button type="button" class="btn-mark-read-item" onclick="markFeedItemRead({{ $item->id }})">
                                    <i class="fas fa-check"></i> Tandai Dibaca
                                </button>
                            @else
                                <span class="text-muted text-sm"><i class="fas fa-check-double text-success"></i> Sudah dibaca</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="portal-empty-card">
                <div class="empty-icon"><i class="far fa-bell-slash"></i></div>
                <h3>Belum Ada Notifikasi</h3>
                <p>Belum ada pengumuman atau pesan rincian dari Admin untuk akun Anda.</p>
            </div>
        @endforelse

        @if($announcements->hasPages())
            <div class="feed-pagination">
                {{ $announcements->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
function toggleNotifItem(id) {
    const card = document.getElementById('announcement-item-' + id);
    if (!card) return;

    const isExpanding = !card.classList.contains('expanded');
    card.classList.toggle('expanded');

    const hint = card.querySelector('.notif-hint-text');
    if (hint) {
        hint.textContent = isExpanding ? 'Klik untuk menutup rincian' : 'Klik untuk melihat rincian pesan...';
    }

    if (isExpanding && card.classList.contains('unread-card')) {
        markFeedItemRead(id);
    }
}

function markFeedItemRead(id) {
    const card = document.getElementById('announcement-item-' + id);
    if (!card) return;

    fetch(`{{ url('/portal/notifikasi') }}/${id}/read`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        }
    })
    .then(res => res.json())
    .then(() => {
        card.classList.remove('unread-card');
        const unreadPill = card.querySelector('.feed-unread-pill');
        if (unreadPill) unreadPill.remove();

        const statusContainer = card.querySelector('.feed-card-status');
        if (statusContainer) {
            statusContainer.innerHTML = '<span class="text-muted text-sm"><i class="fas fa-check-double text-success"></i> Sudah dibaca</span>';
        }

        const badge = document.getElementById('portalNotifBadge');
        if (badge) {
            let count = parseInt(badge.textContent, 10);
            if (!isNaN(count) && count > 1) {
                badge.textContent = count - 1;
            } else {
                badge.remove();
            }
        }
    })
    .catch(console.error);
}
</script>
@endpush
