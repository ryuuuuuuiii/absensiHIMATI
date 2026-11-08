@extends('layouts.app')

@section('title', 'Detail Rapat')

@section('page_icon')
    <i class="fas fa-clipboard-list"></i>
@endsection

@section('content')
<!-- Meeting Info Banner -->
<div class="card mb-6 meeting-header-banner">
    <div class="meeting-header-content">
        <div class="meeting-header-details">
            <div class="meeting-type-badge-row">
                @if($meeting->mode_rapat === 'online')
                    <span class="badge badge-info"><i class="fas fa-video"></i> Online</span>
                @else
                    <span class="badge badge-secondary"><i class="fas fa-building"></i> Offline</span>
                @endif

                @if($meeting->status === 'scanning')
                    <span class="badge badge-success badge-pulse"><span class="pulse-dot"></span> Presensi Aktif</span>
                @elseif($meeting->status === 'completed')
                    <span class="badge badge-secondary"><i class="fas fa-check"></i> Selesai</span>
                @elseif($meeting->status === 'draft')
                    <span class="badge badge-warning"><i class="fas fa-edit"></i> Draft</span>
                @else
                    <span class="badge badge-danger"><i class="fas fa-ban"></i> Batal</span>
                @endif

                @if($meeting->nama_event)
                    <span class="badge badge-primary"><i class="fas fa-calendar-check"></i> {{ $meeting->jenis_label }}</span>
                @endif

                @if(!empty($meeting->departemen_collab))
                    <span class="badge badge-purple"><i class="fas fa-handshake"></i> Kolaborasi</span>
                @endif
            </div>

            <h2 class="meeting-banner-title">
                @if($meeting->nama_event)
                    {{ $meeting->nama_event }}{{ $meeting->divisi_rapat ? ' — Divisi ' . $meeting->divisi_rapat : '' }}
                @elseif($meeting->departemen)
                    {{ $meeting->jenis_label }} — {{ $meeting->departemen }}
                @else
                    {{ $meeting->jenis_label }}
                @endif
            </h2>

            <div class="meeting-meta-row">
                <span class="meta-item">
                    <i class="far fa-calendar-alt"></i>
                    <span>{{ $meeting->tanggal->locale('id')->translatedFormat('d F Y') }}</span>
                </span>
                <span class="meta-separator">•</span>
                <span class="meta-item">
                    <i class="far fa-clock"></i>
                    <span>{{ substr($meeting->waktu_mulai, 0, 5) }} WITA{{ $meeting->waktu_selesai ? ' - ' . substr($meeting->waktu_selesai, 0, 5) . ' WITA' : '' }}</span>
                </span>
                <span class="meta-separator">•</span>
                <span class="meta-item">
                    <i class="far fa-user"></i>
                    <span>Dibuat oleh: <strong>{{ $meeting->creator ? $meeting->creator->nama_lengkap : 'Admin' }}</strong></span>
                </span>
            </div>

            @if ($meeting->fokus_bahasan)
                <div class="meeting-agenda-note mb-4">
                    <i class="fas fa-align-left agenda-icon"></i>
                    <div class="agenda-body">
                        <span class="agenda-label">Agenda:</span>
                        <span class="agenda-text">{{ $meeting->fokus_bahasan }}</span>
                    </div>
                </div>
            @endif
                <div style="display: flex; gap: var(--space-3); align-items: center; flex-wrap: wrap;">
            @if($meeting->status === 'scanning' && $meeting->mode_rapat === 'offline')
                <a href="{{ route('meetings.scan', $meeting->id) }}" class="btn btn-primary">
                    <i class="fas fa-camera"></i> Buka Scanner
                </a>
            @elseif($meeting->mode_rapat === 'online')
                <a href="{{ route('public.online', $meeting->unique_link) }}" target="_blank" class="btn btn-primary">
                    <i class="fas fa-globe"></i> Buka Presensi Online
                </a>
            @endif

            @if($meeting->status === 'scanning')
                <form action="{{ route('meetings.finish', $meeting->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menyelesaikan rapat ini? Peserta yang belum hadir akan dicatat sebagai Alpha.');" style="margin: 0;">
                    @csrf
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-stop-circle"></i> Selesaikan Rapat
                    </button>
                </form>

                <form action="{{ route('meetings.cancel', $meeting->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin membatalkan sesi rapat ini? Anggota tidak akan dicatat Alpha.');" style="margin: 0;">
                    @csrf
                    <button type="submit" class="btn btn-secondary">
                        <i class="fas fa-ban"></i> Batalkan Rapat
                    </button>
                </form>
            @endif

            <form action="{{ route('meetings.sync_sheets', $meeting->id) }}" method="POST" style="margin: 0;">
                @csrf
                <button type="submit" class="btn btn-secondary" title="Kirim data kehadiran ke Google Sheets">
                    <i class="fab fa-google"></i> Sync Sheets
                </button>
            </form>
        </div>
    </div>
    
    <div class="meeting-info-grid" style="margin-top: var(--space-5); margin-bottom: 0;">
        <div class="meeting-info-item">
            <label><i class="fas fa-calendar"></i> Tanggal</label>
            <p>{{ $meeting->tanggal->locale('id')->translatedFormat('d F Y') }}</p>
        </div>
        <div class="meeting-info-item">
            <label><i class="fas fa-clock"></i> Waktu</label>
            <p>{{ substr($meeting->waktu_mulai, 0, 5) }} WITA {{ $meeting->waktu_selesai ? ' - ' . substr($meeting->waktu_selesai, 0, 5) . ' WITA' : '' }}</p>
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
    </div>
    

    <!-- Share Links -->
    <div style="margin-top: var(--space-4); padding-top: var(--space-4); border-top: 1px solid var(--dark-border);">
        <label class="form-label" style="font-size: var(--font-xs); color: var(--text-muted);">
            <i class="fas fa-share-alt"></i> {{ $meeting->mode_rapat === 'online' ? 'Link Presensi Online (Bagikan ke Peserta)' : 'Link Izin / Sakit (Bagikan ke yang Berhalangan)' }}
        </label>
        @if ($meeting->mode_rapat === 'online')
            <div class="share-link-box">
                <input type="text" value="{{ route('public.online', $meeting->unique_link) }}" readonly>
                <button onclick="copyToClipboard('{{ route('public.online', $meeting->unique_link) }}', this)" title="Salin Link Presensi Online">
                    <i class="fas fa-copy"></i> Salin Link Online
                </button>
            </div>
        @else
            <div class="share-link-box">
                <input type="text" value="{{ route('public.absent', $meeting->absent_link) }}" readonly>
                <button onclick="copyToClipboard('{{ route('public.absent', $meeting->absent_link) }}', this)" title="Salin Link Izin/Sakit">
                    <i class="fas fa-copy"></i> Salin Link Izin
                </button>
            </div>
        @endif
    </div>
</div>

<!-- Stat Cards -->
<div class="stat-cards mb-6">
    <div class="stat-card">
        <div class="stat-icon green"><i class="fas fa-check-circle"></i></div>
        <div class="stat-info">
            <h3>{{ $hadirCount }}</h3>
            <p>Hadir</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon blue"><i class="fas fa-info-circle"></i></div>
        <div class="stat-info">
            <h3>{{ $izinCount }}</h3>
            <p>Izin</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon orange"><i class="fas fa-medkit"></i></div>
        <div class="stat-info">
            <h3>{{ $sakitCount }}</h3>
            <p>Sakit</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon red"><i class="fas fa-times-circle"></i></div>
        <div class="stat-info">
            <h3>{{ $alphaCount }}</h3>
            <p>Alpha</p>
        </div>
    </div>
</div>

<!-- Documentation Photos -->
<div class="card mb-6">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-camera-retro"></i> Foto Dokumentasi Rapat</h3>
        <button class="btn btn-sm btn-secondary" onclick="document.getElementById('photoInput').click()">
            <i class="fas fa-upload"></i> Tambah Foto
        </button>
        <form id="photoForm" action="{{ route('meetings.save_photo', $meeting->id) }}" method="POST" enctype="multipart/form-data" style="display: none;">
            @csrf
            <input type="file" id="photoInput" name="foto" accept="image/*" onchange="this.form.submit()">
        </form>
    </div>

    @php
        $photos = $meeting->foto_dokumentasi ? json_decode($meeting->foto_dokumentasi, true) : [];
        if (!is_array($photos)) {
            $photos = $meeting->foto_dokumentasi ? [$meeting->foto_dokumentasi] : [];
        }
    @endphp

    @if(empty($photos))
        <div class="empty-state" style="padding: var(--space-4);">
            <p style="color: var(--text-muted); margin: 0;">Belum ada foto dokumentasi yang diunggah.</p>
        </div>
    @else
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: var(--space-4); margin-top: var(--space-2);">
            @foreach($photos as $photo)
                @if(file_exists(public_path('uploads/dokumentasi/' . $photo)))
                    <div style="border-radius: var(--radius-md); overflow: hidden; border: 1px solid var(--dark-border); aspect-ratio: 4/3; background: #000;">
                        <a href="{{ asset('uploads/dokumentasi/' . $photo) }}" target="_blank">
                            <img src="{{ asset('uploads/dokumentasi/' . $photo) }}" alt="Dokumentasi" style="width: 100%; height: 100%; object-fit: cover;">
                        </a>
                    </div>
                @endif
            @endforeach
        </div>
    @endif
</div>

<!-- Attendance Roster -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-users"></i> Rekapitulasi Presensi Anggota</h3>
        <span class="badge badge-primary">{{ $attendances->count() }} orang</span>
    </div>
    
    <div class="table-wrapper">
        <table class="table">
            <thead>
                <tr>
                    <th>Nama Anggota</th>
                    <th>Departemen</th>
                    <th>Status</th>
                    <th>Waktu & Metode</th>
                    <th>Keterangan / Bukti</th>
                    <th>Ubah Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($attendances as $a)
                <tr>
                    <td>
                        <div style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                            <strong>{{ $a->member ? $a->member->nama_lengkap : 'Unknown' }}</strong>
                            @if($a->member && $a->member->sp_level == 1)
                                <span class="badge badge-sp-1" style="font-size: 10px; padding: 2px 6px;"><i class="fas fa-exclamation-triangle"></i> SP 1</span>
                            @elseif($a->member && $a->member->sp_level == 2)
                                <span class="badge badge-sp-2" style="font-size: 10px; padding: 2px 6px;"><i class="fas fa-exclamation-circle"></i> SP 2</span>
                            @elseif($a->member && $a->member->sp_level >= 3)
                                <span class="badge badge-sp-3" style="font-size: 10px; padding: 2px 6px;"><i class="fas fa-user-times"></i> SP 3</span>
                            @endif
                        </div>
                        <div style="font-size: var(--font-xs); color: var(--text-muted);">
                            {{ $a->member ? $a->member->jabatan : '' }}
                        </div>
                    </td>
                    <td><span class="badge badge-dept">{{ $a->member ? $a->member->departemen : '-' }}</span></td>
                    <td>
                        @if($a->status === 'hadir')
                            <span class="badge badge-hadir"><i class="fas fa-check-circle"></i> Hadir</span>
                        @elseif($a->status === 'izin')
                            <span class="badge badge-izin"><i class="fas fa-info-circle"></i> Izin</span>
                        @elseif($a->status === 'sakit')
                            <span class="badge badge-sakit"><i class="fas fa-medkit"></i> Sakit</span>
                        @else
                            <span class="badge badge-alpha"><i class="fas fa-times-circle"></i> Alpha</span>
                        @endif
                    </td>
                    <td>
                        @if($a->scanned_at)
                            <div style="font-weight: 600; color: var(--text-primary); font-size: 0.88rem;">
                                <i class="fas fa-clock" style="color: var(--blue-primary); font-size: 11px;"></i>
                                {{ $a->scanned_at->format('H:i') }} WITA
                            </div>
                            <div style="margin-top: 3px;">
                                <span class="badge badge-secondary" style="font-size: 10px; font-weight: 500;">
                                    {{ $a->scan_method === 'qr_scan' ? '📷 Scanner QR' : ($a->scan_method === 'online_link' ? '🌐 Online' : '✍️ Manual') }}
                                </span>
                            </div>
                        @else
                            <span style="color: var(--text-muted); font-size: 0.85rem;">-</span>
                        @endif
                    </td>
                    <td>
                        @if($a->keterangan)
                            <div style="font-size: var(--font-sm); font-weight: 500;">{{ $a->keterangan }}</div>
                        @endif
                        @if($a->bukti_foto && file_exists(public_path('uploads/bukti/' . $a->bukti_foto)))
                            <a href="{{ asset('uploads/bukti/' . $a->bukti_foto) }}" target="_blank" class="badge badge-info mt-1" style="display: inline-block;">
                                <i class="fas fa-image"></i> Lihat Bukti
                            </a>
                        @endif
                    </td>
                    <td>
                        <form action="{{ route('meetings.update_attendance', [$meeting->id, $a->id]) }}" method="POST" style="display: flex; gap: 4px; margin: 0;">
                            @csrf
                            <select name="status" class="form-control form-control-sm" style="padding: 5px 10px; font-size: 12px; height: auto; border-radius: var(--radius-md);" onchange="this.form.submit()">
                                <option value="hadir" {{ $a->status === 'hadir' ? 'selected' : '' }}>Hadir</option>
                                <option value="izin" {{ $a->status === 'izin' ? 'selected' : '' }}>Izin</option>
                                <option value="sakit" {{ $a->status === 'sakit' ? 'selected' : '' }}>Sakit</option>
                                <option value="alpha" {{ $a->status === 'alpha' ? 'selected' : '' }}>Alpha</option>
                            </select>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
