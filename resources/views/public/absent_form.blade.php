@extends('layouts.public')

@section('title', 'Form Izin / Sakit — ' . $meeting->jenis_label)
@section('page_heading', config('himati.app_name'))
@section('page_subheading', 'Form Keterangan Ketidakhadiran Rapat')
@section('container_width', '650px')

@section('content')
<!-- Meeting Info -->
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
                @if($meeting->creator)
                    <span class="meta-separator">•</span>
                    <span class="meta-item">
                        <i class="far fa-user"></i>
                        <span>Oleh: <strong>{{ $meeting->creator->nama_lengkap }}</strong></span>
                    </span>
                @endif
            </div>

            @if ($meeting->fokus_bahasan)
                <div class="meeting-agenda-note">
                    <i class="fas fa-align-left agenda-icon"></i>
                    <div class="agenda-body">
                        <span class="agenda-label">Agenda:</span>
                        <span class="agenda-text">{{ $meeting->fokus_bahasan }}</span>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

@if(in_array($meeting->status, ['completed', 'cancelled'], true))
    <div class="card text-center" style="padding: var(--space-8);">
        <i class="fas fa-check-circle" style="font-size: 2.5rem; color: var(--text-muted); margin-bottom: var(--space-4);"></i>
        <h3>Rapat Telah {{ $meeting->status === 'completed' ? 'Selesai' : 'Dibatalkan' }}</h3>
        <p style="color: var(--text-muted);">Sesi absensi untuk rapat ini sudah ditutup. Pengajuan surat keterangan atau izin sudah tidak dapat dilakukan.</p>
    </div>
@else
<div class="card mb-6">
    <h3 class="card-title mb-4"><i class="fas fa-file-medical"></i> Kirim Surat Keterangan / Izin</h3>
    
    <form action="{{ route('public.absent.submit', $meeting->absent_link) }}" method="POST" enctype="multipart/form-data">
        @csrf

        <div class="form-group">
            <label class="form-label" for="user_id">Nama Pengurus <span class="required">*</span></label>
            <select name="user_id" id="user_id" class="form-control" required>
                <option value="">— Cari & Pilih Nama Anda —</option>
                @foreach($participants as $p)
                    <option value="{{ $p->user_id }}" {{ old('user_id') == $p->user_id ? 'selected' : '' }}>
                        {{ $p->member ? $p->member->nama_lengkap : 'Unknown' }} ({{ $p->member ? $p->member->departemen : '' }})
                        @if($p->member && $p->member->sp_level > 0) [{{ $p->member->sp_label }}] @endif
                        @if($p->status !== 'alpha') [Status: {{ strtoupper($p->status) }}] @endif
                    </option>
                @endforeach
            </select>
        </div>

        <div class="form-group">
            <label class="form-label">Alasan Ketidakhadiran <span class="required">*</span></label>
            <div style="display: flex; gap: var(--space-4);">
                <label class="form-check">
                    <input type="radio" name="status" value="izin" checked>
                    <span class="form-check-label"><i class="fas fa-info-circle text-info"></i> Izin (Ada Keperluan)</span>
                </label>
                <label class="form-check">
                    <input type="radio" name="status" value="sakit">
                    <span class="form-check-label"><i class="fas fa-medkit text-warning"></i> Sakit</span>
                </label>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label" for="keterangan">Keterangan / Alasan Lengkap <span class="required">*</span></label>
            <textarea name="keterangan" id="keterangan" class="form-control" placeholder="Tuliskan alasan berhalangan hadir secara jelas..." rows="3" required>{{ old('keterangan') }}</textarea>
        </div>

        <div class="form-group">
            <label class="form-label" for="bukti_foto">Foto Bukti / Surat Keterangan (Opsional)</label>
            <input type="file" name="bukti_foto" id="bukti_foto" class="form-control" accept="image/*">
            <small style="color: var(--text-muted); display: block; margin-top: 4px;">
                Foto surat sakit dari dokter, surat izin, atau bukti pendukung lainnya (maksimal 5MB).
            </small>
        </div>

        <button type="submit" class="btn btn-primary btn-block btn-lg" style="margin-top: var(--space-6);">
            <i class="fas fa-paper-plane"></i> Kirim Keterangan
        </button>
    </form>
</div>
@endif
@endsection
