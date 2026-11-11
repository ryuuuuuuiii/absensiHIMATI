@extends('layouts.public')

@section('title', 'Presensi Online — ' . $meeting->jenis_label)
@section('page_heading', config('himati.app_name'))
@section('page_subheading', 'Form Kehadiran Rapat Online')
@section('container_width', '650px')

@section('content')
<!-- Meeting Info -->
<!-- Meeting Info Banner -->
<div class="card mb-6 meeting-header-banner">
    <div class="meeting-header-content">
        <div class="meeting-header-details">
            <div class="meeting-type-badge-row">
                <span class="badge badge-info"><i class="fas fa-video"></i> Online</span>

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

@if($isExpired)
    <div class="card text-center" style="padding: var(--space-8);">
        @if($meeting->status === 'completed')
            <i class="fas fa-check-circle" style="font-size: 2.5rem; color: var(--blue-primary, #3b82f6); margin-bottom: var(--space-4);"></i>
            <h3>Rapat Telah Selesai (Absensi Ditutup)</h3>
            <p style="color: var(--text-muted); margin-bottom: var(--space-5);">Batas Waktu Absensi Telah Berakhir. Sesi absensi untuk rapat ini telah diselesaikan dan ditutup oleh pembuat rapat.</p>
            <div style="display: flex; gap: 10px; justify-content: center; flex-wrap: wrap;">
                <a href="{{ route('home') }}" class="btn btn-primary">
                    <i class="fas fa-home"></i> Kembali ke Halaman Utama
                </a>
                <a href="{{ route('public.hasil', $meeting->unique_link) }}" class="btn btn-secondary">
                    <i class="fas fa-chart-pie"></i> Lihat Rekap Kehadiran
                </a>
            </div>
        @elseif($meeting->status === 'cancelled')
            <i class="fas fa-times-circle" style="font-size: 2.5rem; color: var(--danger); margin-bottom: var(--space-4);"></i>
            <h3>Rapat Dibatalkan</h3>
            <p style="color: var(--text-muted); margin-bottom: var(--space-5);">Sesi absensi untuk rapat ini telah dibatalkan.</p>
            <a href="{{ route('home') }}" class="btn btn-primary">
                <i class="fas fa-home"></i> Kembali ke Halaman Utama
            </a>
        @else
            <i class="fas fa-hourglass-end" style="font-size: 2.5rem; color: var(--danger); margin-bottom: var(--space-4);"></i>
            <h3>Batas Waktu Absensi Telah Berakhir</h3>
            <p style="color: var(--text-muted); margin-bottom: var(--space-5);">Link presensi untuk rapat ini sudah ditutup karena telah melewati batas jam berakhir absensi (pukul {{ substr($meeting->getOnlineExpiryTime()->format('H:i'), 0, 5) }} WITA). Silakan hubungi pembuat rapat jika ada kendala.</p>
            <div style="display: flex; gap: 10px; justify-content: center; flex-wrap: wrap;">
                <a href="{{ route('home') }}" class="btn btn-primary">
                    <i class="fas fa-home"></i> Kembali ke Halaman Utama
                </a>
                <a href="{{ route('public.hasil', $meeting->unique_link) }}" class="btn btn-secondary">
                    <i class="fas fa-chart-pie"></i> Lihat Rekap Kehadiran
                </a>
            </div>
        @endif
    </div>
@else
    <div class="card mb-6">
        <h3 class="card-title mb-4"><i class="fas fa-user-check"></i> Form Presensi Kehadiran Online</h3>

        <!-- Expiry Deadline Banner -->
        <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px; margin-bottom: var(--space-5); padding: 10px 14px; background: rgba(59, 130, 246, 0.08); border-radius: var(--radius-md); border-left: 3px solid var(--info);">
            <div style="font-size: 0.88rem; color: var(--text-primary); font-weight: 600;">
                <i class="fas fa-stopwatch text-info" style="margin-right: 5px;"></i> Batas Waktu Presensi:
                <span style="color: var(--info); font-weight: 700;">Sampai pukul {{ substr($meeting->getOnlineExpiryTime()->format('H:i'), 0, 5) }} WITA</span>
            </div>
            <div style="font-size: 0.8rem; color: var(--text-muted);">
                Ditentukan oleh pembuat rapat
            </div>
        </div>

        <!-- Verified Member Account Card -->
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
                    <span>Presensi otomatis dicatat untuk akun ini. Bukan Anda? </span>
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
            <div class="alert alert-success mb-4" style="border-radius: 12px;">
                <i class="fas fa-check-circle"></i>
                <div>
                    <strong>Anda sudah tercatat {{ strtoupper($existingAttendance->status) }} pada rapat ini.</strong>
                    <div style="font-size: 0.85rem; margin-top: 2px;">
                        Waktu presensi: {{ $existingAttendance->scanned_at ? $existingAttendance->scanned_at->locale('id')->translatedFormat('H:i') . ' WITA' : '-' }}
                        @if($existingAttendance->keterangan)
                            • Keterangan: "{{ $existingAttendance->keterangan }}"
                        @endif
                    </div>
                    <div style="font-size: 0.78rem; opacity: 0.9; margin-top: 2px;">
                        Anda dapat memperbarui status atau foto bukti di bawah jika diperlukan.
                    </div>
                </div>
            </div>
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

        @if($member->sp_level >= 3)
            <div class="alert alert-danger mb-4" style="border-radius: 12px;">
                <i class="fas fa-user-times"></i>
                <div>
                    <strong>Status Akun: {{ $member->sp_label }}</strong>
                    <div style="font-size: 0.85rem; margin-top: 2px;">
                        Akun pengurus Anda telah dinonaktifkan (Pemberhentian) dan tidak dapat melakukan presensi rapat.
                    </div>
                </div>
            </div>
        @elseif($member->sp_level > 0)
            <div class="alert alert-warning mb-4" style="border-radius: 12px;">
                <i class="fas fa-exclamation-triangle"></i>
                <div>
                    <strong>Informasi Status Akun: {{ $member->sp_label }}</strong>
                    <div style="font-size: 0.85rem; margin-top: 2px;">
                        {{ $member->sp_reason ?: 'Harap tingkatkan kedisiplinan kehadiran rapat Anda.' }}
                    </div>
                </div>
            </div>
        @endif

        <form action="{{ route('public.online.submit', $meeting->unique_link) }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="form-group">
                <label class="form-label">Status Kehadiran <span class="required">*</span></label>
                <div style="display: flex; gap: var(--space-4); flex-wrap: wrap;">
                    <label class="form-check">
                        <input type="radio" name="status" value="hadir" id="statusHadir" {{ old('status', $existingAttendance?->status ?? 'hadir') === 'hadir' ? 'checked' : '' }} onchange="updateDocRequirement()">
                        <span class="form-check-label"><i class="fas fa-check-circle text-success"></i> Hadir</span>
                    </label>
                    <label class="form-check">
                        <input type="radio" name="status" value="izin" id="statusIzin" {{ old('status', $existingAttendance?->status) === 'izin' ? 'checked' : '' }} onchange="updateDocRequirement()">
                        <span class="form-check-label"><i class="fas fa-info-circle text-info"></i> Izin</span>
                    </label>
                    <label class="form-check">
                        <input type="radio" name="status" value="sakit" id="statusSakit" {{ old('status', $existingAttendance?->status) === 'sakit' ? 'checked' : '' }} onchange="updateDocRequirement()">
                        <span class="form-check-label"><i class="fas fa-medkit text-warning"></i> Sakit</span>
                    </label>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="keterangan">Keterangan (Opsional)</label>
                <textarea name="keterangan" id="keterangan" class="form-control" placeholder="Contoh: Mengikuti via Google Meet / Zoom" rows="2">{{ old('keterangan', $existingAttendance?->keterangan) }}</textarea>
            </div>

            <div class="form-group">
                <label class="form-label" for="bukti_foto" id="labelBuktiFoto">
                    Foto Bukti Dokumentasi <span class="required" id="reqBuktiFoto" style="color: var(--danger);">* Wajib</span>
                </label>
                @if($existingAttendance?->bukti_foto_url)
                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                        <img src="{{ $existingAttendance->bukti_foto_url }}" alt="Bukti Foto Sebelumnya" style="width: 48px; height: 48px; object-fit: cover; border-radius: 8px; border: 1.5px solid var(--border-color);">
                        <span style="font-size: 0.8rem; color: var(--text-muted);">Foto bukti sebelumnya sudah tersimpan. Unggah foto baru jika ingin mengganti.</span>
                    </div>
                @endif
                <input type="file" name="bukti_foto" id="bukti_foto" class="form-control @error('bukti_foto') is-invalid @enderror" accept="image/*" {{ $existingAttendance?->bukti_foto ? '' : 'required' }}>
                @error('bukti_foto')
                    <div class="invalid-feedback" style="display: block; color: var(--danger); font-size: 0.8rem; margin-top: 4px;">
                        <i class="fas fa-exclamation-circle"></i> {{ $message }}
                    </div>
                @enderror
                <small id="helperBuktiFoto" style="color: var(--text-muted); display: block; margin-top: 4px;">
                    Wajib melampirkan screenshot layar rapat online (Google Meet, Zoom, dll.) atau foto kehadiran Anda (maksimal 5MB).
                </small>
            </div>

            <button type="submit" class="btn btn-primary btn-block btn-lg" id="btnOnlineSubmit" style="margin-top: var(--space-6);" {{ ($eligibilityError || $member->sp_level >= 3) ? 'disabled' : '' }}>
                <i class="fas fa-paper-plane"></i> {{ $existingAttendance ? 'Perbarui Presensi' : 'Kirim Presensi Kehadiran' }}
            </button>
        </form>
    </div>
@endif
@endsection

@push('scripts')
<script>
    const hasExistingProof = {{ ($existingAttendance && filled($existingAttendance->bukti_foto)) ? 'true' : 'false' }};

    function updateDocRequirement() {
        const isHadir = document.getElementById('statusHadir')?.checked ?? true;
        const inputBukti = document.getElementById('bukti_foto');
        const reqBadge = document.getElementById('reqBuktiFoto');
        const helper = document.getElementById('helperBuktiFoto');

        if (!inputBukti) return;

        if (isHadir) {
            if (reqBadge) reqBadge.style.display = 'inline';
            if (!hasExistingProof) {
                inputBukti.required = true;
            }
            if (helper) {
                helper.textContent = 'Wajib melampirkan screenshot layar rapat online (Google Meet, Zoom, dll.) atau foto kehadiran Anda (maksimal 5MB).';
            }
        } else {
            if (reqBadge) reqBadge.style.display = 'none';
            inputBukti.required = false;
            if (helper) {
                helper.textContent = 'Lampirkan surat keterangan izin atau surat sakit jika ada (opsional, maksimal 5MB).';
            }
        }
    }

    document.addEventListener('DOMContentLoaded', updateDocRequirement);
</script>
@endpush
