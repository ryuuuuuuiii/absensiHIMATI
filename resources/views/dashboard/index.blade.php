@extends('layouts.app')

@section('title', 'Dashboard')

@section('page_icon')
    <i class="fas fa-th-large"></i>
@endsection

@section('content')
<!-- Stat Cards -->
<div class="stat-cards">
    <div class="stat-card">
        <div class="stat-icon orange"><i class="fas fa-users"></i></div>
        <div class="stat-info">
            <h3>{{ $totalMembers }}</h3>
            <p>Total Pengurus</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i class="fas fa-calendar-check"></i></div>
        <div class="stat-info">
            <h3>{{ $totalRapatBulanIni }}</h3>
            <p>Rapat Bulan Ini</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon blue"><i class="fas fa-broadcast-tower"></i></div>
        <div class="stat-info">
            <h3>{{ $activeMeetingsCount }}</h3>
            <p>Rapat Aktif (Scanning)</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon red"><i class="fas fa-chart-line"></i></div>
        <div class="stat-info">
            <h3>{{ $avgAttendance }}%</h3>
            <p>Rata-rata Kehadiran</p>
        </div>
    </div>
</div>

<!-- Active Scanning Meetings Banner if any -->
@if($activeMeetings->isNotEmpty())
    <div class="card mb-6" style="border-left: 4px solid var(--accent); background: rgba(59, 130, 246, 0.05);">
        <div class="card-header" style="border: none; padding-bottom: 0;">
            <h3 style="color: var(--accent); margin: 0; font-size: var(--font-base);">
                <i class="fas fa-qrcode fa-spin"></i> Rapat Sedang Berlangsung (Live Scanning)
            </h3>
        </div>
        <div class="table-wrapper mt-4">
            <table class="table">
                <thead>
                    <tr>
                        <th>Rapat / Agenda</th>
                        <th>Pembuat</th>
                        <th>Hadir</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($activeMeetings as $m)
                    <tr>
                        <td>
                            <strong>{{ $m->jenis_label }}</strong>
                            <div style="font-size: var(--font-xs); color: var(--text-muted);">
                                {{ $m->fokus_bahasan ?? $m->nama_event }} • {{ $m->tanggal->format('d/m/Y') }}
                            </div>
                        </td>
                        <td>{{ $m->creator ? $m->creator->nama_lengkap : 'Admin' }}</td>
                        <td><span class="badge badge-success">{{ $m->jumlah_hadir }}/{{ $m->total_peserta }}</span></td>
                        <td>
                            <a href="{{ route('meetings.scan', $m->id) }}" class="btn btn-sm btn-primary">
                                <i class="fas fa-camera"></i> Buka Scanner
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif

<!-- Public Link Card -->
<div class="card mb-6" style="border-left: 4px solid var(--primary);">
    <div class="d-flex align-center justify-between flex-wrap gap-4">
        <div>
            <h3 style="color: var(--primary); margin-bottom: var(--space-1);">
                <i class="fas fa-link"></i> Link Publik Buat Rapat
            </h3>
            <p style="font-size: var(--font-sm); color: var(--text-muted);">
                Bagikan link ini ke pengurus himpunan agar bisa membuat agenda rapat tanpa perlu login
            </p>
        </div>
        <div style="display: flex; gap: var(--space-3);">
            <button class="btn btn-primary" onclick="copyToClipboard('{{ route('public.meeting.create') }}', this)">
                <i class="fas fa-copy"></i> Salin Link
            </button>
            <a href="{{ route('public.meeting.create') }}" class="btn btn-secondary" target="_blank">
                <i class="fas fa-external-link-alt"></i> Buka
            </a>
        </div>
    </div>
    <div class="share-link-box mt-4">
        <input type="text" value="{{ route('public.meeting.create') }}" readonly id="publicCreateInput">
        <button onclick="copyToClipboard('{{ route('public.meeting.create') }}', this)"><i class="fas fa-copy"></i> Salin</button>
    </div>
</div>

<!-- Quick Actions -->
<div class="quick-actions">
    <a href="{{ route('members.index') }}" class="quick-action-card">
        <i class="fas fa-users-cog"></i>
        <span>Kelola Anggota</span>
    </a>
    <a href="{{ route('meetings.create') }}" class="quick-action-card">
        <i class="fas fa-calendar-plus"></i>
        <span>Buat Rapat Admin</span>
    </a>
    <a href="{{ route('meetings.index') }}" class="quick-action-card">
        <i class="fas fa-clipboard-list"></i>
        <span>Kelola Rapat</span>
    </a>
    <a href="{{ route('events.index') }}" class="quick-action-card">
        <i class="fas fa-calendar-alt"></i>
        <span>Kelola Event</span>
    </a>
    <a href="{{ route('reports.index') }}" class="quick-action-card">
        <i class="fas fa-chart-bar"></i>
        <span>Laporan</span>
    </a>
    <a href="{{ route('members.generate_qr') }}" class="quick-action-card">
        <i class="fas fa-qrcode"></i>
        <span>Generate QR</span>
    </a>
</div>

<!-- Rapat Terakhir -->
<div class="card mt-6">
    <div class="card-header">
        <h2 class="card-title"><i class="fas fa-clock"></i> Rapat Terbaru</h2>
        <a href="{{ route('meetings.index') }}" class="btn btn-ghost btn-sm">Lihat Semua <i class="fas fa-arrow-right"></i></a>
    </div>
    
    @if ($recentMeetings->isEmpty())
        <div class="empty-state">
            <i class="fas fa-calendar-times"></i>
            <h3>Belum Ada Rapat</h3>
            <p>Pengurus bisa membuat rapat melalui link publik atau tombol Buat Rapat di atas.</p>
        </div>
    @else
        <div class="table-wrapper">
            <table class="table">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Jenis Rapat</th>
                        <th>Pembuat</th>
                        <th>Kehadiran</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($recentMeetings as $rapat)
                    <tr>
                        <td>
                            <div style="font-weight: 600;">{{ $rapat->tanggal->format('d M Y') }}</div>
                            <small style="color: var(--text-muted);">{{ substr($rapat->waktu_mulai, 0, 5) }} WITA</small>
                        </td>
                        <td>
                            <span class="badge badge-primary">{{ $rapat->jenis_label }}</span>
                            @if($rapat->fokus_bahasan)
                                <div style="font-size: var(--font-xs); color: var(--text-secondary); margin-top: 2px;">
                                    {{ Str::limit($rapat->fokus_bahasan, 40) }}
                                </div>
                            @endif
                        </td>
                        <td>{{ $rapat->creator ? $rapat->creator->nama_lengkap : 'Admin' }}</td>
                        <td>
                            <strong style="color: var(--success);">{{ $rapat->jumlah_hadir }}</strong>/{{ $rapat->total_peserta }}
                        </td>
                        <td>
                            @if($rapat->status === 'scanning')
                                <span class="badge badge-primary"><i class="fas fa-qrcode"></i> Scanning</span>
                            @elseif($rapat->status === 'completed')
                                <span class="badge badge-success"><i class="fas fa-check"></i> Selesai</span>
                            @elseif($rapat->status === 'draft')
                                <span class="badge badge-warning"><i class="fas fa-edit"></i> Draft</span>
                            @else
                                <span class="badge badge-danger"><i class="fas fa-ban"></i> Batal</span>
                            @endif
                        </td>
                        <td>
                            <div style="display: flex; gap: var(--space-2);">
                                <a href="{{ route('meetings.show', $rapat->id) }}" class="btn btn-ghost btn-sm" title="Lihat Detail">
                                    <i class="fas fa-eye"></i>
                                </a>
                                @if($rapat->status === 'scanning')
                                <a href="{{ route('meetings.scan', $rapat->id) }}" class="btn btn-sm btn-primary" title="Buka Scanner">
                                    <i class="fas fa-qrcode"></i>
                                </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
