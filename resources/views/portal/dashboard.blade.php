@extends('layouts.portal')

@section('title', 'Dashboard')
@section('page_icon')
    <i class="fas fa-th-large"></i>
@endsection

@section('content')
<div class="portal-page-header">
    <h1 class="portal-page-title">
        <i class="fas fa-th-large"></i> Dashboard
    </h1>
</div>

<!-- Hero / Profil Singkat Pengurus -->
<div class="portal-hero-card">
    <div class="portal-hero-user">
        <div class="portal-hero-avatar">
            @if ($member->profile_photo_url)
                <img src="{{ $member->profile_photo_url }}" alt="{{ $member->nama_lengkap }}">
            @else
                {{ $member->initials }}
            @endif
        </div>
        <div class="portal-hero-details">
            <h2 class="portal-hero-title">
                Halo, <span class="hero-name">{{ $member->nama_lengkap }}</span>!&nbsp;<span class="wave-emoji">👋</span>
            </h2>
            <div class="portal-hero-meta">
                <span class="hero-pill-badge role-badge">
                    <i class="fas fa-id-badge"></i> {{ $member->jabatan }}
                </span>
                @if ($member->isBphInti())
                    <span class="hero-pill-badge bph-badge">
                        <i class="fas fa-crown"></i> Pengurus Inti
                    </span>
                @else
                    <span class="hero-pill-badge dept-badge">
                        <i class="fas fa-building"></i> Dept. {{ $member->departemen }}
                    </span>
                @endif
                @if ($member->is_bph)
                    <span class="hero-pill-badge bph-badge">
                        <i class="fas fa-shield-alt"></i> BPH
                    </span>
                @endif
                @if ($member->sp_level == 1)
                    <span class="badge badge-sp-1">
                        <i class="fas fa-exclamation-triangle"></i> SP 1
                    </span>
                @elseif ($member->sp_level == 2)
                    <span class="badge badge-sp-2">
                        <i class="fas fa-exclamation-circle"></i> SP 2
                    </span>
                @elseif ($member->sp_level >= 3)
                    <span class="badge badge-sp-3">
                        <i class="fas fa-user-times"></i> SP 3 (Diberhentikan)
                    </span>
                @endif
            </div>
        </div>
    </div>
    <div class="portal-hero-actions header-desktop-btn">
        <a href="{{ route('portal.qr') }}" class="btn btn-secondary btn-sm">
            <i class="fas fa-qrcode"></i> Buka QR Saya
        </a>
    </div>
</div>

<!-- Level & Gamifikasi Card (Terpisah di Bawah Hero Card) -->
@php $lvl = $member->level_data; @endphp
<div class="portal-level-card">
    <div class="level-card-header">
        <div class="level-card-left">
            <span class="level-icon-badge" style="background: {{ $lvl['badge_bg'] }};">
                <img src="{{ $lvl['tier_icon_url'] }}" alt="{{ $lvl['title'] }}" class="tier-minecraft-icon">
            </span>
            <div>
                <div class="level-card-title">
                    <span class="level-number">Lv. {{ $lvl['level'] }}</span> &bull; <span class="level-tier-name">{{ $lvl['title'] }}</span>
                </div>
                <div class="level-card-sub">
                    <span class="exp-total-pill"><i class="fas fa-bolt"></i> {{ number_format($lvl['exp']) }} EXP</span>
                </div>
            </div>
        </div>
        <div class="level-card-right">
            <a href="{{ route('portal.profile') }}" class="level-card-link">
                <span>Lihat Lencana</span>
                <i class="fas fa-chevron-right"></i>
            </a>
        </div>
    </div>

    <!-- Progress Track & Info -->
    <div class="level-progress-wrapper">
        <div class="level-progress-labels">
            <span class="progress-label-left">Progress Menuju Lv. {{ $lvl['level'] + 1 }}</span>
            <span class="progress-label-right">{{ $lvl['progress'] }} / {{ $lvl['needed'] }} EXP ({{ $lvl['percentage'] }}%)</span>
        </div>
        <div class="level-bar-track">
            <div class="level-bar-fill" style="width: {{ $lvl['percentage'] }}%;"></div>
        </div>
        <div class="level-progress-footer">
            <span><i class="fas fa-bolt" style="color: #F59E0B;"></i> +100 EXP setiap hadir rapat</span>
            <span>~{{ max(1, (int) ceil(($lvl['needed'] - $lvl['progress']) / 100)) }}x presensi lagi</span>
        </div>
    </div>
</div>

@php
    $currentAlphaStreak = $member->consecutive_alpha_count;
@endphp
@if ($member->sp_level > 0)
    <div class="sp-portal-alert sp-level-{{ $member->sp_level }} mb-6">
        <div class="sp-portal-alert-icon">
            @if ($member->sp_level == 1)
                <i class="fas fa-exclamation-triangle"></i>
            @elseif ($member->sp_level == 2)
                <i class="fas fa-exclamation-circle"></i>
            @else
                <i class="fas fa-user-times"></i>
            @endif
        </div>
        <div class="sp-portal-alert-body">
            <div class="sp-portal-alert-header">
                <strong>Status Akun: {{ $member->sp_full_label }}</strong>
            </div>
            <div style="display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap; margin-top: 6px;">
                <p class="sp-portal-alert-desc" style="margin: 0;">
                    <strong>Pelanggaran:</strong> {{ $member->sp_reason_display }}
                </p>
                <a
                    href="{{ route('portal.announcements.index') }}"
                    class="btn btn-secondary btn-sm"
                    style="font-size: 0.75rem; padding: 4px 12px; flex-shrink: 0; display: inline-flex; align-items: center; gap: 6px; text-decoration: none;"
                    title="Buka rincian lengkap di pesan">
                    <i class="fas fa-envelope-open-text"></i>
                    <span>Lihat Rincian</span>
                </a>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const spKey = 'sp_ack_{{ $member->id }}_{{ $member->sp_level }}';
            if (!sessionStorage.getItem(spKey) && typeof window.showPopupAlert === 'function') {
                sessionStorage.setItem(spKey, '1');
                window.showPopupAlert(
                    '{{ $member->sp_level >= 3 ? "danger" : "warning" }}',
                    'Pemberitahuan {{ $member->sp_full_label }}',
                    '<strong>Status Akun Anda Saat Ini:</strong> {{ $member->sp_full_label }}<br><br><strong>Pelanggaran:</strong> {{ addslashes($member->sp_reason_display) }}<br><br><small>Silakan hubungi BPH Inti atau buka menu Pesan untuk informasi pemulihan akun.</small>'
                );
            }
        });
    </script>
@elseif ($currentAlphaStreak > 0)
    <div class="sp-portal-alert sp-level-streak mb-6">
        <div class="sp-portal-alert-icon">
            <i class="fas fa-history"></i>
        </div>
        <div class="sp-portal-alert-body">
            <div class="sp-portal-alert-header">
                <strong>Perhatian Kedisiplinan Kehadiran</strong>
                <span class="badge badge-warning">Alpha Berturut: {{ $currentAlphaStreak }}/3</span>
            </div>
            <p class="sp-portal-alert-desc">
                Anda tercatat <strong>Alpha {{ $currentAlphaStreak }} kali berturut-turut</strong> pada kegiatan terakhir. Alpha 3 kali berturut-turut akan otomatis dikenakan <strong>SP 1</strong>.
            </p>
        </div>
    </div>
@endif

@if(isset($dashboardBanners) && $dashboardBanners->count() > 0)
    <div class="portal-announcements-wrapper" id="portalAnnouncementsContainer">
        @foreach($dashboardBanners as $banner)
            <div class="portal-banner-card banner-type-{{ $banner->tipe }} {{ $banner->pinned ? 'banner-is-pinned' : '' }}" id="banner-card-{{ $banner->id }}">
                <div class="portal-banner-accent-bar"></div>
                <div class="portal-banner-inner">
                    <div class="portal-banner-header">
                        <div class="portal-banner-badges">
                            @if($banner->tipe === 'mendesak')
                                <span class="banner-badge badge-urgent"><i class="fas fa-exclamation-triangle"></i> Mendesak</span>
                            @elseif($banner->tipe === 'penting')
                                <span class="banner-badge badge-important"><i class="fas fa-bell"></i> Penting</span>
                            @else
                                <span class="banner-badge badge-info"><i class="fas fa-bullhorn"></i> Informasi</span>
                            @endif

                            @if($banner->target_type === 'personal')
                                <span class="banner-badge badge-personal"><i class="fas fa-user-lock"></i> Pesan Pribadi</span>
                            @elseif($banner->target_type === 'departemen')
                                <span class="banner-badge badge-target"><i class="fas fa-building"></i> Dept. {{ $banner->target_departemen }}</span>
                            @endif

                            @if($banner->pinned)
                                <span class="banner-badge badge-pinned"><i class="fas fa-thumbtack"></i> Disematkan</span>
                            @endif
                        </div>
                        <span class="portal-banner-time">
                            <i class="far fa-clock"></i> {{ $banner->created_at->diffForHumans() }}
                        </span>
                    </div>

                    <h3 class="portal-banner-title">{{ $banner->judul }}</h3>
                    <div class="portal-banner-text">
                        {!! nl2br(e($banner->konten)) !!}
                    </div>

                    <div class="portal-banner-footer">
                        <div class="portal-banner-author">
                            <i class="fas fa-user-shield"></i> {{ $banner->admin?->nama ?? 'Admin HIMA TI' }}
                        </div>
                        <div class="portal-banner-actions">
                            <button type="button" class="btn-dismiss-banner" onclick="dismissBannerCard({{ $banner->id }})" title="Tutup pengumuman ini">
                                <i class="fas fa-check"></i> <span>Tutup</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endif

<!-- Statistik Kehadiran (2x2 di Mobile, 4 Kolom di Desktop) -->
<div class="stat-cards">
    <div class="stat-card stat-card-total">
        <div class="stat-icon stat-icon-blue"><i class="fas fa-calendar-check"></i></div>
        <div class="stat-info">
            <h3 class="stat-num-blue">{{ $stats['total'] }}</h3>
            <p>Total Rapat</p>
        </div>
    </div>
    <div class="stat-card stat-card-hadir">
        <div class="stat-icon stat-icon-green"><i class="fas fa-user-check"></i></div>
        <div class="stat-info">
            <h3 class="stat-num-green">{{ $stats['hadir'] }}</h3>
            <p>Hadir</p>
        </div>
    </div>
    <div class="stat-card stat-card-izin">
        <div class="stat-icon stat-icon-orange"><i class="fas fa-file-medical"></i></div>
        <div class="stat-info">
            <h3 class="stat-num-orange">{{ $stats['izin'] + $stats['sakit'] }}</h3>
            <p>Izin / Sakit</p>
        </div>
    </div>
    <div class="stat-card stat-card-persen">
        <div class="stat-icon stat-icon-purple"><i class="fas fa-chart-line"></i></div>
        <div class="stat-info">
            <h3 class="stat-num-purple">{{ $stats['percentage'] }}%</h3>
            <p>% Kehadiran</p>
        </div>
    </div>
</div>

@if ($member->canViewMembersMonitoring())
    <!-- Kartu Monitoring Anggota / Pengurus (Khusus Kadep & Pengurus Inti - Read Only) -->
    <div class="card mb-6" style="background: var(--bg-surface); border: 2px solid var(--blue-primary); padding: 16px; position: relative; overflow: hidden;">
        <div style="display: flex; align-items: center; justify-content: space-between; gap: 14px; flex-wrap: wrap;">
            <div style="display: flex; align-items: center; gap: 14px; flex: 1; min-width: 240px;">
                <div style="width: 48px; height: 48px; border-radius: 12px; background: rgba(48, 175, 255, 0.12); color: var(--blue-primary); display: flex; align-items: center; justify-content: center; font-size: 1.4rem; flex-shrink: 0; border: 1.5px solid rgba(48, 175, 255, 0.3);">
                    <i class="fas {{ $member->isBphInti() ? 'fa-users-cog' : 'fa-user-friends' }}"></i>
                </div>
                <div>
                    <h3 style="margin: 0 0 4px; font-size: 1.02rem; font-weight: 800; color: var(--text-primary);">
                        {{ $member->isBphInti() ? 'Monitoring Seluruh Pengurus' : 'Monitoring Anggota Dept. ' . $member->departemen }}
                    </h3>
                    <p style="margin: 0; font-size: 0.8rem; color: var(--text-muted); line-height: 1.4;">
                        {{ $member->isBphInti() ? 'Pantau profil, track record rapat, bukti kehadiran, dan kedisiplinan 41 pengurus HIMA TI (Read-Only).' : 'Pantau track record kegiatan, bukti kehadiran, dan kedisiplinan anggota departemen Anda (Read-Only).' }}
                    </p>
                </div>
            </div>
            <div style="flex-shrink: 0;">
                <a href="{{ route('portal.members.index') }}" class="btn btn-primary btn-sm" style="display: inline-flex; align-items: center; gap: 8px; font-weight: 700; padding: 8px 16px;">
                    <span>{{ $member->isBphInti() ? 'Buka Data Pengurus' : 'Buka Data Anggota' }}</span>
                    <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>
@endif

<!-- Menu Aksi Utama Pengurus (Khusus Layar Desktop, di HP sudah ada Bottom Nav) -->
<div class="portal-actions-strip header-desktop-btn">
    <a href="{{ route('portal.qr') }}" class="portal-action-btn portal-action-qr" title="Buka QR Saya untuk Absen">
        <i class="fas fa-qrcode"></i>
        <span>QR Saya</span>
    </a>
    <a href="{{ route('public.meeting.create') }}" class="portal-action-btn portal-action-rapat" title="Buat Jadwal Rapat Baru">
        <i class="fas fa-calendar-plus"></i>
        <span>Buat Rapat</span>
    </a>
    <a href="{{ route('portal.attendance') }}" class="portal-action-btn portal-action-history" title="Lihat Rekapan Absensi">
        <i class="fas fa-history"></i>
        <span>Riwayat Absensi</span>
    </a>
</div>

<!-- Rapat Aktif & Mendatang (Dipisah: Belum Absen vs Sudah Absen) -->
<div class="card mb-6">
    @php
        $unattendedCount = $unattendedAttendances->count();
        $attendedCount = $attendedAttendances->count();
        $defaultTab = ($unattendedCount === 0 && $attendedCount > 0) ? 'sudah' : 'belum';
    @endphp

    <div class="card-header" style="flex-wrap: wrap; gap: 8px;">
        <h2 class="card-title">
            <i class="fas fa-calendar-day text-primary"></i> Rapat Aktif & Mendatang
        </h2>
        <div class="card-header-actions">
            @if ($unattendedCount > 0)
                <span class="badge badge-warning" title="Rapat yang belum Anda hadiri">
                    <i class="fas fa-bell"></i> {{ $unattendedCount }} Perlu Dihadiri
                </span>
            @elseif ($attendedCount > 0)
                <span class="badge badge-success" title="Semua rapat aktif telah dihadiri">
                    <i class="fas fa-check-circle"></i> Semua Selesai
                </span>
            @endif
        </div>
    </div>

    @if ($upcomingAttendances->isEmpty())
        <div class="empty-state" style="padding: var(--space-6) var(--space-4);">
            <div style="width: 52px; height: 52px; border-radius: 50%; background: var(--success-light); color: var(--success); display: inline-flex; align-items: center; justify-content: center; font-size: 1.4rem; margin-bottom: 10px;">
                <i class="fas fa-check-circle"></i>
            </div>
            <h3 style="font-size: 1.05rem; margin-bottom: 4px;">Tidak Ada Rapat Aktif</h3>
            <p style="color: var(--text-muted); font-size: 0.85rem; margin: 0;">Saat ini belum ada jadwal rapat aktif atau mendatang.</p>
        </div>
    @else
        <!-- Segmented Tab Toggle (Pemisah Belum Absen vs Sudah Absen) -->
        <div class="portal-segmented-tabs" role="tablist">
            <button type="button" 
                    class="portal-tab-btn {{ $defaultTab === 'belum' ? 'active' : '' }}" 
                    id="tabBtnBelum" 
                    onclick="switchMeetingTab('belum')">
                <i class="fas fa-clock text-warning"></i>
                <span>Belum Absen</span>
                <span class="tab-badge {{ $unattendedCount > 0 ? 'badge-warn' : '' }}">
                    {{ $unattendedCount }}
                </span>
            </button>
            <button type="button" 
                    class="portal-tab-btn {{ $defaultTab === 'sudah' ? 'active' : '' }}" 
                    id="tabBtnSudah" 
                    onclick="switchMeetingTab('sudah')">
                <i class="fas fa-check-circle text-success"></i>
                <span>Sudah Absen</span>
                <span class="tab-badge {{ $attendedCount > 0 ? 'badge-ok' : '' }}">
                    {{ $attendedCount }}
                </span>
            </button>
        </div>

        <!-- ================= TAB 1: BELUM ABSEN ================= -->
        <div id="panelBelumAbsen" class="meeting-tab-panel" style="{{ $defaultTab === 'belum' ? '' : 'display: none;' }}">
            @if ($unattendedAttendances->isEmpty())
                <div class="empty-state" style="padding: var(--space-6) var(--space-4);">
                    <div style="width: 50px; height: 50px; border-radius: 50%; background: rgba(16, 185, 129, 0.12); color: #10B981; display: inline-flex; align-items: center; justify-content: center; font-size: 1.3rem; margin-bottom: 10px;">
                        <i class="fas fa-clipboard-check"></i>
                    </div>
                    <h3 style="font-size: 1rem; margin-bottom: 4px; font-weight: 700;">Semua Rapat Sudah Dihadiri! 🎉</h3>
                    <p style="color: var(--text-muted); font-size: 0.85rem; margin: 0;">Bagus sekali! Tidak ada rapat aktif atau mendatang yang belum Anda hadiri.</p>
                </div>
            @else
                <!-- Mode HP: Kartu Rapat Belum Absen -->
                <div class="portal-cards-mobile meeting-card-list">
                    @foreach ($unattendedAttendances as $attendance)
                        @if ($attendance->meeting)
                            @php
                                $catLabel = match($attendance->meeting->jenis_rapat) {
                                    'internal_dept' => 'Internal ' . ($attendance->meeting->departemen ?? 'Dept'),
                                    'kepanitiaan' => 'Kepanitiaan',
                                    'bph' => 'BPH / Inti',
                                    'lintas_dept' => 'Lintas Dept',
                                    'perdana' => 'Rapat Perdana',
                                    default => $attendance->meeting->jenis_label,
                                };
                                $catClass = match($attendance->meeting->jenis_rapat) {
                                    'internal_dept' => 'cat-internal',
                                    'kepanitiaan' => 'cat-kepanitiaan',
                                    'bph' => 'cat-bph',
                                    'lintas_dept' => 'cat-lintas',
                                    'perdana' => 'cat-perdana',
                                    default => 'cat-default',
                                };
                                $isOnline = $attendance->meeting->mode_rapat === 'online';
                                $isScanning = $attendance->meeting->status === 'scanning';
                            @endphp
                            <div class="meeting-item-card is-pending">
                                <div class="meeting-card-top">
                                    <h4 class="meeting-card-title">
                                        {{ $attendance->meeting->fokus_bahasan ?? $attendance->meeting->nama_event ?? $attendance->meeting->jenis_label }}
                                    </h4>
                                    <div class="meeting-card-badges">
                                        @if ($isScanning)
                                            <span class="chip-status chip-live"><span class="pulse-dot"></span> Presensi Dibuka</span>
                                        @else
                                            <span class="chip-status chip-alpha">Belum Absen</span>
                                        @endif
                                    </div>
                                </div>

                                <div class="meeting-card-meta">
                                    <span class="meeting-meta-cat {{ $catClass }}">{{ $catLabel }}</span>
                                    <span class="meta-dot">&bull;</span>
                                    <span>{{ $attendance->meeting->tanggal->locale('id')->translatedFormat('d M Y') }}, {{ substr($attendance->meeting->waktu_mulai, 0, 5) }} WITA</span>
                                    <span class="meta-dot">&bull;</span>
                                    <span class="meeting-meta-mode {{ $isOnline ? 'mode-online' : 'mode-offline' }}">
                                        {{ $isOnline ? 'Online' : 'Offline' }}
                                    </span>
                                </div>

                                <div class="meeting-card-actions">
                                    @if ($isOnline && $isScanning)
                                        <form action="{{ route('portal.presence.store', $attendance->id) }}" method="POST" style="margin: 0;">
                                            @csrf
                                            <button type="submit" class="btn-chip btn-chip-green" onclick="return confirm('Catat kehadiran Anda pada rapat online ini?')">
                                                <i class="fas fa-check-circle"></i> Hadir Online
                                            </button>
                                        </form>
                                    @else
                                        <a href="{{ route('portal.qr') }}" class="btn-chip btn-chip-blue" title="Buka QR Saya">
                                            <i class="fas fa-qrcode"></i> Buka QR
                                        </a>
                                    @endif

                                    <a href="{{ route('portal.absence.create') }}" class="btn-chip btn-chip-subtle" title="Ajukan Izin">
                                        <i class="fas fa-paper-plane"></i> Izin
                                    </a>
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>

                <!-- Mode Desktop: Tabel Belum Absen -->
                <div class="table-wrapper portal-table-desktop" style="width: 100%;">
                    <table class="table" style="width: 100%;">
                        <thead>
                            <tr>
                                <th style="width: 36%;">Rapat / Agenda</th>
                                <th style="width: 20%;">Jadwal</th>
                                <th style="width: 20%;">Status Rapat</th>
                                <th style="width: 24%; text-align: right;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($unattendedAttendances as $attendance)
                                @if ($attendance->meeting)
                                    @php
                                        $isOnline = $attendance->meeting->mode_rapat === 'online';
                                        $isScanning = $attendance->meeting->status === 'scanning';
                                    @endphp
                                    <tr>
                                        <td>
                                            <div style="font-weight: 700; color: var(--text-primary);">{{ $attendance->meeting->jenis_label }}</div>
                                            <small style="color: var(--text-muted);">
                                                {{ $attendance->meeting->fokus_bahasan ?? $attendance->meeting->nama_event ?? '-' }}
                                            </small>
                                        </td>
                                        <td>
                                            <div style="font-weight: 600;">{{ $attendance->meeting->tanggal->locale('id')->translatedFormat('d M Y') }}</div>
                                            <small style="color: var(--text-muted);">{{ substr($attendance->meeting->waktu_mulai, 0, 5) }} WITA</small>
                                        </td>
                                        <td>
                                            @if ($isScanning)
                                                <span class="badge badge-danger"><span class="pulse-dot"></span> Presensi Dibuka</span>
                                            @else
                                                <span class="badge badge-alpha">Belum Dimulai</span>
                                            @endif

                                            @if ($isOnline)
                                                <span class="badge badge-info" style="margin-left: 4px; font-size: 0.7rem;"><i class="fas fa-globe"></i> Online</span>
                                            @else
                                                <span class="badge badge-dept" style="margin-left: 4px; font-size: 0.7rem;"><i class="fas fa-qrcode"></i> Offline</span>
                                            @endif
                                        </td>
                                        <td style="text-align: right;">
                                            <div style="display: flex; gap: var(--space-2); flex-wrap: wrap; align-items: center; justify-content: flex-end;">
                                                @if ($isOnline && $isScanning)
                                                    <form action="{{ route('portal.presence.store', $attendance->id) }}" method="POST" style="margin: 0; display: inline;">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Catat kehadiran Anda pada rapat online ini?')">
                                                            <i class="fas fa-check-circle"></i> Hadir Online
                                                        </button>
                                                    </form>
                                                @else
                                                    <a href="{{ route('portal.qr') }}" class="btn btn-sm btn-secondary" title="Tunjukkan QR Code ini ke kamera scanner panitia">
                                                        <i class="fas fa-qrcode"></i> QR Saya
                                                    </a>
                                                @endif

                                                <a href="{{ route('portal.absence.create') }}" class="btn btn-sm btn-outline-secondary" title="Ajukan Izin jika berhalangan">
                                                    <i class="fas fa-paper-plane"></i> Ajukan Izin
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <!-- ================= TAB 2: SUDAH ABSEN ================= -->
        <div id="panelSudahAbsen" class="meeting-tab-panel" style="{{ $defaultTab === 'sudah' ? '' : 'display: none;' }}">
            @if ($attendedAttendances->isEmpty())
                <div class="empty-state" style="padding: var(--space-6) var(--space-4);">
                    <div style="width: 50px; height: 50px; border-radius: 50%; background: var(--bg-surface-subtle); color: var(--text-muted); display: inline-flex; align-items: center; justify-content: center; font-size: 1.3rem; margin-bottom: 10px;">
                        <i class="fas fa-history"></i>
                    </div>
                    <h3 style="font-size: 1rem; margin-bottom: 4px; font-weight: 700;">Belum Ada Rapat Dihadiri</h3>
                    <p style="color: var(--text-muted); font-size: 0.85rem; margin: 0;">Rapat yang telah Anda hadiri atau izin akan tercatat di sini.</p>
                </div>
            @else
                <!-- Mode HP: Kartu Rapat Sudah Absen -->
                <div class="portal-cards-mobile meeting-card-list">
                    @foreach ($attendedAttendances as $attendance)
                        @if ($attendance->meeting)
                            @php
                                $catLabel = match($attendance->meeting->jenis_rapat) {
                                    'internal_dept' => 'Internal ' . ($attendance->meeting->departemen ?? 'Dept'),
                                    'kepanitiaan' => 'Kepanitiaan',
                                    'bph' => 'BPH / Inti',
                                    'lintas_dept' => 'Lintas Dept',
                                    'perdana' => 'Rapat Perdana',
                                    default => $attendance->meeting->jenis_label,
                                };
                                $catClass = match($attendance->meeting->jenis_rapat) {
                                    'internal_dept' => 'cat-internal',
                                    'kepanitiaan' => 'cat-kepanitiaan',
                                    'bph' => 'cat-bph',
                                    'lintas_dept' => 'cat-lintas',
                                    'perdana' => 'cat-perdana',
                                    default => 'cat-default',
                                };
                                $isOnline = $attendance->meeting->mode_rapat === 'online';
                            @endphp
                            <div class="meeting-item-card is-attended">
                                <div class="meeting-card-top">
                                    <h4 class="meeting-card-title">
                                        {{ $attendance->meeting->fokus_bahasan ?? $attendance->meeting->nama_event ?? $attendance->meeting->jenis_label }}
                                    </h4>
                                    <div class="meeting-card-badges">
                                        @if ($attendance->status === 'hadir')
                                            <span class="chip-status chip-hadir"><i class="fas fa-check"></i> Hadir</span>
                                        @elseif ($attendance->status === 'izin')
                                            <span class="chip-status chip-izin">Izin</span>
                                        @elseif ($attendance->status === 'sakit')
                                            <span class="chip-status chip-sakit">Sakit</span>
                                        @endif
                                    </div>
                                </div>

                                <div class="meeting-card-meta">
                                    <span class="meeting-meta-cat {{ $catClass }}">{{ $catLabel }}</span>
                                    <span class="meta-dot">&bull;</span>
                                    <span>{{ $attendance->meeting->tanggal->locale('id')->translatedFormat('d M Y') }}, {{ substr($attendance->meeting->waktu_mulai, 0, 5) }} WITA</span>
                                    <span class="meta-dot">&bull;</span>
                                    <span class="meeting-meta-mode {{ $isOnline ? 'mode-online' : 'mode-offline' }}">
                                        {{ $isOnline ? 'Online' : 'Offline' }}
                                    </span>
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>

                <!-- Mode Desktop: Tabel Sudah Absen -->
                <div class="table-wrapper portal-table-desktop" style="width: 100%;">
                    <table class="table" style="width: 100%;">
                        <thead>
                            <tr>
                                <th style="width: 38%;">Rapat / Agenda</th>
                                <th style="width: 24%;">Jadwal</th>
                                <th style="width: 20%;">Status Kehadiran</th>
                                <th style="width: 18%; text-align: right;">Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($attendedAttendances as $attendance)
                                @if ($attendance->meeting)
                                    <tr>
                                        <td>
                                            <div style="font-weight: 700; color: var(--text-primary);">{{ $attendance->meeting->jenis_label }}</div>
                                            <small style="color: var(--text-muted);">
                                                {{ $attendance->meeting->fokus_bahasan ?? $attendance->meeting->nama_event ?? '-' }}
                                            </small>
                                        </td>
                                        <td>
                                            <div style="font-weight: 600;">{{ $attendance->meeting->tanggal->locale('id')->translatedFormat('d M Y') }}</div>
                                            <small style="color: var(--text-muted);">{{ substr($attendance->meeting->waktu_mulai, 0, 5) }} WITA</small>
                                        </td>
                                        <td>
                                            @if ($attendance->status === 'hadir')
                                                <span class="badge badge-hadir"><i class="fas fa-check"></i> Hadir</span>
                                            @elseif ($attendance->status === 'izin')
                                                <span class="badge badge-izin">Izin</span>
                                            @elseif ($attendance->status === 'sakit')
                                                <span class="badge badge-sakit">Sakit</span>
                                            @endif
                                        </td>
                                        <td style="text-align: right;">
                                            <span class="badge badge-success"><i class="fas fa-check-circle"></i> Presensi Tercatat</span>
                                        </td>
                                    </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    @endif
</div>

<!-- Riwayat Absensi Terbaru (Ringkas & Nyaman di HP) -->
<div class="card mb-6">
    <div class="card-header">
        <h2 class="card-title">
            <i class="fas fa-history text-primary"></i> Riwayat Absensi Terbaru
        </h2>
        <a href="{{ route('portal.attendance') }}" class="btn btn-ghost btn-sm" style="font-weight: 600; color: var(--blue-primary);">
            Lihat Semua <i class="fas fa-arrow-right"></i>
        </a>
    </div>

    @if ($recentAttendances->isEmpty())
        <div class="empty-state" style="padding: var(--space-6) var(--space-4);">
            <i class="fas fa-history"></i>
            <h3>Belum Ada Riwayat</h3>
            <p>Riwayat kehadiran akan otomatis muncul setelah rapat selesai.</p>
        </div>
    @else
        <div class="attendance-history-list">
            @foreach ($recentAttendances as $attendance)
                @if ($attendance->meeting)
                    @php
                        $catLabel = match($attendance->meeting->jenis_rapat) {
                            'internal_dept' => 'Internal ' . ($attendance->meeting->departemen ?? 'Dept'),
                            'kepanitiaan' => 'Kepanitiaan',
                            'bph' => 'BPH / Inti',
                            'lintas_dept' => 'Lintas Dept',
                            'perdana' => 'Rapat Perdana',
                            default => $attendance->meeting->jenis_label,
                        };
                        $methodLabel = match($attendance->scan_method) {
                            'qr_scan' => 'Scan QR',
                            'online_link' => 'Online',
                            'absent_form' => 'Form Izin',
                            default => 'Presensi',
                        };
                    @endphp
                    <div class="attendance-history-item" style="flex-direction: column; align-items: stretch; gap: 4px; padding: 11px 13px;">
                        <div style="display: flex; justify-content: space-between; width: 100%; align-items: center; gap: 8px;">
                            <div class="attendance-history-name" style="margin: 0; font-size: 0.92rem; font-weight: 700; color: var(--text-primary); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; flex: 1;">
                                {{ $attendance->meeting->fokus_bahasan ?? $attendance->meeting->nama_event ?? $attendance->meeting->jenis_label }}
                            </div>
                            @if ($attendance->status === 'hadir')
                                <span class="chip-status chip-hadir"><i class="fas fa-check"></i> Hadir</span>
                            @elseif ($attendance->status === 'izin')
                                <span class="chip-status chip-izin">Izin</span>
                            @elseif ($attendance->status === 'sakit')
                                <span class="chip-status chip-sakit">Sakit</span>
                            @else
                                <span class="chip-status chip-alpha">Alpha</span>
                            @endif
                        </div>
                        <div class="attendance-history-meta" style="display: flex; align-items: center; gap: 6px; font-size: 0.74rem; color: var(--text-muted); flex-wrap: wrap; margin: 0;">
                            <span class="meeting-meta-cat">{{ $catLabel }}</span>
                            <span class="meta-dot">&bull;</span>
                            <span>{{ $attendance->meeting->tanggal->locale('id')->translatedFormat('d M Y') }}</span>
                            <span class="meta-dot">&bull;</span>
                            <span>{{ $methodLabel }}</span>
                        </div>
                    </div>
                @endif
            @endforeach
        </div>
    @endif
</div>

<!-- Kepanitiaan Saya (Hanya Ditampilkan jika Terdaftar) -->
@if ($eventMemberships->isNotEmpty())
    <div class="card mb-6">
        <div class="card-header">
            <h2 class="card-title"><i class="fas fa-users text-primary"></i> Kepanitiaan Saya</h2>
            <span class="badge badge-primary">{{ $eventMemberships->count() }} event</span>
        </div>
        <div class="attendance-history-list">
            @foreach ($eventMemberships as $membership)
                <div class="attendance-history-item" style="display: flex; justify-content: space-between; align-items: center; gap: 10px; padding: 11px 13px;">
                    <div class="attendance-history-left">
                        <div class="attendance-history-name" style="font-size: 0.92rem; font-weight: 700; color: var(--text-primary);">{{ $membership->event?->nama_event ?? '-' }}</div>
                        <div class="attendance-history-meta" style="font-size: 0.74rem; color: var(--text-muted); margin-top: 2px;">
                            <span><i class="fas fa-layer-group text-primary"></i> Divisi {{ $membership->division?->nama_divisi ?? 'Umum' }}</span>
                        </div>
                    </div>
                    <div>
                        <span class="chip-status chip-izin" style="font-size: 0.72rem;">{{ $membership->jabatan_panitia }}</span>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endif
@endsection

@push('scripts')
<script>
function switchMeetingTab(tab) {
    const btnBelum = document.getElementById('tabBtnBelum');
    const btnSudah = document.getElementById('tabBtnSudah');
    const panelBelum = document.getElementById('panelBelumAbsen');
    const panelSudah = document.getElementById('panelSudahAbsen');

    if (!btnBelum || !btnSudah || !panelBelum || !panelSudah) return;

    if (tab === 'belum') {
        btnBelum.classList.add('active');
        btnSudah.classList.remove('active');
        panelBelum.style.display = 'block';
        panelSudah.style.display = 'none';
    } else {
        btnSudah.classList.add('active');
        btnBelum.classList.remove('active');
        panelSudah.style.display = 'block';
        panelBelum.style.display = 'none';
    }
}

function dismissBannerCard(id) {
    const card = document.getElementById('banner-card-' + id);
    if (!card) return;

    card.style.opacity = '0.5';
    card.style.pointerEvents = 'none';

    fetch(`{{ url('/portal/notifikasi') }}/${id}/dismiss`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        card.style.transition = 'all 0.3s ease';
        card.style.transform = 'translateY(-8px)';
        card.style.opacity = '0';
        setTimeout(() => {
            card.remove();
            const container = document.getElementById('portalAnnouncementsContainer');
            if (container && container.children.length === 0) {
                container.remove();
            }
        }, 300);
    })
    .catch(error => {
        card.style.opacity = '1';
        card.style.pointerEvents = 'auto';
        console.error('Error dismissing banner:', error);
    });
}
</script>
@endpush
