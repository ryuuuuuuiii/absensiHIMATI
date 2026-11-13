@extends('layouts.portal')

@section('title', 'Track Record: ' . $member->nama_lengkap)
@section('page_icon')
    <i class="fas fa-clipboard-user"></i>
@endsection

@section('content')
<!-- Navigasi Balik & Breadcrumb -->
<div class="portal-breadcrumb mb-4">
    <a href="{{ route('portal.members.index') }}" class="btn btn-secondary btn-sm" style="display: inline-flex; align-items: center; gap: 8px;">
        <i class="fas fa-arrow-left"></i>
        <span>Kembali ke Daftar</span>
    </a>
    <div style="font-size: 0.85rem; color: var(--text-muted); display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
        <a href="{{ route('portal.dashboard') }}" style="color: var(--text-muted); text-decoration: none;">Dashboard</a>
        <span>/</span>
        <a href="{{ route('portal.members.index') }}" style="color: var(--text-muted); text-decoration: none;">
            {{ $current->isBphInti() ? 'Data Pengurus' : 'Anggota Dept' }}
        </a>
        <span>/</span>
        <span style="color: var(--text-primary); font-weight: 600;">{{ $member->nama_lengkap }}</span>
    </div>
</div>

<!-- Banner Indikator Read-Only -->
<div class="alert alert-info mb-6" style="display: flex; align-items: flex-start; gap: 12px; background: rgba(48, 175, 255, 0.08); border-left: 4px solid var(--blue-primary); border-radius: var(--radius-md); padding: 12px 16px;">
    <i class="fas fa-eye" style="font-size: 1.25rem; color: var(--blue-primary); margin-top: 2px;"></i>
    <div style="font-size: 0.84rem; line-height: 1.5; color: var(--text-primary);">
        <strong>Mode Pantau Track Record (Read-Only)</strong><br>
        <span style="color: var(--text-muted);">
            Anda sedang meninjau rekam jejak kepengurusan lengkap untuk <strong>{{ $member->nama_lengkap }}</strong>. Halaman ini bersifat <em>khusus pengawasan</em> tanpa formulir untuk mengubah atau menghapus catatan.
        </span>
    </div>
</div>

<!-- Header Profil Pengurus Dossier -->
<div class="card mb-6" style="padding: 20px;">
    <div class="member-dossier-header">
        <div class="member-dossier-avatar">
            @if ($member->profile_photo_url)
                <img src="{{ $member->profile_photo_url }}" alt="{{ $member->nama_lengkap }}">
            @else
                <div class="member-avatar-initials-lg">{{ $member->initials }}</div>
            @endif
        </div>

        <div class="member-dossier-details">
            <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap; margin-bottom: 6px;">
                <h1 style="font-size: 1.45rem; font-weight: 800; color: var(--text-primary); margin: 0;">
                    {{ $member->nama_lengkap }}
                </h1>
                @if ($member->is_active && $member->sp_level < 3)
                    <span class="badge badge-success"><i class="fas fa-check-circle"></i> Akun Aktif</span>
                @else
                    <span class="badge badge-danger"><i class="fas fa-ban"></i> Nonaktif / Diberhentikan</span>
                @endif
            </div>

            <div class="member-dossier-meta-pills mb-3">
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
            </div>

            <!-- Detail Info Grid -->
            <div class="member-dossier-info-grid">
                <div class="info-item">
                    <span class="info-label"><i class="fas fa-id-card"></i> NIM</span>
                    <span class="info-val">{{ $member->nim ?? '-' }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label"><i class="fas fa-barcode"></i> NRP</span>
                    <span class="info-val">{{ $member->nrp ?? '-' }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label"><i class="fas fa-graduation-cap"></i> Angkatan</span>
                    <span class="info-val">{{ $member->angkatan ?? '-' }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label"><i class="fas fa-envelope"></i> Email</span>
                    <span class="info-val">{{ $member->email ?? '-' }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label"><i class="fas fa-birthday-cake"></i> Tanggal Lahir</span>
                    <span class="info-val">
                        {{ $member->ttl ? \Carbon\Carbon::parse($member->ttl)->locale('id')->translatedFormat('d F Y') : '-' }}
                    </span>
                </div>
                <div class="info-item">
                    <span class="info-label"><i class="fas fa-history"></i> Login Terakhir</span>
                    <span class="info-val">
                        {{ $member->last_login_at ? $member->last_login_at->diffForHumans() . ' (' . $member->last_login_at->format('d M Y H:i') . ')' : 'Belum pernah login' }}
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Row: Kedisiplinan SP & Gamifikasi Level -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: var(--space-4);" class="mb-6">
    <!-- Card Kedisiplinan & SP -->
    <div class="card" style="padding: 18px;">
        <div class="card-header" style="margin-bottom: 12px; padding: 0; border: none;">
            <h3 class="card-title" style="font-size: 1rem;"><i class="fas fa-balance-scale"></i> Kedisiplinan & Status SP</h3>
            @if ($member->sp_level == 1)
                <span class="badge badge-sp-1">SP 1</span>
            @elseif ($member->sp_level == 2)
                <span class="badge badge-sp-2">SP 2</span>
            @elseif ($member->sp_level >= 3)
                <span class="badge badge-sp-3">SP 3 (Diberhentikan)</span>
            @else
                <span class="badge badge-success"><i class="fas fa-shield-alt"></i> Bersih (Aman)</span>
            @endif
        </div>

        @php
            $alphaStreak = $member->consecutive_alpha_count;
        @endphp

        <div style="background: var(--bg-surface-subtle); padding: 12px; border-radius: 8px; margin-bottom: 12px; border: 1px solid var(--border-color);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                <span style="font-size: 0.8rem; font-weight: 600; color: var(--text-muted);">Streak Alpha Berturut-turut:</span>
                <span class="badge {{ $alphaStreak >= 2 ? 'badge-danger' : ($alphaStreak === 1 ? 'badge-warning' : 'badge-success') }}">
                    {{ $alphaStreak }} / 3 Kegiatan
                </span>
            </div>
            <p style="margin: 0; font-size: 0.76rem; color: var(--text-muted); line-height: 1.4;">
                @if ($alphaStreak === 0)
                    Pengurus tertib mengikuti kegiatan atau mengajukan izin resmi pada rapat-rapat terakhir.
                @elseif ($alphaStreak === 1)
                    Tercatat 1 kali Alpha. Jika mencapai 3 kali berturut-turut, SP akan diterbitkan otomatis.
                @else
                    <strong>Peringatan:</strong> Pengurus tercatat {{ $alphaStreak }} kali berturut-turut Alpha!
                @endif
            </p>
        </div>

        @if ($member->sp_level > 0)
            <div class="sp-portal-alert sp-level-{{ $member->sp_level }}" style="margin: 0; padding: 12px;">
                <div class="sp-portal-alert-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="sp-portal-alert-body">
                    <div style="font-weight: 700; font-size: 0.85rem; margin-bottom: 4px;">
                        {{ $member->sp_full_label }}
                    </div>
                    <div style="font-size: 0.78rem; color: var(--text-muted); margin-bottom: 4px;">
                        <strong>Sumber:</strong> {{ $member->sp_source_label ?? 'Organisasi' }}
                    </div>
                    <div style="font-size: 0.78rem; color: var(--text-muted); margin-bottom: 4px;">
                        <strong>Alasan:</strong> {{ $member->sp_reason_display }}
                    </div>
                    @if ($member->sp_issued_at)
                        <div style="font-size: 0.72rem; color: var(--text-muted);">
                            <i class="far fa-calendar-alt"></i> Diterbitkan: {{ $member->sp_issued_at->format('d M Y H:i') }}
                        </div>
                    @endif
                </div>
            </div>
        @else
            <div style="display: flex; align-items: center; gap: 10px; color: var(--text-muted); font-size: 0.8rem; padding: 4px 0;">
                <i class="fas fa-check-circle" style="color: #10B981; font-size: 1.1rem;"></i>
                <span>Tidak ada catatan pelanggaran aktif untuk pengurus ini.</span>
            </div>
        @endif
    </div>

    <!-- Card Gamifikasi & Lencana Acara -->
    @php $lvl = $member->level_data; @endphp
    <div class="card" style="padding: 18px;">
        <div class="card-header" style="margin-bottom: 12px; padding: 0; border: none;">
            <h3 class="card-title" style="font-size: 1rem;"><i class="fas fa-gamepad"></i> Level & Gamifikasi</h3>
            <span class="exp-total-pill"><i class="fas fa-bolt"></i> {{ number_format($lvl['exp']) }} EXP</span>
        </div>

        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
            <span class="level-icon-badge" style="background: {{ $lvl['badge_bg'] }}; width: 44px; height: 44px;">
                <img src="{{ $lvl['tier_icon_url'] }}" alt="{{ $lvl['title'] }}" class="tier-minecraft-icon" style="width: 24px; height: 24px;">
            </span>
            <div>
                <div style="font-weight: 800; font-size: 1.1rem; color: var(--text-primary);">
                    Lv. {{ $lvl['level'] }} &bull; {{ $lvl['title'] }} {{ $lvl['sub_rank'] }}
                </div>
                <div style="font-size: 0.76rem; color: var(--text-muted);">
                    Tier {{ $lvl['tier_name'] }} Minecraft &bull; Progres: {{ $lvl['percentage'] }}%
                </div>
            </div>
        </div>

        <div class="level-bar-track" style="margin-bottom: 12px;">
            <div class="level-bar-fill" style="width: {{ $lvl['percentage'] }}%;"></div>
        </div>

        <!-- Lencana Acara (Badges) -->
        <div style="border-top: 1px solid var(--border-color); padding-top: 10px;">
            <span style="font-size: 0.78rem; font-weight: 700; color: var(--text-muted); display: block; margin-bottom: 8px;">
                <i class="fas fa-award"></i> Lencana Kegiatan ({{ $badges->count() }})
            </span>
            @if ($badges->isEmpty())
                <p style="margin: 0; font-size: 0.75rem; color: var(--text-muted); font-style: italic;">
                    Belum ada lencana kegiatan yang diperoleh.
                </p>
            @else
                <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                    @foreach ($badges as $badge)
                        <span class="badge badge-primary" style="display: inline-flex; align-items: center; gap: 6px; font-size: 0.75rem; padding: 4px 10px;" title="{{ $badge->event?->nama_event ?? 'Kegiatan' }}">
                            <i class="fas fa-medal" style="color: #F59E0B;"></i>
                            <span>{{ $badge->badge_name ?? $badge->event?->nama_event ?? 'Lencana' }}</span>
                        </span>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Statistik Kehadiran Rapat -->
<div class="stat-cards mb-6">
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
    <div class="stat-card stat-card-alpha">
        <div class="stat-icon stat-icon-red"><i class="fas fa-user-times"></i></div>
        <div class="stat-info">
            <h3 class="stat-num-red">{{ $stats['alpha'] }}</h3>
            <p>Alpha</p>
        </div>
    </div>
</div>

<!-- Riwayat Rapat Lengkap (Track Record) -->
<div class="card mb-6">
    <div class="card-header">
        <div>
            <h3 class="card-title"><i class="fas fa-history"></i> Riwayat Lengkap Kegiatan & Rapat</h3>
            <p style="margin: 2px 0 0; font-size: 0.78rem; color: var(--text-muted);">
                Daftar semua rapat yang telah diikuti atau tercatat atas nama {{ $member->nama_lengkap }}.
            </p>
        </div>
        <span class="badge badge-primary">{{ $attendances->count() }} Pertemuan</span>
    </div>

    @if ($attendances->isEmpty())
        <div class="empty-state" style="padding: 36px 20px; text-align: center;">
            <i class="fas fa-calendar-times" style="font-size: 2.5rem; color: var(--text-muted); margin-bottom: 10px; display: block;"></i>
            <h4 style="margin-bottom: 4px;">Belum Ada Riwayat Presensi</h4>
            <p style="font-size: 0.82rem; color: var(--text-muted); margin: 0;">Pengurus ini belum terdaftar pada rapat yang telah diselesaikan.</p>
        </div>
    @else
        <!-- Mobile View (Card List) -->
        <div class="portal-cards-mobile attendance-history-list mb-3">
            @foreach ($attendances as $att)
                @php
                    $m = $att->meeting;
                    $catLabel = match($m?->jenis_rapat) {
                        'internal_dept' => 'Internal ' . ($m?->departemen ?? 'Dept'),
                        'kepanitiaan' => 'Kepanitiaan',
                        'bph' => 'BPH / Inti',
                        'lintas_dept' => 'Lintas Dept',
                        'perdana' => 'Rapat Perdana',
                        default => $m?->jenis_label ?? 'Rapat',
                    };
                    $methodLabel = match($att->scan_method) {
                        'qr_scan' => 'Scan QR',
                        'online_link' => 'Online Mandiri',
                        'absent_form' => 'Form Izin/Sakit',
                        'manual' => 'Input Manual',
                        default => $att->scan_method ? ucfirst($att->scan_method) : '-',
                    };
                @endphp
                <div class="attendance-history-item" style="flex-direction: column; align-items: stretch; gap: 6px; padding: 12px 14px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; gap: 8px;">
                        <h4 style="margin: 0; font-size: 0.92rem; font-weight: 700; color: var(--text-primary); flex: 1;">
                            {{ $m?->fokus_bahasan ?? $m?->nama_event ?? $m?->jenis_label ?? 'Rapat' }}
                        </h4>
                        @if ($att->status === 'hadir')
                            <span class="chip-status chip-hadir"><i class="fas fa-check"></i> Hadir</span>
                        @elseif ($att->status === 'izin')
                            <span class="chip-status chip-izin"><i class="fas fa-info-circle"></i> Izin</span>
                        @elseif ($att->status === 'sakit')
                            <span class="chip-status chip-sakit"><i class="fas fa-heartbeat"></i> Sakit</span>
                        @else
                            <span class="chip-status chip-alpha"><i class="fas fa-times"></i> Alpha</span>
                        @endif
                    </div>

                    <div style="display: flex; align-items: center; gap: 6px; font-size: 0.74rem; color: var(--text-muted); flex-wrap: wrap;">
                        <span class="meeting-meta-cat">{{ $catLabel }}</span>
                        <span class="meta-dot">&bull;</span>
                        <span>{{ $m?->tanggal?->locale('id')->translatedFormat('d M Y') ?? '-' }}</span>
                        <span class="meta-dot">&bull;</span>
                        <span>{{ $m?->mode_rapat === 'online' ? 'Online' : 'Offline' }}</span>
                        <span class="meta-dot">&bull;</span>
                        <span>{{ $methodLabel }}</span>
                    </div>

                    @if ($att->scanned_at)
                        <div style="font-size: 0.72rem; color: var(--text-muted);">
                            <i class="far fa-clock"></i> Masuk: {{ $att->scanned_at->format('d M Y, H:i') }} WITA
                        </div>
                    @endif

                    @if ($att->keterangan)
                        <div style="font-size: 0.75rem; color: var(--text-muted); background: var(--bg-surface-subtle); padding: 5px 8px; border-radius: 4px; border-left: 2px solid var(--blue-primary);">
                            <strong>Ket:</strong> {{ $att->keterangan }}
                        </div>
                    @endif

                    @if ($att->bukti_foto)
                        <div style="margin-top: 4px;">
                            <button type="button" class="btn btn-secondary btn-sm" onclick="previewProofModal('{{ asset('uploads/bukti/' . $att->bukti_foto) }}', '{{ addslashes($m?->fokus_bahasan ?? 'Bukti Izin/Sakit') }}')" style="font-size: 0.74rem; padding: 3px 8px;">
                                <i class="fas fa-paperclip"></i> Lihat Bukti Surat / Foto
                            </button>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        <!-- Desktop View (Table) -->
        <div class="table-wrapper portal-table-desktop">
            <table class="table">
                <thead>
                    <tr>
                        <th style="width: 30%;">Rapat & Fokus Bahasan</th>
                        <th>Kategori & Mode</th>
                        <th>Tanggal & Waktu</th>
                        <th>Status</th>
                        <th>Metode & Jam Presensi</th>
                        <th>Keterangan & Bukti</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($attendances as $att)
                        @php
                            $m = $att->meeting;
                            $catLabel = match($m?->jenis_rapat) {
                                'internal_dept' => 'Internal ' . ($m?->departemen ?? 'Dept'),
                                'kepanitiaan' => 'Kepanitiaan',
                                'bph' => 'BPH / Inti',
                                'lintas_dept' => 'Lintas Dept',
                                'perdana' => 'Rapat Perdana',
                                default => $m?->jenis_label ?? 'Rapat',
                            };
                            $methodLabel = match($att->scan_method) {
                                'qr_scan' => 'Scan QR',
                                'online_link' => 'Online Mandiri',
                                'absent_form' => 'Form Izin/Sakit',
                                'manual' => 'Input Manual',
                                default => $att->scan_method ? ucfirst($att->scan_method) : '-',
                            };
                        @endphp
                        <tr>
                            <td>
                                <div style="font-weight: 700; color: var(--text-primary);">
                                    {{ $m?->fokus_bahasan ?? $m?->nama_event ?? $m?->jenis_label ?? 'Rapat' }}
                                </div>
                                <small style="color: var(--text-muted);">
                                    Dibuat oleh: {{ $m?->creator?->nama_lengkap ?? 'Admin/Sistem' }}
                                </small>
                            </td>
                            <td>
                                <span class="badge badge-secondary" style="font-size: 0.72rem;">{{ $catLabel }}</span>
                                <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 2px;">
                                    <i class="fas fa-{{ $m?->mode_rapat === 'online' ? 'globe' : 'building' }}"></i>
                                    {{ ucfirst($m?->mode_rapat ?? 'offline') }}
                                </div>
                            </td>
                            <td>
                                <div style="font-weight: 600;">
                                    {{ $m?->tanggal?->locale('id')->translatedFormat('d M Y') ?? '-' }}
                                </div>
                                <small style="color: var(--text-muted);">
                                    {{ $m?->waktu_mulai ? substr($m->waktu_mulai, 0, 5) . ' WITA' : '-' }}
                                </small>
                            </td>
                            <td>
                                @if ($att->status === 'hadir')
                                    <span class="badge badge-success"><i class="fas fa-check"></i> Hadir</span>
                                @elseif ($att->status === 'izin')
                                    <span class="badge badge-warning"><i class="fas fa-info-circle"></i> Izin</span>
                                @elseif ($att->status === 'sakit')
                                    <span class="badge badge-warning" style="background: rgba(239, 68, 68, 0.15); color: #EF4444;"><i class="fas fa-heartbeat"></i> Sakit</span>
                                @else
                                    <span class="badge badge-danger"><i class="fas fa-times"></i> Alpha</span>
                                @endif
                            </td>
                            <td>
                                <div style="font-weight: 600; font-size: 0.8rem;">
                                    {{ $methodLabel }}
                                </div>
                                <small style="color: var(--text-muted);">
                                    {{ $att->scanned_at ? $att->scanned_at->format('H:i') . ' WITA' : '-' }}
                                </small>
                            </td>
                            <td>
                                @if ($att->keterangan)
                                    <div style="font-size: 0.78rem; color: var(--text-primary); margin-bottom: 4px;">
                                        {{ $att->keterangan }}
                                    </div>
                                @endif
                                @if ($att->bukti_foto)
                                    <button type="button" class="btn btn-secondary btn-sm" onclick="previewProofModal('{{ asset('uploads/bukti/' . $att->bukti_foto) }}', '{{ addslashes($m?->fokus_bahasan ?? 'Bukti Izin/Sakit') }}')" style="font-size: 0.72rem; padding: 2px 8px;">
                                        <i class="fas fa-paperclip"></i> Lihat Bukti
                                    </button>
                                @elseif (! $att->keterangan)
                                    <span style="color: var(--text-muted); font-size: 0.75rem;">-</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

<!-- Grid: Kepanitiaan Acara & Rapat yang Pernah Dibuat -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: var(--space-4);">
    <!-- Kepanitiaan Acara -->
    <div class="card" style="padding: 18px;">
        <div class="card-header" style="margin-bottom: 12px; padding: 0; border: none;">
            <h3 class="card-title" style="font-size: 1rem;"><i class="fas fa-calendar-alt"></i> Kepanitiaan Kegiatan</h3>
            <span class="badge badge-primary">{{ $eventMemberships->count() }} Acara</span>
        </div>

        @if ($eventMemberships->isEmpty())
            <p style="color: var(--text-muted); font-size: 0.8rem; margin: 0; font-style: italic;">
                Belum terdaftar dalam susunan kepanitiaan acara.
            </p>
        @else
            <div style="display: flex; flex-direction: column; gap: 10px;">
                @foreach ($eventMemberships as $membership)
                    <div style="background: var(--bg-surface-subtle); padding: 10px 12px; border-radius: 8px; border: 1px solid var(--border-color);">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 8px;">
                            <div style="font-weight: 700; font-size: 0.88rem; color: var(--text-primary);">
                                {{ $membership->event?->nama_event ?? 'Acara' }}
                            </div>
                            <span class="badge badge-secondary" style="font-size: 0.7rem;">
                                {{ ucfirst($membership->peran ?? 'Anggota') }}
                            </span>
                        </div>
                        <div style="font-size: 0.76rem; color: var(--text-muted); margin-top: 4px;">
                            <i class="fas fa-users"></i> Divisi: <strong>{{ $membership->division?->nama_divisi ?? '-' }}</strong>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- Rapat yang Pernah Dibuat -->
    <div class="card" style="padding: 18px;">
        <div class="card-header" style="margin-bottom: 12px; padding: 0; border: none;">
            <h3 class="card-title" style="font-size: 1rem;"><i class="fas fa-calendar-plus"></i> Rapat yang Dibuat</h3>
            <span class="badge badge-primary">{{ $meetingsCreated->count() }} Rapat</span>
        </div>

        @if ($meetingsCreated->isEmpty())
            <p style="color: var(--text-muted); font-size: 0.8rem; margin: 0; font-style: italic;">
                Belum pernah membuat agenda rapat.
            </p>
        @else
            <div style="display: flex; flex-direction: column; gap: 10px;">
                @foreach ($meetingsCreated as $meeting)
                    <div style="background: var(--bg-surface-subtle); padding: 10px 12px; border-radius: 8px; border: 1px solid var(--border-color);">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 8px;">
                            <div style="font-weight: 700; font-size: 0.88rem; color: var(--text-primary);">
                                {{ $meeting->fokus_bahasan ?? $meeting->nama_event ?? 'Rapat' }}
                            </div>
                            <span class="badge badge-{{ $meeting->status === 'completed' ? 'success' : 'warning' }}" style="font-size: 0.7rem;">
                                {{ ucfirst($meeting->status) }}
                            </span>
                        </div>
                        <div style="font-size: 0.76rem; color: var(--text-muted); margin-top: 4px;">
                            <i class="far fa-calendar"></i> {{ $meeting->tanggal?->format('d M Y') ?? '-' }} &bull;
                            <i class="fas fa-{{ $meeting->mode_rapat === 'online' ? 'globe' : 'building' }}"></i> {{ ucfirst($meeting->mode_rapat) }}
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>

<!-- Modal Preview Bukti Foto -->
<div class="portal-modal-overlay" id="proofPreviewModal" onclick="closeProofModal(event)" style="display: none; position: fixed; inset: 0; background: rgba(0, 0, 0, 0.75); z-index: 9999; align-items: center; justify-content: center; padding: 20px;">
    <div class="portal-modal-dialog" onclick="event.stopPropagation()" style="background: var(--bg-surface); border: 2px solid var(--border-color); border-radius: 12px; max-width: 560px; width: 100%; overflow: hidden; box-shadow: 0 12px 36px rgba(0,0,0,0.5);">
        <div style="display: flex; justify-content: space-between; align-items: center; padding: 14px 18px; border-bottom: 1px solid var(--border-color);">
            <h4 id="proofModalTitle" style="margin: 0; font-size: 0.95rem; font-weight: 700; color: var(--text-primary);">
                Lampiran Bukti Izin / Sakit
            </h4>
            <button type="button" onclick="closeProofModal()" style="background: none; border: none; font-size: 1.2rem; cursor: pointer; color: var(--text-muted);">
                &times;
            </button>
        </div>
        <div style="padding: 16px; text-align: center; max-height: 75vh; overflow-y: auto;">
            <img id="proofModalImg" src="" alt="Bukti Izin/Sakit" style="max-width: 100%; height: auto; border-radius: 8px; border: 1px solid var(--border-color);">
        </div>
        <div style="padding: 12px 18px; border-top: 1px solid var(--border-color); text-align: right;">
            <a id="proofDownloadLink" href="#" target="_blank" download class="btn btn-primary btn-sm">
                <i class="fas fa-download"></i> Buka Gambar Penuh
            </a>
            <button type="button" class="btn btn-secondary btn-sm" onclick="closeProofModal()">
                Tutup
            </button>
        </div>
    </div>
</div>

@push('styles')
<style>
.portal-breadcrumb {
    display: flex;
    align-items: center;
    gap: 16px;
    flex-wrap: wrap;
}

.member-dossier-header {
    display: flex;
    align-items: flex-start;
    gap: 20px;
}

.member-dossier-avatar {
    width: 84px;
    height: 84px;
    border-radius: 50%;
    overflow: hidden;
    flex-shrink: 0;
    border: 3px solid var(--blue-primary);
    background: var(--bg-surface-subtle);
    display: flex;
    align-items: center;
    justify-content: center;
}

.member-dossier-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.member-avatar-initials-lg {
    font-size: 1.8rem;
    font-weight: 800;
    color: var(--blue-primary);
}

.member-dossier-details {
    flex: 1;
    min-width: 0;
}

.member-dossier-meta-pills {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}

.member-dossier-info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 10px 16px;
    margin-top: 12px;
    padding-top: 12px;
    border-top: 1px solid var(--border-color);
}

.info-item {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.info-label {
    font-size: 0.72rem;
    font-weight: 600;
    color: var(--text-muted);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.info-val {
    font-size: 0.86rem;
    font-weight: 600;
    color: var(--text-primary);
    word-break: break-word;
}

@media (max-width: 640px) {
    .member-dossier-header {
        flex-direction: column;
        align-items: center;
        text-align: center;
    }

    .member-dossier-meta-pills {
        justify-content: center;
    }

    .member-dossier-info-grid {
        text-align: left;
    }
}
</style>
@endpush

@push('scripts')
<script>
function previewProofModal(imgUrl, title) {
    const modal = document.getElementById('proofPreviewModal');
    const img = document.getElementById('proofModalImg');
    const titleEl = document.getElementById('proofModalTitle');
    const dlLink = document.getElementById('proofDownloadLink');

    if (modal && img) {
        img.src = imgUrl;
        if (titleEl && title) {
            titleEl.textContent = 'Bukti: ' + title;
        }
        if (dlLink) {
            dlLink.href = imgUrl;
        }
        modal.style.display = 'flex';
    }
}

function closeProofModal(e) {
    const modal = document.getElementById('proofPreviewModal');
    if (modal) {
        modal.style.display = 'none';
    }
}
</script>
@endpush
@endsection