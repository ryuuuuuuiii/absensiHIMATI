@extends('layouts.public')

@section('title', 'Rapat Dibuat — ' . $meeting->jenis_label)
@section('page_heading', config('himati.app_name'))
@section('page_subheading', 'Rapat Berhasil Dibuat — Simpan dan Bagikan Link')
@section('container_width', '680px')

@section('content')
<!-- Success Notification Card -->
<div class="card mb-6 text-center" style="border-top: 4px solid var(--success); padding: var(--space-8) var(--space-6);">
    <div style="width: 64px; height: 64px; border-radius: 50%; background: rgba(39, 174, 96, 0.15); display: flex; align-items: center; justify-content: center; margin: 0 auto var(--space-4);">
        <i class="fas fa-check" style="font-size: 2rem; color: var(--success);"></i>
    </div>
    
    <h2 style="font-size: var(--font-2xl); color: var(--text-primary); margin-bottom: var(--space-2);">
        Rapat Berhasil Dibuat!
    </h2>
    <p style="color: var(--text-muted); font-size: var(--font-sm); max-width: 450px; margin: 0 auto var(--space-6);">
        Simpan halaman ini atau bookmark link di bawah untuk membuka scanner absensi kapan saja.
    </p>

    <!-- Primary Action: Open Scanner -->
    @if ($meeting->mode_rapat === 'offline')
        <a href="{{ $scanUrl }}" class="btn btn-primary btn-lg" style="display: inline-flex; align-items: center; gap: 10px; padding: 14px 28px; font-size: var(--font-base);">
            <i class="fas fa-qrcode"></i> Buka Scanner Kamera Sekarang
        </a>
    @else
        <a href="{{ $onlineUrl }}" class="btn btn-primary btn-lg" style="display: inline-flex; align-items: center; gap: 10px; padding: 14px 28px; font-size: var(--font-base);">
            <i class="fas fa-link"></i> Buka Halaman Absensi Online
        </a>
    @endif
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
            <p>{{ $meeting->tanggal->format('d M Y') }}</p>
        </div>
        <div class="meeting-info-item">
            <label><i class="fas fa-clock"></i> Waktu Mulai</label>
            <p>{{ substr($meeting->waktu_mulai, 0, 5) }} WITA</p>
        </div>
        <div class="meeting-info-item">
            <label><i class="fas fa-user"></i> Penanggung Jawab</label>
            <p>{{ $meeting->creator ? $meeting->creator->nama_lengkap : 'Admin' }}</p>
        </div>
        <div class="meeting-info-item">
            <label><i class="fas fa-wifi"></i> Mode Rapat</label>
            <p>{{ $meeting->mode_rapat === 'offline' ? '🏢 Tatap Muka (Offline)' : '🌐 Online Link' }}</p>
        </div>
    </div>

    @if ($meeting->fokus_bahasan)
    <div class="meeting-info-item mt-4">
        <label><i class="fas fa-bookmark"></i> Fokus Bahasan / Agenda</label>
        <p>{{ $meeting->fokus_bahasan }}</p>
    </div>
    @endif
</div>

<!-- Share Links Card -->
<div class="card mb-6">
    <h3 class="card-title mb-4"><i class="fas fa-share-alt"></i> Link Absensi untuk Dibagikan</h3>

    <!-- Scanner Link (for meeting organizer) -->
    <div style="margin-bottom: var(--space-4);">
        <label class="form-label" style="font-size: var(--font-xs); color: var(--text-primary); font-weight: 600;">
            <i class="fas fa-qrcode text-primary"></i> Link Scanner Absensi (Buka di HP Pembuat Rapat)
        </label>
        <div class="share-link-box">
            <input type="text" value="{{ $scanUrl }}" readonly id="scanLinkInput">
            <button onclick="copyToClipboard('{{ $scanUrl }}', this)">
                <i class="fas fa-copy"></i> Salin
            </button>
        </div>
    </div>

    <!-- Absent Link -->
    <div style="margin-bottom: var(--space-4);">
        <label class="form-label" style="font-size: var(--font-xs); color: var(--danger); font-weight: 600;">
            <i class="fas fa-user-slash"></i> Link Izin / Sakit (Bagikan ke Anggota yang Berhalangan Hadir)
        </label>
        <div class="share-link-box">
            <input type="text" value="{{ $absentUrl }}" readonly id="absentLinkInput">
            <button onclick="copyToClipboard('{{ $absentUrl }}', this)">
                <i class="fas fa-copy"></i> Salin
            </button>
        </div>
    </div>

    <!-- Online Attend Link (if online) -->
    @if ($meeting->mode_rapat === 'online')
    <div>
        <label class="form-label" style="font-size: var(--font-xs); color: var(--info); font-weight: 600;">
            <i class="fas fa-globe"></i> Link Absensi Mandiri Online (Bagikan ke Seluruh Peserta Rapat)
        </label>
        <div class="share-link-box">
            <input type="text" value="{{ $onlineUrl }}" readonly id="onlineLinkInput">
            <button onclick="copyToClipboard('{{ $onlineUrl }}', this)">
                <i class="fas fa-copy"></i> Salin
            </button>
        </div>
    </div>
    @endif
</div>

<div class="text-center">
    <a href="{{ route('public.meeting.create') }}" class="btn btn-secondary">
        <i class="fas fa-calendar-plus"></i> Buat Agenda Rapat Lain
    </a>
</div>
@endsection
