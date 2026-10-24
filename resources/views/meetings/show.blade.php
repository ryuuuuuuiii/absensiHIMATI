@extends('layouts.app')

@section('title', 'Detail Rapat')

@section('page_icon')
    <i class="fas fa-clipboard-list"></i>
@endsection

@section('content')
<!-- Meeting Info Banner -->
<div class="card mb-6" style="border-left: 4px solid var(--primary);">
    <div class="d-flex align-center justify-between flex-wrap gap-4 mb-4">
        <div>
            <h2 style="color: var(--primary); font-size: var(--font-xl);">
                {{ $meeting->jenis_label }}
            </h2>
            <p style="font-size: var(--font-sm); color: var(--text-muted);">
                Dibuat oleh: <strong>{{ $meeting->creator ? $meeting->creator->nama_lengkap : 'Admin' }}</strong>
            </p>
        </div>
        <div style="display: flex; gap: var(--space-3); align-items: center;">
            @if($meeting->status === 'scanning')
                <span class="badge badge-primary"><i class="fas fa-qrcode"></i> Scanning</span>
                <a href="{{ route('meetings.scan', $meeting->id) }}" class="btn btn-sm btn-primary">
                    <i class="fas fa-camera"></i> Buka Scanner
                </a>
            @elseif($meeting->status === 'completed')
                <span class="badge badge-success"><i class="fas fa-check"></i> Selesai</span>
            @elseif($meeting->status === 'draft')
                <span class="badge badge-warning"><i class="fas fa-edit"></i> Draft</span>
            @else
                <span class="badge badge-danger"><i class="fas fa-ban"></i> Batal</span>
            @endif

            <form action="{{ route('meetings.sync_sheets', $meeting->id) }}" method="POST" style="margin: 0;">
                @csrf
                <button type="submit" class="btn btn-sm btn-secondary" title="Kirim data kehadiran ke Google Sheets">
                    <i class="fab fa-google"></i> Sync Sheets
                </button>
            </form>
        </div>
    </div>
    
    <div class="meeting-info-grid">
        <div class="meeting-info-item">
            <label><i class="fas fa-calendar"></i> Tanggal</label>
            <p>{{ $meeting->tanggal->format('d M Y') }}</p>
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
    
    @if ($meeting->fokus_bahasan)
    <div class="meeting-info-item mt-4">
        <label><i class="fas fa-bookmark"></i> Fokus Bahasan / Agenda</label>
        <p>{{ $meeting->fokus_bahasan }}</p>
    </div>
    @endif
    
    @if ($meeting->nama_event)
    <div class="meeting-info-item mt-4">
        <label><i class="fas fa-calendar-star"></i> Nama Event / Divisi</label>
        <p>{{ $meeting->nama_event }} {{ $meeting->divisi_rapat ? '• Divisi ' . $meeting->divisi_rapat : '' }}</p>
    </div>
    @endif

    <!-- Share Links -->
    <div style="margin-top: var(--space-4); padding-top: var(--space-4); border-top: 1px solid var(--dark-border);">
        <label class="form-label" style="font-size: var(--font-xs); color: var(--text-muted);">
            <i class="fas fa-share-alt"></i> Link Berbagi
        </label>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-3);" class="flex-column-mobile">
            <div class="share-link-box">
                <input type="text" value="{{ route('public.absent', $meeting->absent_link) }}" readonly>
                <button onclick="copyToClipboard('{{ route('public.absent', $meeting->absent_link) }}', this)" title="Salin Link Izin/Sakit">
                    <i class="fas fa-copy"></i> Izin
                </button>
            </div>
            <div class="share-link-box">
                <input type="text" value="{{ route('public.online', $meeting->unique_link) }}" readonly>
                <button onclick="copyToClipboard('{{ route('public.online', $meeting->unique_link) }}', this)" title="Salin Link Online">
                    <i class="fas fa-copy"></i> Online
                </button>
            </div>
        </div>
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
                        <strong>{{ $a->member ? $a->member->nama_lengkap : 'Unknown' }}</strong>
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
                            <div style="font-weight: 600;">{{ $a->scanned_at->format('H:i') }} WITA</div>
                            <small style="color: var(--text-muted);">
                                {{ $a->scan_method === 'qr_scan' ? 'Scanner QR' : ($a->scan_method === 'online_link' ? 'Online' : 'Manual') }}
                            </small>
                        @else
                            <span style="color: var(--text-muted);">-</span>
                        @endif
                    </td>
                    <td>
                        @if($a->keterangan)
                            <div style="font-size: var(--font-sm);">{{ $a->keterangan }}</div>
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
                            <select name="status" class="form-control form-control-sm" style="padding: 4px 8px; font-size: 12px; height: auto;" onchange="this.form.submit()">
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
