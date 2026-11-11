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
        <p style="color: var(--text-muted); margin-bottom: var(--space-5);">Sesi absensi untuk rapat ini sudah ditutup. Pengajuan surat keterangan atau izin sudah tidak dapat dilakukan.</p>
        <div style="display: flex; gap: 10px; justify-content: center; flex-wrap: wrap;">
            <a href="{{ route('home') }}" class="btn btn-primary">
                <i class="fas fa-home"></i> Kembali ke Halaman Utama
            </a>
            <a href="{{ route('public.hasil', $meeting->unique_link) }}" class="btn btn-secondary">
                <i class="fas fa-chart-pie"></i> Lihat Rekap Kehadiran
            </a>
        </div>
    </div>
@else
<div class="card mb-6">
    <h3 class="card-title mb-4"><i class="fas fa-file-medical"></i> Kirim Surat Keterangan / Izin</h3>

    <!-- Verified Member Account Card (Identitas Terverifikasi) -->
    <div class="online-member-card">
        <div class="online-member-avatar">
            @if($member->profile_photo_url)
                <img src="{{ $member->profile_photo_url }}" alt="{{ $member->nama_lengkap }}">
            @else
                <span>{{ $member->initials }}</span>
            @endif
        </div>
        <div class="online-member-details">
            <div class="online-member-name-row">
                <span class="online-member-name">{{ $member->nama_lengkap }}</span>
                <span class="badge badge-success" style="font-size: 0.72rem; padding: 2px 7px;">
                    <i class="fas fa-check-circle"></i> Terverifikasi
                </span>
                @if($member->sp_level > 0)
                    <span class="badge badge-warning" style="font-size: 0.72rem; padding: 2px 7px;">
                        {{ $member->sp_label }}
                    </span>
                @endif
            </div>
            <div class="online-member-badges">
                <span class="online-member-badge">
                    <i class="fas fa-id-card"></i> {{ $member->nim ?? '-' }}
                </span>
                <span class="online-member-badge">
                    <i class="fas fa-building"></i> Dept. {{ $member->departemen }}
                </span>
                <span class="online-member-badge">
                    <i class="fas fa-briefcase"></i> {{ $member->jabatan }}
                </span>
                @if($member->isBphInti())
                    <span class="online-member-badge" style="background: #92EEFF; color: #061A2E;">
                        <i class="fas fa-crown"></i> Pengurus Inti
                    </span>
                @elseif($member->is_bph)
                    <span class="online-member-badge" style="background: #92EEFF; color: #061A2E;">
                        <i class="fas fa-shield-alt"></i> BPH
                    </span>
                @endif
            </div>
            <div class="online-member-switch">
                <span>Pengajuan izin/sakit otomatis dicatat untuk akun ini. Bukan Anda? </span>
                <a href="#" onclick="event.preventDefault(); document.getElementById('switchAccountForm').submit();">
                    Ganti Akun
                </a>
            </div>
        </div>
    </div>

    <form id="switchAccountForm" action="{{ auth('member')->check() ? route('portal.logout') : route('logout') }}" method="POST" style="display: none;">
        @csrf
    </form>

    @if($existingAttendance)
        @if($existingAttendance->status === 'hadir')
            <div class="alert alert-info mb-4" style="border-radius: 12px;">
                <i class="fas fa-check-circle"></i>
                <div>
                    <strong>Anda sudah tercatat HADIR pada rapat ini.</strong>
                    <div style="font-size: 0.85rem; margin-top: 2px;">
                        Presensi Anda telah dicatat pada pukul {{ $existingAttendance->scanned_at ? $existingAttendance->scanned_at->locale('id')->translatedFormat('H:i') . ' WITA' : '-' }}.
                    </div>
                </div>
            </div>
        @else
            <div class="alert alert-success mb-4" style="border-radius: 12px;">
                <i class="fas fa-info-circle"></i>
                <div>
                    <strong>Anda telah mengajukan status {{ strtoupper($existingAttendance->status) }}.</strong>
                    <div style="font-size: 0.85rem; margin-top: 2px;">
                        Alasan: "{{ $existingAttendance->keterangan }}"
                        @if($existingAttendance->bukti_foto)
                            &bull; <a href="{{ asset('uploads/bukti/' . $existingAttendance->bukti_foto) }}" target="_blank" style="color: inherit; text-decoration: underline; font-weight: 700;">Lihat Bukti Foto</a>
                        @endif
                    </div>
                    <div style="font-size: 0.78rem; opacity: 0.9; margin-top: 2px;">
                        Anda dapat memperbarui alasan atau file bukti di bawah jika diperlukan.
                    </div>
                </div>
            </div>
        @endif
    @endif

    @if($eligibilityError)
        <div class="alert alert-danger mb-4" style="border-radius: 12px;">
            <i class="fas fa-exclamation-triangle"></i>
            <div>
                <strong>Pemberitahuan Partisipasi:</strong>
                <div style="font-size: 0.88rem; margin-top: 2px;">{{ $eligibilityError }}</div>
            </div>
        </div>
    @endif

    <form action="{{ route('public.absent.submit', $meeting->absent_link) }}" method="POST" enctype="multipart/form-data">
        @csrf

        <div class="form-group">
            <label class="form-label">Alasan Ketidakhadiran <span class="required">*</span></label>
            <div style="display: flex; gap: var(--space-4);">
                <label class="form-check">
                    <input type="radio" name="status" value="izin" {{ (old('status', $existingAttendance?->status) === 'sakit') ? '' : 'checked' }}>
                    <span class="form-check-label"><i class="fas fa-info-circle text-info"></i> Izin (Ada Keperluan)</span>
                </label>
                <label class="form-check">
                    <input type="radio" name="status" value="sakit" {{ (old('status', $existingAttendance?->status) === 'sakit') ? 'checked' : '' }}>
                    <span class="form-check-label"><i class="fas fa-medkit text-warning"></i> Sakit</span>
                </label>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label" for="keterangan">Keterangan / Alasan Lengkap <span class="required">*</span></label>
            <textarea name="keterangan" id="keterangan" class="form-control" placeholder="Tuliskan alasan berhalangan hadir secara jelas..." rows="3" required>{{ old('keterangan', $existingAttendance?->keterangan) }}</textarea>
        </div>

        <div class="form-group">
            <label class="form-label" for="bukti_foto">Foto Bukti / Surat Keterangan (Opsional)</label>
            <input type="file" name="bukti_foto" id="bukti_foto" class="form-control" accept="image/*">
            <small style="color: var(--text-muted); display: block; margin-top: 4px;">
                Foto surat sakit dari dokter, surat izin, atau bukti pendukung lainnya (maksimal 5MB).
                @if($existingAttendance?->bukti_foto)
                    <br><strong style="color: var(--success);"><i class="fas fa-check"></i> Sudah ada bukti tersimpan:</strong> <a href="{{ asset('uploads/bukti/' . $existingAttendance->bukti_foto) }}" target="_blank" style="text-decoration: underline;">Lihat Bukti Sebelumnya</a>
                @endif
            </small>
        </div>

        <button type="submit" class="btn btn-primary btn-block btn-lg" style="margin-top: var(--space-6);" {{ $eligibilityError ? 'disabled' : '' }}>
            <i class="fas fa-paper-plane"></i> {{ $existingAttendance ? 'Perbarui Keterangan Izin / Sakit' : 'Kirim Keterangan Izin / Sakit' }}
        </button>
    </form>
</div>
@endif
@endsection
