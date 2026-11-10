@extends('layouts.public')

@section('title', 'Rapat Dibuat — ' . $meeting->jenis_label)
@section('page_heading', config('himati.app_name'))
@section('page_subheading', 'Rapat Berhasil Dibuat — Simpan dan Bagikan Link')
@section('container_width', '980px')

@push('styles')
<style>
/* Real-Time Live Attendance Grid & Cards */
.live-stats-grid {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: var(--space-4);
    margin-bottom: var(--space-5);
}
@media (max-width: 820px) {
    .live-stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    .live-stats-grid .stat-item-total {
        grid-column: span 2;
    }
}
.live-stat-card {
    background: var(--bg-surface);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-xl);
    padding: var(--space-4);
    display: flex;
    align-items: center;
    gap: var(--space-3);
    box-shadow: var(--shadow-sm);
    transition: all var(--transition-base);
}
.live-stat-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}
[data-theme="dark"] .live-stat-card {
    background: #1A2333 !important;
    border-color: #263347 !important;
}
.live-stat-icon {
    width: 44px;
    height: 44px;
    border-radius: var(--radius-lg);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    flex-shrink: 0;
}
.live-stat-icon.blue { background: var(--blue-subtle); color: var(--blue-primary); }
.live-stat-icon.green { background: var(--success-light); color: var(--success); }
.live-stat-icon.info { background: #EFF6FF; color: #2563EB; }
.live-stat-icon.orange { background: var(--orange-subtle); color: var(--orange-primary); }
.live-stat-icon.red { background: var(--danger-light); color: var(--danger); }

[data-theme="dark"] .live-stat-icon.blue { background: rgba(37, 99, 235, 0.2); color: #60A5FA; }
[data-theme="dark"] .live-stat-icon.green { background: rgba(16, 185, 129, 0.2); color: #34D399; }
[data-theme="dark"] .live-stat-icon.info { background: rgba(59, 130, 246, 0.2); color: #60A5FA; }
[data-theme="dark"] .live-stat-icon.orange { background: rgba(245, 158, 11, 0.2); color: #FBBF24; }
[data-theme="dark"] .live-stat-icon.red { background: rgba(239, 68, 68, 0.2); color: #F87171; }

.live-stat-info h3 {
    font-size: 1.6rem;
    font-weight: 800;
    line-height: 1;
    margin: 0 0 3px 0;
    color: var(--text-primary);
}
#statTotal { color: var(--blue-primary); }
#statHadir { color: var(--success); }
#statIzin { color: #2563EB; }
#statSakit { color: #D97706; }
#statAlpha { color: var(--danger); }

[data-theme="dark"] #statTotal { color: #60A5FA !important; }
[data-theme="dark"] #statHadir { color: #34D399 !important; }
[data-theme="dark"] #statIzin { color: #60A5FA !important; }
[data-theme="dark"] #statSakit { color: #FBBF24 !important; }
[data-theme="dark"] #statAlpha { color: #F87171 !important; }

.live-stat-info p {
    font-size: 0.76rem;
    color: var(--text-muted);
    font-weight: 600;
    margin: 0;
    text-transform: uppercase;
    letter-spacing: 0.03em;
}
[data-theme="dark"] .live-stat-info p {
    color: #94A3B8 !important;
}

/* Pulsing live badge */
.live-indicator-badge {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 4px 12px;
    border-radius: 9999px;
    background: #ECFDF5;
    border: 1px solid #A7F3D0;
    color: #059669;
    font-size: 0.75rem;
    font-weight: 700;
}
[data-theme="dark"] .live-indicator-badge {
    background: rgba(16, 185, 129, 0.15) !important;
    border-color: rgba(16, 185, 129, 0.3) !important;
    color: #34D399 !important;
}
.pulse-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #10B981;
    position: relative;
    display: inline-block;
}
.pulse-dot::after {
    content: '';
    position: absolute;
    top: -2px;
    left: -2px;
    right: -2px;
    bottom: -2px;
    border-radius: 50%;
    border: 2px solid #10B981;
    animation: pulseRing 1.6s infinite cubic-bezier(0.4, 0, 0.6, 1);
}
@keyframes pulseRing {
    0% { transform: scale(0.9); opacity: 1; }
    70% { transform: scale(2.2); opacity: 0; }
    100% { transform: scale(2.3); opacity: 0; }
}

/* Row entrance / updated highlight */
@keyframes rowFlash {
    0% { background-color: #A7F3D0; }
    100% { background-color: transparent; }
}
.row-highlight {
    animation: rowFlash 2.8s ease-out;
}

/* Controls Bar */
.table-controls-bar {
    padding: var(--space-4);
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid var(--border-color);
}

/* Filter pills */
.filter-btn-group {
    display: inline-flex;
    flex-wrap: wrap;
    gap: 8px;
    align-items: center;
}
.btn-filter-pill {
    border: 1px solid var(--border-color);
    background: var(--bg-surface);
    color: var(--text-secondary);
    padding: 7px 14px;
    border-radius: var(--radius-full);
    font-size: 0.82rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
    user-select: none;
    white-space: nowrap;
}
.btn-filter-pill:hover {
    border-color: var(--blue-primary);
    color: var(--blue-primary);
    background: rgba(37, 99, 235, 0.05);
    transform: translateY(-1px);
}
[data-theme="dark"] .btn-filter-pill {
    background: #1A2333 !important;
    border-color: #263347 !important;
    color: #CBD5E1 !important;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2) !important;
}
[data-theme="dark"] .btn-filter-pill:hover {
    background: #222E42 !important;
    border-color: #3B82F6 !important;
    color: #60A5FA !important;
}
.btn-filter-pill.active {
    background: linear-gradient(135deg, #2563EB 0%, #1D4ED8 100%) !important;
    border-color: #2563EB !important;
    color: #FFFFFF !important;
    box-shadow: 0 3px 10px rgba(37, 99, 235, 0.28) !important;
}
.btn-filter-pill.active i {
    color: #FFFFFF !important;
}
.btn-filter-pill .pill-count {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 20px;
    height: 19px;
    padding: 0 6px;
    border-radius: var(--radius-full);
    font-size: 0.72rem;
    font-weight: 700;
    background: rgba(0, 0, 0, 0.07);
    color: inherit;
    line-height: 1;
    transition: all 0.2s ease;
}
[data-theme="dark"] .btn-filter-pill .pill-count {
    background: rgba(255, 255, 255, 0.15) !important;
    color: #F8FAFC !important;
}
.btn-filter-pill.active .pill-count {
    background: rgba(255, 255, 255, 0.28) !important;
    color: #FFFFFF !important;
}

@media (max-width: 640px) {
    .table-controls-bar {
        padding: 12px !important;
        gap: 10px !important;
        flex-direction: column !important;
        align-items: stretch !important;
    }
    .filter-btn-group {
        display: grid !important;
        grid-template-columns: 1fr 1fr !important;
        gap: 8px !important;
        width: 100% !important;
    }
    .btn-filter-pill {
        width: 100% !important;
        justify-content: center !important;
        padding: 9px 8px !important;
        font-size: 0.78rem !important;
        gap: 6px !important;
    }
}

/* Mobile table scroll hint & responsiveness */
.table-scroll-hint-mobile {
    display: none;
    padding: 8px 14px;
    background: var(--bg-surface-subtle);
    color: var(--text-muted);
    font-size: 0.76rem;
    font-weight: 600;
    border-bottom: 1px solid var(--border-color);
    text-align: right;
    user-select: none;
}
[data-theme="dark"] .table-scroll-hint-mobile {
    background: #0d1522 !important;
    color: #94A3B8 !important;
    border-color: rgba(48, 175, 255, 0.2) !important;
}
@media (max-width: 768px) {
    .table-scroll-hint-mobile {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 6px;
    }
    .table-wrapper {
        overflow-x: auto !important;
        -webkit-overflow-scrolling: touch;
        width: 100% !important;
        max-width: 100% !important;
        display: block !important;
    }
    #attendanceTable {
        min-width: 720px !important;
        width: 100%;
    }
    #attendanceTable th {
        white-space: nowrap !important;
    }
    .table-search-box {
        max-width: 100% !important;
        flex: 1 1 100%;
    }
}

/* Search input wrapper */
.table-search-box {
    position: relative;
    max-width: 280px;
    width: 100%;
}
.table-search-box input {
    padding-left: 36px;
    font-size: 0.85rem;
    border-radius: var(--radius-full);
    height: 38px;
    border: 1px solid var(--border-color);
    background: var(--bg-surface);
    color: var(--text-primary);
    transition: all 0.2s ease;
}
.table-search-box input:focus {
    border-color: var(--blue-primary);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
}
.table-search-box i {
    position: absolute;
    left: 13px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--text-muted);
    font-size: 0.85rem;
    pointer-events: none;
}
@media (max-width: 640px) {
    .table-search-box {
        max-width: 100% !important;
        width: 100% !important;
    }
}
</style>
@endpush

@section('content')
<!-- Success Notification Card -->
<div class="card mb-6 text-center" style="border-top: 4px solid {{ $meeting->status === 'completed' ? 'var(--blue-primary)' : 'var(--success)' }}; padding: var(--space-8) var(--space-6);">
    <div style="width: 64px; height: 64px; border-radius: 50%; background: {{ $meeting->status === 'completed' ? 'rgba(59, 130, 246, 0.15)' : 'rgba(39, 174, 96, 0.15)' }}; display: flex; align-items: center; justify-content: center; margin: 0 auto var(--space-4);">
        @if ($meeting->status === 'completed')
            <i class="fas fa-check-double" style="font-size: 2rem; color: var(--blue-primary);"></i>
        @else
            <i class="fas fa-check" style="font-size: 2rem; color: var(--success);"></i>
        @endif
    </div>
    
    <div style="margin-bottom: var(--space-3);">
        @if ($meeting->status === 'completed')
            <span class="badge badge-success" style="font-size: 0.85rem; padding: 6px 14px;">
                <i class="fas fa-check-circle"></i> Rapat Selesai ({{ substr($meeting->waktu_selesai, 0, 5) }} WITA)
            </span>
        @else
            <span class="badge badge-primary" style="font-size: 0.85rem; padding: 6px 14px;">
                <i class="fas fa-broadcast-tower"></i> Absensi Sedang Berlangsung
            </span>
        @endif
    </div>

    <h2 style="font-size: var(--font-2xl); color: var(--text-primary); margin-bottom: var(--space-2);">
        {{ $meeting->status === 'completed' ? 'Rapat Telah Diselesaikan' : 'Rapat Berhasil Dibuat!' }}
    </h2>

    <div style="display: inline-flex; align-items: center; gap: 8px; padding: 6px 16px; border-radius: 9999px; background: var(--blue-subtle); border: 1px solid var(--blue-border); font-size: 0.85rem; color: var(--blue-primary); margin: 0 auto var(--space-4); font-weight: 500;">
        <i class="fas fa-user-check"></i>
        <span>Dibuat oleh: <strong style="color: var(--text-primary);">{{ $meeting->creator ? $meeting->creator->nama_lengkap : 'Admin HIMA TI' }}</strong>{{ $meeting->creator ? ' (' . $meeting->creator->jabatan . ' - Departemen ' . $meeting->creator->departemen . ')' : '' }}</span>
    </div>

    <p style="color: var(--text-muted); font-size: var(--font-sm); max-width: 520px; margin: 0 auto var(--space-6);">
        @if ($meeting->status === 'completed')
            Rapat telah ditutup. Sisa peserta yang tidak hadir tercatat sebagai Alpha dan form presensi online sudah ditutup.
        @elseif ($meeting->mode_rapat === 'online')
            Bagikan link presensi tunggal di bawah ke grup peserta rapat untuk absensi mandiri. Jika sesi rapat telah selesai, klik tombol <strong>Selesaikan Rapat</strong> di bawah untuk menutup absensi.
        @else
            Simpan halaman ini atau bookmark link di bawah untuk memantau presensi peserta secara real-time. Buka scanner kamera untuk memindai QR anggota.
        @endif
    </p>

    <!-- Primary Action: Open Scanner, Online Attend, or Finish Meeting -->
    <div style="display: flex; gap: 12px; justify-content: center; align-items: center; flex-wrap: wrap;">
        @if ($meeting->status === 'scanning' || $meeting->status === 'draft')
            @if ($meeting->mode_rapat === 'offline')
                <a href="{{ $scanUrl }}" class="btn btn-primary btn-lg" style="display: inline-flex; align-items: center; gap: 10px; padding: 14px 28px; font-size: var(--font-base);">
                    <i class="fas fa-qrcode"></i> Buka Scanner Kamera
                </a>
            @else
                <a href="{{ $onlineUrl }}" target="_blank" class="btn btn-primary btn-lg" style="display: inline-flex; align-items: center; gap: 10px; padding: 14px 28px; font-size: var(--font-base);">
                    <i class="fas fa-link"></i> Buka Presensi Online
                </a>
            @endif

            <form action="{{ route('public.meeting.finish', $meeting->unique_link) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menyelesaikan rapat ini sekarang?\n\n- Sisa peserta yang belum hadir akan dicatat sebagai Belum Hadir (Alpha).\n- Form presensi online akan ditutup sehingga peserta tidak bisa mengisi presensi lagi.');" style="margin: 0; display: inline-block;">
                @csrf
                <button type="submit" class="btn btn-danger btn-lg" style="display: inline-flex; align-items: center; gap: 10px; padding: 14px 24px; font-size: var(--font-base);">
                    <i class="fas fa-stop-circle"></i> Selesaikan Rapat
                </button>
            </form>

            <form action="{{ route('public.meeting.cancel', $meeting->unique_link) }}" method="POST" onsubmit="return confirm('Batalkan sesi rapat ini?\n\n- Sesi akan ditutup tanpa mencatat status Alpha pada peserta.\n- Tidak akan mempengaruhi sistem Surat Peringatan (SP) anggota.');" style="margin: 0; display: inline-block;">
                @csrf
                <button type="submit" class="btn btn-secondary btn-lg" style="display: inline-flex; align-items: center; gap: 8px; padding: 14px 20px; font-size: var(--font-base);">
                    <i class="fas fa-ban"></i> Batalkan Sesi
                </button>
            </form>
        @elseif ($meeting->status === 'cancelled')
            <div style="display: inline-flex; align-items: center; gap: 8px; padding: 12px 24px; border-radius: var(--radius-lg); background: rgba(239, 68, 68, 0.12); border: 1px solid rgba(239, 68, 68, 0.35); color: var(--danger); font-weight: 700; font-size: 0.95rem;">
                <i class="fas fa-ban"></i> Sesi Absensi Telah Dibatalkan (Aman & Tidak Mempengaruhi SP)
            </div>
        @else
            <div style="display: inline-flex; align-items: center; gap: 8px; padding: 12px 24px; border-radius: var(--radius-lg); background: #ECFDF5; border: 1px solid #A7F3D0; color: #059669; font-weight: 700; font-size: 0.95rem;">
                <i class="fas fa-lock"></i> Absensi Rapat Sudah Ditutup (Final)
            </div>
        @endif
    </div>
</div>

<!-- Share Links Card -->
<div class="card mb-6">
    <h3 class="card-title mb-4"><i class="fas fa-share-alt"></i> Link Absensi untuk Dibagikan</h3>

    @if ($meeting->mode_rapat === 'online')
        <!-- Rapat Online: 1 Link Rapat Tunggal -->
        <div>
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px; margin-bottom: 6px;">
                <label class="form-label" style="font-size: var(--font-xs); color: var(--info); font-weight: 600; margin: 0;">
                    <i class="fas fa-globe"></i> Link Presensi Rapat Online (Bagikan ke Seluruh Peserta)
                </label>
                @if($meeting->status === 'scanning')
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span class="badge badge-info" id="expiryInfoBadge" style="font-size: 0.75rem;">
                            <i class="fas fa-clock"></i> Batas Absen: <span id="textExpiryTime">{{ substr($meeting->getOnlineExpiryTime()->format('H:i'), 0, 5) }} WITA</span>
                        </span>
                        <button type="button" class="btn btn-sm btn-outline-info" onclick="openExpiryModal()" style="padding: 3px 8px; font-size: 0.72rem; border-radius: 6px;">
                            <i class="fas fa-edit"></i> Ubah Jam Berakhir
                        </button>
                    </div>
                @endif
            </div>
            <p style="font-size: 0.8rem; color: var(--text-muted); margin: 0 0 var(--space-2);">
                Peserta dapat langsung mengisi absensi (Hadir, Izin, atau Sakit beserta bukti) melalui link tunggal ini sebelum batas waktu berakhir.
            </p>
            <div class="share-link-box">
                <input type="text" value="{{ $onlineUrl }}" readonly id="onlineLinkInput">
                <button onclick="copyToClipboard('{{ $onlineUrl }}', this)">
                    <i class="fas fa-copy"></i> Salin Link
                </button>
            </div>
        </div>
    @else
        <!-- Rapat Offline: Form Izin / Sakit untuk Peserta -->
        <div>
            <label class="form-label" style="font-size: var(--font-xs); color: var(--danger); font-weight: 600;">
                <i class="fas fa-user-slash"></i> Link Izin / Sakit (Bagikan ke Anggota yang Berhalangan Hadir)
            </label>
            <div class="share-link-box">
                <input type="text" value="{{ $absentUrl }}" readonly id="absentLinkInput">
                <button onclick="copyToClipboard('{{ $absentUrl }}', this)">
                    <i class="fas fa-copy"></i> Salin
                </button>
            </div>
            <div style="margin-top: 10px; font-size: 0.8rem; color: var(--text-muted); display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px;">
                <span><i class="fas fa-info-circle text-primary"></i> Anda adalah pembuat rapat. Buka kamera lewat tombol <strong>Buka Scanner Kamera</strong> di atas.</span>
                <button type="button" onclick="copyToClipboard('{{ $scanUrl }}', this)" style="background: none; border: none; color: var(--blue-primary); cursor: pointer; font-size: 0.8rem; font-weight: 600; padding: 0; text-decoration: underline;">
                    <i class="fas fa-copy"></i> Salin link scanner untuk panitia lain
                </button>
            </div>
        </div>
    @endif
</div>

<!-- Real-Time Live Attendance Card -->
<div class="card mb-6" id="attendanceLiveCard">
    <div class="card-header" style="flex-wrap: wrap; gap: 12px; align-items: center; justify-content: space-between;">
        <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
            <h3 class="card-title" style="margin: 0;">
                <i class="fas fa-clipboard-check"></i> Data Presensi Masuk
            </h3>
            <span class="live-indicator-badge">
                <span class="pulse-dot"></span>
                <span>Live Otomatis</span>
            </span>
        </div>
        
        <div style="display: flex; align-items: center; gap: 10px;">
            <small style="color: var(--text-muted); font-size: 0.78rem;" id="lastUpdatedText">
                <i class="fas fa-sync-alt" id="refreshSpinner" style="margin-right: 3px;"></i>
                Sinkron baru saja
            </small>
            <button type="button" class="btn btn-sm btn-secondary" id="btnManualRefresh" title="Segarkan Data Sekarang">
                <i class="fas fa-redo-alt"></i> Refresh
            </button>
        </div>
    </div>

    <!-- Live Stat Cards -->
    <div class="live-stats-grid" style="padding: var(--space-4) var(--space-4) 0 var(--space-4);">
        <div class="live-stat-card stat-item-total">
            <div class="live-stat-icon blue"><i class="fas fa-users"></i></div>
            <div class="live-stat-info">
                <h3 id="statTotal">{{ $stats['total'] }}</h3>
                <p>Total Peserta</p>
            </div>
        </div>
        <div class="live-stat-card">
            <div class="live-stat-icon green"><i class="fas fa-check-circle"></i></div>
            <div class="live-stat-info">
                <h3 id="statHadir">{{ $stats['hadir'] }}</h3>
                <p>Hadir</p>
            </div>
        </div>
        <div class="live-stat-card">
            <div class="live-stat-icon info"><i class="fas fa-info-circle"></i></div>
            <div class="live-stat-info">
                <h3 id="statIzin">{{ $stats['izin'] }}</h3>
                <p>Izin</p>
            </div>
        </div>
        <div class="live-stat-card">
            <div class="live-stat-icon orange"><i class="fas fa-medkit"></i></div>
            <div class="live-stat-info">
                <h3 id="statSakit">{{ $stats['sakit'] }}</h3>
                <p>Sakit</p>
            </div>
        </div>
        <div class="live-stat-card">
            <div class="live-stat-icon red"><i class="fas fa-times-circle"></i></div>
            <div class="live-stat-info">
                <h3 id="statAlpha">{{ $stats['alpha'] }}</h3>
                <p>Belum Hadir</p>
            </div>
        </div>
    </div>

    <!-- Controls: Filter Pills & Search -->
    <div class="table-controls-bar">
        <div class="filter-btn-group" id="filterButtonGroup">
            <button type="button" class="btn-filter-pill active" data-filter="all">
                Semua <span class="pill-count" id="pillAll">{{ $stats['total'] }}</span>
            </button>
            <button type="button" class="btn-filter-pill" data-filter="hadir">
                <i class="fas fa-check-circle text-success" style="font-size: 11px;"></i> Hadir 
                <span class="pill-count" id="pillHadir">{{ $stats['hadir'] }}</span>
            </button>
            <button type="button" class="btn-filter-pill" data-filter="izin_sakit">
                <i class="fas fa-envelope-open-text text-info" style="font-size: 11px;"></i> Izin / Sakit 
                <span class="pill-count" id="pillIzinSakit">{{ $stats['izin'] + $stats['sakit'] }}</span>
            </button>
            <button type="button" class="btn-filter-pill" data-filter="alpha">
                <i class="fas fa-clock text-danger" style="font-size: 11px;"></i> Belum Hadir 
                <span class="pill-count" id="pillAlpha">{{ $stats['alpha'] }}</span>
            </button>
        </div>

        <div class="table-search-box">
            <i class="fas fa-search"></i>
            <input type="text" id="searchInput" class="form-control" placeholder="Cari nama anggota...">
        </div>
    </div>

    <!-- Table Scroll Hint for Mobile -->
    <div class="table-scroll-hint-mobile">
        <i class="fas fa-arrows-alt-h"></i> Geser tabel ke samping untuk melihat data lengkap &rarr;
    </div>

    <!-- Table Roster -->
    <div class="table-wrapper" style="border: none; border-radius: 0; overflow-x: auto !important; -webkit-overflow-scrolling: touch; width: 100%;">
        <table class="table" id="attendanceTable">
            <thead>
                <tr>
                    <th style="width: 50px; text-align: center;">No</th>
                    <th>Nama & Jabatan</th>
                    <th>Departemen</th>
                    <th>Status Presensi</th>
                    <th>Waktu & Metode</th>
                    <th>Keterangan / Bukti</th>
                </tr>
            </thead>
            <tbody id="attendanceTableBody">
                @forelse ($attendances as $index => $a)
                    <tr id="att-row-{{ $a->user_id }}" data-status="{{ $a->status }}">
                        <td style="text-align: center; color: var(--text-muted); font-weight: 600;" class="row-num">
                            {{ $index + 1 }}
                        </td>
                        <td>
                            <div style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                                <strong style="color: var(--text-primary);">{{ $a->member ? $a->member->nama_lengkap : 'Unknown' }}</strong>
                                @if($a->member && $a->member->sp_level == 1)
                                    <span class="badge badge-sp-1" style="font-size: 10px; padding: 2px 6px;"><i class="fas fa-exclamation-triangle"></i> SP 1</span>
                                @elseif($a->member && $a->member->sp_level == 2)
                                    <span class="badge badge-sp-2" style="font-size: 10px; padding: 2px 6px;"><i class="fas fa-exclamation-circle"></i> SP 2</span>
                                @elseif($a->member && $a->member->sp_level >= 3)
                                    <span class="badge badge-sp-3" style="font-size: 10px; padding: 2px 6px;"><i class="fas fa-user-times"></i> SP 3</span>
                                @endif
                            </div>
                            @if($a->member && $a->member->jabatan)
                                <span style="font-size: var(--font-xs); color: var(--text-muted);">{{ $a->member->jabatan }}</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge badge-dept">{{ $a->member ? $a->member->departemen : '-' }}</span>
                        </td>
                        <td>
                            @if($a->status === 'hadir')
                                <span class="badge badge-hadir"><i class="fas fa-check-circle"></i> Hadir</span>
                            @elseif($a->status === 'izin')
                                <span class="badge badge-izin"><i class="fas fa-info-circle"></i> Izin</span>
                            @elseif($a->status === 'sakit')
                                <span class="badge badge-sakit"><i class="fas fa-medkit"></i> Sakit</span>
                            @else
                                <span class="badge badge-alpha"><i class="fas fa-clock"></i> Belum Hadir</span>
                            @endif
                        </td>
                        <td>
                            @if($a->scanned_at)
                                <div style="font-weight: 600; color: var(--text-primary); font-size: 0.85rem;">
                                    <i class="fas fa-clock" style="color: var(--blue-primary); font-size: 10px;"></i>
                                    {{ $a->scanned_at->format('H:i') }} WITA
                                </div>
                                <div style="margin-top: 2px;">
                                    <span class="badge badge-secondary" style="font-size: 9px; padding: 2px 6px;">
                                        @if($a->scan_method === 'qr_scan')
                                            📷 QR Scanner
                                        @elseif($a->scan_method === 'online_link')
                                            🌐 Online Link
                                        @else
                                            ✍️ Manual
                                        @endif
                                    </span>
                                </div>
                            @else
                                <span style="color: var(--text-muted); font-size: 0.85rem;">-</span>
                            @endif
                        </td>
                        <td>
                            @if($a->keterangan)
                                <div style="font-size: 0.85rem; color: var(--text-secondary); max-width: 220px; word-break: break-word;">
                                    {{ $a->keterangan }}
                                </div>
                            @endif
                            @if($a->bukti_foto && file_exists(public_path('uploads/bukti/' . $a->bukti_foto)))
                                <button type="button" class="badge badge-info" style="display: inline-flex; align-items: center; gap: 4px; margin-top: 4px; border: none; cursor: pointer;" onclick="openBuktiModal('{{ asset('uploads/bukti/' . $a->bukti_foto) }}', '{{ addslashes($a->member?->nama_lengkap ?? '') }}', '{{ addslashes($a->keterangan ?? '') }}')">
                                    <i class="fas fa-image"></i> Lihat Bukti
                                </button>
                            @endif
                            @if(!$a->keterangan && (!$a->bukti_foto || !file_exists(public_path('uploads/bukti/' . $a->bukti_foto))))
                                <span style="color: var(--text-muted); font-size: 0.85rem;">-</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align: center; padding: var(--space-6); color: var(--text-muted);">
                            Belum ada peserta yang terdaftar untuk rapat ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Empty Filter/Search State -->
    <div id="emptyAttendanceState" style="display: none; padding: var(--space-8); text-align: center;">
        <div style="width: 56px; height: 56px; border-radius: 50%; background: var(--bg-surface-subtle); display: flex; align-items: center; justify-content: center; margin: 0 auto var(--space-3); color: var(--text-muted); font-size: 1.5rem;">
            <i class="fas fa-search"></i>
        </div>
        <h4 style="margin-bottom: 4px; color: var(--text-primary); font-size: 1rem;">Tidak Ada Data Ditemukan</h4>
        <p style="color: var(--text-muted); font-size: 0.85rem; margin: 0;">Tidak ada peserta yang cocok dengan filter atau kata kunci pencarian.</p>
    </div>
</div>

<!-- Meeting Info Card -->
<div class="card mb-6">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-info-circle"></i> Ringkasan Rapat</h3>
        <span class="badge badge-primary">{{ $meeting->jenis_label }}</span>
    </div>
    
    <div class="meeting-info-grid">
        <div class="meeting-info-item">
            <label><i class="fas fa-calendar"></i> Tanggal</label>
            <p>{{ $meeting->tanggal->locale('id')->translatedFormat('d F Y') }}</p>
        </div>
        <div class="meeting-info-item">
            <label><i class="fas fa-clock"></i> Waktu</label>
            <p>{{ substr($meeting->waktu_mulai, 0, 5) }} WITA{{ $meeting->waktu_selesai ? ' - ' . substr($meeting->waktu_selesai, 0, 5) . ' WITA' : '' }}</p>
        </div>
        <div class="meeting-info-item">
            <label><i class="fas fa-user-check"></i> Pembuat Rapat (PJ)</label>
            <p>
                <strong>{{ $meeting->creator ? $meeting->creator->nama_lengkap : 'Admin HIMA TI' }}</strong>
                @if($meeting->creator)
                    <br><small style="color: var(--text-muted); font-size: 0.8rem;">{{ $meeting->creator->jabatan }} &bull; Dept. {{ $meeting->creator->departemen }}</small>
                @endif
            </p>
        </div>
        <div class="meeting-info-item">
            <label><i class="fas fa-wifi"></i> Mode Rapat</label>
            <p>{{ $meeting->mode_rapat === 'offline' ? '🏢 Tatap Muka (Offline)' : '🌐 Online Link' }}</p>
        </div>
        @if($meeting->departemen)
        <div class="meeting-info-item">
            <label><i class="fas fa-building"></i> Departemen</label>
            <p>{{ $meeting->departemen }}</p>
        </div>
        @endif
        @if($meeting->nama_event)
        <div class="meeting-info-item">
            <label><i class="fas fa-calendar-star"></i> Event</label>
            <p>{{ $meeting->nama_event }}</p>
        </div>
        @endif
        @if($meeting->divisi_rapat)
        <div class="meeting-info-item">
            <label><i class="fas fa-sitemap"></i> Divisi</label>
            <p>{{ $meeting->divisi_rapat }}</p>
        </div>
        @endif
    </div>

    @if ($meeting->fokus_bahasan)
    <div class="meeting-agenda-box" style="margin-top: var(--space-4);">
        <i class="fas fa-bookmark" style="color: var(--blue-primary);"></i>
        <span class="agenda-label">Agenda / Pembahasan:</span>
        <span class="agenda-text">{{ $meeting->fokus_bahasan }}</span>
    </div>
    @endif
</div>

<!-- Dokumentasi Kegiatan Rapat -->
@if(!empty($docPhotos))
<div class="card mb-6">
    <div class="card-header" style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px;">
        <h3 class="card-title" style="margin: 0; font-size: 1.05rem;">
            <i class="fas fa-camera-retro" style="color: var(--blue-primary);"></i> Foto Dokumentasi Kegiatan Rapat
        </h3>
        <span class="badge badge-success">
            <i class="fas fa-check-circle"></i> {{ count($docPhotos) }} Foto Terupload
        </span>
    </div>
    <div class="doc-thumbnails-grid" style="grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 14px; margin-top: 14px;">
        @foreach($docPhotos as $idx => $photoUrl)
            <div class="doc-thumb-item" onclick="openBuktiModal('{{ $photoUrl }}', 'Foto Dokumentasi #{{ $idx + 1 }}', 'Dokumentasi kegiatan {{ $meeting->nama_event ?? $meeting->jenis_label }}')" style="aspect-ratio: 16/10; cursor: pointer;">
                <img src="{{ $photoUrl }}" alt="Dokumentasi {{ $idx + 1 }}" loading="lazy">
                <div class="doc-thumb-overlay">
                    <i class="fas fa-search-plus"></i>
                    <span>Lihat Fullsize</span>
                </div>
                <span class="doc-thumb-badge">Dokumentasi {{ $idx + 1 }}</span>
            </div>
        @endforeach
    </div>
</div>
@endif

<!-- Bottom Navigation Actions -->
<div class="text-center">
    <a href="{{ route('public.meeting.create') }}" class="btn btn-secondary">
        <i class="fas fa-calendar-plus"></i> Buat Agenda Rapat Lain
    </a>
</div>

<!-- Modal Bukti Foto Preview -->
<div class="modal-overlay" id="buktiModal" onclick="closeBuktiModal(event)">
    <div class="modal-dialog" style="max-width: 520px;">
        <div class="modal-header">
            <h3 class="modal-title" id="buktiModalTitle" style="font-size: 1rem; margin: 0;">
                <i class="fas fa-image text-primary"></i> Bukti Surat / Foto
            </h3>
            <button type="button" class="btn-close" onclick="closeBuktiModalDirect()" style="background: none; border: none; font-size: 1.25rem; cursor: pointer; color: var(--text-muted);">&times;</button>
        </div>
        <div class="modal-body" style="padding: var(--space-4); text-align: center;">
            <img id="buktiModalImg" src="" alt="Bukti Foto" style="max-width: 100%; max-height: 65vh; border-radius: var(--radius-lg); object-fit: contain; box-shadow: var(--shadow-sm);">
            <div id="buktiModalDesc" style="margin-top: 10px; font-size: 0.85rem; color: var(--text-secondary); text-align: left; background: var(--bg-surface-subtle); padding: 8px 12px; border-radius: var(--radius-md);"></div>
        </div>
        <div class="modal-footer" style="padding: var(--space-3) var(--space-4); display: flex; justify-content: flex-end; gap: 8px;">
            <a id="buktiModalDownload" href="#" target="_blank" class="btn btn-sm btn-primary" style="display: inline-flex; align-items: center; gap: 6px;">
                <i class="fas fa-external-link-alt"></i> Buka Ukuran Penuh
            </a>
            <button type="button" class="btn btn-sm btn-secondary" onclick="closeBuktiModalDirect()">Tutup</button>
        </div>
    </div>
</div>

@if ($meeting->mode_rapat === 'online')
<!-- Modal Ubah Jam Berakhir Online -->
<div class="modal-overlay" id="expiryModal" onclick="closeExpiryModal(event)">
    <div class="modal-dialog" style="max-width: 440px;">
        <div class="modal-header">
            <h3 class="modal-title" style="font-size: 1rem; margin: 0;">
                <i class="fas fa-clock text-info"></i> Ubah Jam Berakhir Absensi Online
            </h3>
            <button type="button" class="btn-close" onclick="closeExpiryModalDirect()" style="background: none; border: none; font-size: 1.25rem; cursor: pointer; color: var(--text-muted);">&times;</button>
        </div>
        <form id="formUpdateExpiry" onsubmit="submitExpiryUpdate(event)">
            @csrf
            <div class="modal-body" style="padding: var(--space-4); text-align: left;">
                <p style="font-size: 0.85rem; color: var(--text-muted); margin-top: 0;">
                    Tentukan batas jam kapan link absensi online otomatis ditutup untuk seluruh peserta.
                </p>
                <div class="form-group">
                    <label class="form-label" for="modalWaktuSelesai">Batas Jam Berakhir Baru <span class="required">*</span></label>
                    <input type="time" name="waktu_selesai" id="modalWaktuSelesai" class="form-control" value="{{ $meeting->getOnlineExpiryTime()->format('H:i') }}" required>
                </div>
                <div style="display: flex; gap: 6px; flex-wrap: wrap; margin-top: 8px;">
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="addExpiryOffsetModal(30)">+30 Menit</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="addExpiryOffsetModal(60)">+1 Jam</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="addExpiryOffsetModal(120)">+2 Jam</button>
                </div>
            </div>
            <div class="modal-footer" style="padding: var(--space-3) var(--space-4); display: flex; justify-content: flex-end; gap: 8px;">
                <button type="button" class="btn btn-sm btn-secondary" onclick="closeExpiryModalDirect()">Batal</button>
                <button type="submit" class="btn btn-sm btn-primary" id="btnSaveExpiry">
                    <i class="fas fa-save"></i> Simpan Jam Berakhir
                </button>
            </div>
        </form>
    </div>
</div>
@endif
@endsection

@push('scripts')
<script>
(function() {
    let attendancesData = @json($attendancesData ?? []);

    let previousStatuses = {};
    attendancesData.forEach(function(item) {
        previousStatuses[item.user_id] = item.status;
    });

    let currentFilter = 'all';
    let currentSearch = '';
    let pollingTimer = null;
    let isFetching = false;
    let newlyChangedUserIds = new Set();

    const searchInput = document.getElementById('searchInput');
    const filterButtons = document.querySelectorAll('.btn-filter-pill');
    const tableBody = document.getElementById('attendanceTableBody');
    const emptyState = document.getElementById('emptyAttendanceState');
    const refreshBtn = document.getElementById('btnManualRefresh');
    const refreshSpinner = document.getElementById('refreshSpinner');
    const lastUpdatedText = document.getElementById('lastUpdatedText');

    // UI Stats elements
    const statTotalEl = document.getElementById('statTotal');
    const statHadirEl = document.getElementById('statHadir');
    const statIzinEl = document.getElementById('statIzin');
    const statSakitEl = document.getElementById('statSakit');
    const statAlphaEl = document.getElementById('statAlpha');

    const pillAllEl = document.getElementById('pillAll');
    const pillHadirEl = document.getElementById('pillHadir');
    const pillIzinSakitEl = document.getElementById('pillIzinSakit');
    const pillAlphaEl = document.getElementById('pillAlpha');

    // Escape HTML Helper
    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    // Render Table Rows
    function renderTable() {
        if (!tableBody) return;

        const query = currentSearch.trim().toLowerCase();
        let visibleCount = 0;
        let html = '';

        attendancesData.forEach(function(item) {
            // Apply Status Filter
            let statusMatch = true;
            if (currentFilter === 'hadir') {
                statusMatch = item.status === 'hadir';
            } else if (currentFilter === 'izin_sakit') {
                statusMatch = item.status === 'izin' || item.status === 'sakit';
            } else if (currentFilter === 'alpha') {
                statusMatch = item.status === 'alpha';
            }

            // Apply Search Query Match
            let searchMatch = true;
            if (query !== '') {
                const nameStr = (item.nama || '').toLowerCase();
                const deptStr = (item.departemen || '').toLowerCase();
                const jabStr = (item.jabatan || '').toLowerCase();
                searchMatch = nameStr.includes(query) || deptStr.includes(query) || jabStr.includes(query);
            }

            if (statusMatch && searchMatch) {
                visibleCount++;
                const isHighlighted = newlyChangedUserIds.has(item.user_id);
                const highlightClass = isHighlighted ? 'row-highlight' : '';

                // Status Badge
                let statusBadge = '';
                if (item.status === 'hadir') {
                    statusBadge = '<span class="badge badge-hadir"><i class="fas fa-check-circle"></i> Hadir</span>';
                } else if (item.status === 'izin') {
                    statusBadge = '<span class="badge badge-izin"><i class="fas fa-info-circle"></i> Izin</span>';
                } else if (item.status === 'sakit') {
                    statusBadge = '<span class="badge badge-sakit"><i class="fas fa-medkit"></i> Sakit</span>';
                } else {
                    statusBadge = '<span class="badge badge-alpha"><i class="fas fa-clock"></i> Belum Hadir</span>';
                }

                // Time & Method
                let timeHtml = '<span style="color: var(--text-muted); font-size: 0.85rem;">-</span>';
                if (item.scanned_at) {
                    let methodLabel = '✍️ Manual';
                    if (item.scan_method === 'qr_scan') methodLabel = '📷 QR Scanner';
                    else if (item.scan_method === 'online_link') methodLabel = '🌐 Online Link';

                    timeHtml = `
                        <div style="font-weight: 600; color: var(--text-primary); font-size: 0.85rem;">
                            <i class="fas fa-clock" style="color: var(--blue-primary); font-size: 10px;"></i>
                            ${escapeHtml(item.scanned_at)}
                        </div>
                        <div style="margin-top: 2px;">
                            <span class="badge badge-secondary" style="font-size: 9px; padding: 2px 6px;">${methodLabel}</span>
                        </div>
                    `;
                }

                // Keterangan / Bukti
                let ketHtml = '';
                if (item.keterangan) {
                    ketHtml += `<div style="font-size: 0.85rem; color: var(--text-secondary); max-width: 220px; word-break: break-word;">${escapeHtml(item.keterangan)}</div>`;
                }
                if (item.bukti_foto) {
                    const safeName = escapeHtml(item.nama);
                    const safeKet = escapeHtml(item.keterangan || '');
                    ketHtml += `
                        <button type="button" class="badge badge-info" style="display: inline-flex; align-items: center; gap: 4px; margin-top: 4px; border: none; cursor: pointer;" 
                            onclick="openBuktiModal('${escapeHtml(item.bukti_foto)}', '${safeName}', '${safeKet}')">
                            <i class="fas fa-image"></i> Lihat Bukti
                        </button>
                    `;
                }
                if (!ketHtml) {
                    ketHtml = '<span style="color: var(--text-muted); font-size: 0.85rem;">-</span>';
                }

                let spBadgeHtml = '';
                if (item.sp_level === 1) {
                    spBadgeHtml = '<span class="badge badge-sp-1" style="font-size: 10px; padding: 2px 6px;"><i class="fas fa-exclamation-triangle"></i> SP 1</span>';
                } else if (item.sp_level === 2) {
                    spBadgeHtml = '<span class="badge badge-sp-2" style="font-size: 10px; padding: 2px 6px;"><i class="fas fa-exclamation-circle"></i> SP 2</span>';
                } else if (item.sp_level >= 3) {
                    spBadgeHtml = '<span class="badge badge-sp-3" style="font-size: 10px; padding: 2px 6px;"><i class="fas fa-user-times"></i> SP 3</span>';
                }

                html += `
                    <tr id="att-row-${item.user_id}" class="${highlightClass}">
                        <td style="text-align: center; color: var(--text-muted); font-weight: 600;">${visibleCount}</td>
                        <td>
                            <div style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                                <strong style="color: var(--text-primary);">${escapeHtml(item.nama)}</strong>
                                ${spBadgeHtml}
                            </div>
                            ${item.jabatan ? `<span style="font-size: var(--font-xs); color: var(--text-muted);">${escapeHtml(item.jabatan)}</span>` : ''}
                        </td>
                        <td><span class="badge badge-dept">${escapeHtml(item.departemen)}</span></td>
                        <td>${statusBadge}</td>
                        <td>${timeHtml}</td>
                        <td>${ketHtml}</td>
                    </tr>
                `;
            }
        });

        tableBody.innerHTML = html;

        if (visibleCount === 0) {
            emptyState.style.display = 'block';
            document.getElementById('attendanceTable').style.display = 'none';
        } else {
            emptyState.style.display = 'none';
            document.getElementById('attendanceTable').style.display = 'table';
        }

        // Clear highlight flags after animation completes
        if (newlyChangedUserIds.size > 0) {
            setTimeout(function() {
                newlyChangedUserIds.clear();
            }, 3000);
        }
    }

    // Update Stats Display
    function updateStats(stats) {
        if (!stats) return;
        if (statTotalEl) statTotalEl.textContent = stats.total;
        if (statHadirEl) statHadirEl.textContent = stats.hadir;
        if (statIzinEl) statIzinEl.textContent = stats.izin;
        if (statSakitEl) statSakitEl.textContent = stats.sakit;
        if (statAlphaEl) statAlphaEl.textContent = stats.alpha;

        if (pillAllEl) pillAllEl.textContent = stats.total;
        if (pillHadirEl) pillHadirEl.textContent = stats.hadir;
        if (pillIzinSakitEl) pillIzinSakitEl.textContent = (stats.izin || 0) + (stats.sakit || 0);
        if (pillAlphaEl) pillAlphaEl.textContent = stats.alpha;
    }

    // Real-Time Fetch Function
    function fetchAttendanceUpdates() {
        if (isFetching) return;
        isFetching = true;

        if (refreshSpinner) {
            refreshSpinner.classList.add('fa-spin');
        }

        const fetchUrl = window.location.pathname + '?json=1';

        fetch(fetchUrl, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(function(res) {
            if (!res.ok) throw new Error('Network error');
            return res.json();
        })
        .then(function(data) {
            if (data && data.success) {
                // Check for new changes
                let hasChanges = false;
                const newAttendances = data.attendances || [];

                newAttendances.forEach(function(newItem) {
                    const prevStatus = previousStatuses[newItem.user_id];
                    if (prevStatus !== undefined && prevStatus !== newItem.status) {
                        hasChanges = true;
                        newlyChangedUserIds.add(newItem.user_id);
                        
                        // Show discreet toast notification when attendance is filled
                        if (newItem.status === 'hadir' || newItem.status === 'izin' || newItem.status === 'sakit') {
                            const statusUpper = newItem.status.toUpperCase();
                            if (typeof showToast === 'function') {
                                showToast('success', 'Presensi Masuk (' + statusUpper + ')', `${newItem.nama} baru saja mengisi presensi.`);
                            }
                        }
                    }
                    previousStatuses[newItem.user_id] = newItem.status;
                });

                attendancesData = newAttendances;
                updateStats(data.stats);
                renderTable();

                const now = new Date();
                const timeString = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
                if (lastUpdatedText) {
                    lastUpdatedText.innerHTML = `<i class="fas fa-sync-alt" id="refreshSpinner" style="margin-right: 3px;"></i> Terakhir sinkron ${timeString} WITA`;
                }
            }
        })
        .catch(function(err) {
            console.warn('Real-time polling notice:', err);
        })
        .finally(function() {
            isFetching = false;
            const spinner = document.getElementById('refreshSpinner');
            if (spinner) {
                spinner.classList.remove('fa-spin');
            }
        });
    }

    // Start Polling (every 3.5 seconds)
    function startPolling() {
        if (pollingTimer) clearInterval(pollingTimer);
        pollingTimer = setInterval(fetchAttendanceUpdates, 3500);
    }

    // Search Input Listener
    if (searchInput) {
        searchInput.addEventListener('input', function(e) {
            currentSearch = e.target.value;
            renderTable();
        });
    }

    // Filter Button Click Listeners
    filterButtons.forEach(function(btn) {
        btn.addEventListener('click', function() {
            filterButtons.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            currentFilter = btn.getAttribute('data-filter');
            renderTable();
        });
    });

    // Manual Refresh Button Listener
    if (refreshBtn) {
        refreshBtn.addEventListener('click', function() {
            fetchAttendanceUpdates();
        });
    }

    // Start Real-Time Polling Engine
    startPolling();

    // Pause polling when browser tab is inactive to save battery and network
    document.addEventListener('visibilitychange', function() {
        if (document.hidden) {
            if (pollingTimer) clearInterval(pollingTimer);
        } else {
            fetchAttendanceUpdates();
            startPolling();
        }
    });
})();

// Modal Bukti Preview Functions
function openBuktiModal(imgUrl, memberName, keterangan) {
    const modal = document.getElementById('buktiModal');
    const img = document.getElementById('buktiModalImg');
    const title = document.getElementById('buktiModalTitle');
    const desc = document.getElementById('buktiModalDesc');
    const dlBtn = document.getElementById('buktiModalDownload');

    if (!modal || !img) return;

    img.src = imgUrl;
    title.innerHTML = `<i class="fas fa-image text-primary"></i> Bukti Surat/Foto — ${memberName}`;
    
    if (keterangan && keterangan.trim() !== '') {
        desc.style.display = 'block';
        desc.innerHTML = `<strong>Keterangan:</strong> ${keterangan}`;
    } else {
        desc.style.display = 'none';
    }

    dlBtn.href = imgUrl;
    modal.classList.add('active');
}

function closeBuktiModalDirect() {
    const modal = document.getElementById('buktiModal');
    if (modal) modal.classList.remove('active');
}

function closeBuktiModal(event) {
    if (event.target.id === 'buktiModal') {
        closeBuktiModalDirect();
    }
}

function openExpiryModal() {
    const modal = document.getElementById('expiryModal');
    if (modal) modal.classList.add('active');
}

function closeExpiryModalDirect() {
    const modal = document.getElementById('expiryModal');
    if (modal) modal.classList.remove('active');
}

function closeExpiryModal(event) {
    if (event.target && event.target.id === 'expiryModal') {
        closeExpiryModalDirect();
    }
}

function addExpiryOffsetModal(minutes) {
    const input = document.getElementById('modalWaktuSelesai');
    if (!input || !input.value) return;
    const [h, m] = input.value.split(':').map(Number);
    let totalM = (h * 60 + m + minutes) % (24 * 60);
    const newH = String(Math.floor(totalM / 60)).padStart(2, '0');
    const newM = String(totalM % 60).padStart(2, '0');
    input.value = `${newH}:${newM}`;
}

async function submitExpiryUpdate(event) {
    event.preventDefault();
    const btn = document.getElementById('btnSaveExpiry');
    const input = document.getElementById('modalWaktuSelesai');
    if (!input || !input.value) return;

    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan...';
    }

    try {
        const res = await fetch('{{ route('public.meeting.update_expiry', $meeting->unique_link) }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                waktu_selesai: input.value
            })
        });

        const data = await res.json();
        if (data.success) {
            const textBadge = document.getElementById('textExpiryTime');
            if (textBadge) textBadge.textContent = data.waktu_selesai;
            closeExpiryModalDirect();
            if (typeof showToast === 'function') {
                showToast('success', 'Batas Waktu Diperbarui', data.message);
            } else {
                alert(data.message);
            }
        } else {
            alert(data.message || 'Gagal memperbarui batas waktu absensi.');
        }
    } catch (err) {
        alert('Terjadi kesalahan jaringan saat menyimpan batas waktu.');
    } finally {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-save"></i> Simpan Jam Berakhir';
        }
    }
}
</script>
@endpush
