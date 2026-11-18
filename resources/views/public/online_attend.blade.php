@extends('layouts.public')

@php
    $isKegiatan = $meeting->isKegiatan();
    $isOfflineKegiatan = $isKegiatan && $meeting->mode_rapat === 'offline';
    $isOnlineKegiatan = $isKegiatan && $meeting->mode_rapat === 'online';
    $proofHelper = $isOfflineKegiatan
        ? 'Ambil foto dokumentasi langsung di lokasi/tempat kegiatan (maksimal 5MB).'
        : ($isOnlineKegiatan
            ? 'Unggah foto/screenshot dokumentasi saat kegiatan berlangsung (maksimal 5MB).'
            : 'Lampirkan foto bukti kehadiran atau dokumentasi rapat (maksimal 5MB).');
@endphp

@section('title', 'Presensi ' . ($isKegiatan ? 'Kegiatan' : 'Online') . ' — ' . $meeting->jenis_label)
@section('page_heading', config('himati.app_name'))
@section('page_subheading', $isKegiatan ? 'Form Kehadiran Kegiatan (' . ($isOfflineKegiatan ? 'Offline' : 'Online') . ')' : 'Form Kehadiran Rapat Online')
@section('container_width', '650px')

@section('content')
<!-- Meeting Info -->
<!-- Meeting Info Banner -->
<div class="card mb-6 meeting-header-banner">
    <div class="meeting-header-content">
        <div class="meeting-header-details">
            <div class="meeting-type-badge-row">
                @if($isKegiatan)
                    <span class="badge badge-warning"><i class="fas fa-camera"></i> Kegiatan</span>
                @endif
                @if($meeting->mode_rapat === 'offline')
                    <span class="badge badge-dept"><i class="fas fa-map-marker-alt"></i> Offline</span>
                @else
                    <span class="badge badge-info"><i class="fas fa-video"></i> Online</span>
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
        <h3 class="card-title mb-4"><i class="fas fa-user-check"></i> {{ $isKegiatan ? 'Form Presensi Kegiatan (Foto Dokumentasi)' : 'Form Presensi Kehadiran Online' }}</h3>

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
            </div>
        </div>

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

        @if($isKegiatan)
            <div class="alert alert-warning mb-4" style="border-radius: 12px;">
                <i class="fas fa-camera"></i>
                <div>
                    @if($isOfflineKegiatan)
                        <strong>Presensi Offline — Foto Dokumentasi di Lokasi Kegiatan</strong>
                        <div style="font-size: 0.88rem; margin-top: 2px;">
                            Ambil foto dokumentasi langsung di lokasi/tempat kegiatan berlangsung.
                        </div>
                    @else
                        <strong>Presensi Online — Foto Dokumentasi Kegiatan</strong>
                        <div style="font-size: 0.88rem; margin-top: 2px;">
                            Ambil foto atau tangkapan layar dokumentasi saat kegiatan online berlangsung.
                        </div>
                    @endif
                    <div style="font-size: 0.82rem; margin-top: 4px;">Tidak bisa hadir? Pilih status <strong>Izin</strong> atau <strong>Sakit</strong> di bawah dan isi keterangannya.</div>
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
                <textarea name="keterangan" id="keterangan" class="form-control" placeholder="{{ $isKegiatan ? 'Contoh: Hadir sebagai peserta / alasan jika izin atau sakit' : 'Contoh: Mengikuti via Google Meet / Zoom' }}" rows="2">{{ old('keterangan', $existingAttendance?->keterangan) }}</textarea>
            </div>

            @if($isKegiatan)
                <!-- CAMERA ONLY SECTION FOR KEGIATAN (STATUS HADIR) -->
                <div id="cameraKegiatanWrapper" class="form-group">
                    <label class="form-label" style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 6px;">
                        <span>
                            <i class="fas fa-camera text-warning"></i> Foto Kehadiran Kamera Langsung 
                            <span class="required" style="color: var(--danger);">* Wajib</span>
                        </span>
                        <span class="badge badge-warning" style="font-size: 0.72rem; padding: 3px 8px; border-radius: 9999px;">
                            <i class="fas fa-lock"></i> Hanya Kamera (Galeri Nonaktif)
                        </span>
                    </label>

                    @if($existingAttendance?->bukti_foto_url)
                        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 12px; padding: 10px; background: rgba(59, 130, 246, 0.08); border-radius: 8px; border: 1px solid rgba(59, 130, 246, 0.2);">
                            <img src="{{ $existingAttendance->bukti_foto_url }}" alt="Bukti Foto Sebelumnya" style="width: 48px; height: 48px; object-fit: cover; border-radius: 8px; border: 1.5px solid var(--border-color);">
                            <div style="font-size: 0.8rem; color: var(--text-muted);">
                                <strong>Foto sebelumnya sudah tersimpan.</strong> Ambil foto baru di bawah jika ingin memperbarui.
                            </div>
                        </div>
                    @endif

                    <!-- Box Viewfinder Kamera -->
                    <div id="cameraBox" class="camera-viewport-box">
                        <!-- Live Video Stream -->
                        <div id="videoContainer" style="position: relative; width: 100%; max-width: 440px; margin: 0 auto; border-radius: 12px; overflow: hidden; background: #000; display: none;">
                            <video id="cameraVideo" playsinline autoplay muted style="width: 100%; height: auto; max-height: 380px; object-fit: cover; display: block; border-radius: 12px;"></video>
                            
                            <!-- Live Badge -->
                            <div style="position: absolute; top: 10px; left: 10px; background: rgba(239, 68, 68, 0.9); color: white; font-size: 0.72rem; font-weight: 700; padding: 3px 8px; border-radius: 6px; display: flex; align-items: center; gap: 5px;">
                                <span class="pulse-cam" style="width: 7px; height: 7px; border-radius: 50%; background: #fff; display: inline-block;"></span> LIVE KAMERA
                            </div>
                        </div>

                        <!-- Placeholder Sebelum Kamera Dibuka -->
                        <div id="cameraPlaceholder" style="padding: 24px 16px;">
                            <div style="width: 64px; height: 64px; border-radius: 50%; background: rgba(56, 189, 248, 0.12); color: #38bdf8; display: inline-flex; align-items: center; justify-content: center; font-size: 1.8rem; margin-bottom: 12px;">
                                <i class="fas fa-camera"></i>
                            </div>
                            <h4 style="font-size: 1rem; margin-bottom: 4px; color: var(--text-primary);">Kamera Dokumentasi Kegiatan</h4>
                            <p style="font-size: 0.82rem; color: var(--text-muted); margin-bottom: 16px; max-width: 380px; margin-left: auto; margin-right: auto;">
                                Ambil foto dokumentasi langsung di lokasi kegiatan. Pemilihan file galeri dinonaktifkan demi keaslian presensi.
                            </p>
                            <div style="display: flex; gap: 8px; justify-content: center; flex-wrap: wrap;">
                                <button type="button" class="btn btn-primary" id="btnStartCamera" style="padding: 10px 20px; font-weight: 600; border-radius: 10px;">
                                    <i class="fas fa-camera"></i> Buka Kamera
                                </button>
                                <button type="button" class="btn btn-secondary btn-sm" id="btnTriggerNativeDirect" style="padding: 10px 16px; border-radius: 10px;">
                                    <i class="fas fa-mobile-alt"></i> Kamera HP
                                </button>
                            </div>
                        </div>

                        <!-- Tombol Kontrol Saat Kamera Aktif -->
                        <div id="cameraControls" style="display: none; margin-top: 14px; gap: 8px; justify-content: center; flex-wrap: wrap;">
                            <button type="button" class="btn btn-success" id="btnCapturePhoto" style="padding: 10px 22px; font-weight: 700; border-radius: 10px;">
                                <i class="fas fa-circle-dot"></i> Ambil Foto (Jepret)
                            </button>
                            <button type="button" class="btn btn-secondary btn-sm" id="btnSwitchCamera" style="padding: 10px 14px; border-radius: 10px;" title="Ganti Kamera Depan / Belakang">
                                <i class="fas fa-sync-alt"></i> Ganti Kamera
                            </button>
                            <button type="button" class="btn btn-ghost btn-sm" id="btnCloseCamera" style="padding: 10px 14px; border-radius: 10px;">
                                <i class="fas fa-times"></i> Batal
                            </button>
                        </div>

                        <!-- Hasil Jepretan Preview -->
                        <div id="capturedPreviewBox" style="display: none; margin-top: 6px;">
                            <div style="position: relative; display: inline-block; max-width: 100%; border-radius: 12px; overflow: hidden; border: 2.5px solid #22c55e;">
                                <img id="capturedPhotoImg" src="" alt="Foto Kehadiran" style="max-width: 100%; max-height: 380px; display: block; border-radius: 9px;">
                                <div style="position: absolute; bottom: 8px; left: 8px; background: rgba(34, 197, 94, 0.92); color: white; font-size: 0.75rem; font-weight: 700; padding: 4px 10px; border-radius: 6px; display: flex; align-items: center; gap: 5px; box-shadow: 0 2px 6px rgba(0,0,0,0.3);">
                                    <i class="fas fa-check-circle"></i> Foto Kamera Terverifikasi
                                </div>
                            </div>
                            <div style="margin-top: 10px; display: flex; gap: 8px; justify-content: center;">
                                <button type="button" class="btn btn-secondary btn-sm" id="btnRetakePhoto" style="padding: 8px 16px; border-radius: 8px;">
                                    <i class="fas fa-redo"></i> Foto Ulang
                                </button>
                            </div>
                        </div>

                        <!-- Fallback Alert jika izin kamera ditolak / error -->
                        <div id="cameraErrorBox" style="display: none; padding: 12px; background: rgba(239, 68, 68, 0.1); border-radius: 8px; margin-top: 12px; border: 1px solid rgba(239, 68, 68, 0.3); text-align: center;">
                            <div style="color: var(--danger); font-size: 0.85rem; margin-bottom: 8px;">
                                <i class="fas fa-exclamation-triangle"></i> <span id="cameraErrorMessage">Akses kamera peramban tidak dapat dibuka.</span>
                            </div>
                            <button type="button" class="btn btn-warning btn-sm" id="btnTriggerNativeCamera">
                                <i class="fas fa-camera"></i> Buka Kamera Bawaan HP Langsung
                            </button>
                        </div>
                    </div>

                    <!-- Hidden Inputs for Form Submission -->
                    <!-- Input capture="environment" fallback (strictly opens camera app on mobile, bypassing file selector) -->
                    <input type="file" id="nativeCameraInput" accept="image/*" capture="environment" style="display: none;">
                    <input type="hidden" name="bukti_foto_base64" id="bukti_foto_base64" value="">
                    <canvas id="snapshotCanvas" style="display: none;"></canvas>

                    <small id="helperBuktiKamera" style="color: var(--text-muted); display: block; margin-top: 6px;">
                        {{ $proofHelper }}
                    </small>
                </div>
            @endif

            <!-- STANDARD FILE UPLOAD (FOR IZIN/SAKIT OR NON-KEGIATAN) -->
            <div class="form-group" id="fileUploadWrapper" style="{{ $isKegiatan ? 'display: none;' : '' }}">
                <label class="form-label" for="bukti_foto" id="labelBuktiFoto">
                    Foto Bukti Dokumentasi <span class="required" id="reqBuktiFoto" style="color: var(--danger);">* Wajib</span>
                </label>
                @if($existingAttendance?->bukti_foto_url)
                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                        <img src="{{ $existingAttendance->bukti_foto_url }}" alt="Bukti Foto Sebelumnya" style="width: 48px; height: 48px; object-fit: cover; border-radius: 8px; border: 1.5px solid var(--border-color);">
                        <span style="font-size: 0.8rem; color: var(--text-muted);">Foto bukti sebelumnya sudah tersimpan. Unggah file baru jika ingin mengganti.</span>
                    </div>
                @endif
                <input type="file" name="bukti_foto" id="bukti_foto" class="form-control @error('bukti_foto') is-invalid @enderror" accept="image/*" {{ (!$isKegiatan && !$existingAttendance?->bukti_foto) ? 'required' : '' }}>
                @error('bukti_foto')
                    <div class="invalid-feedback" style="display: block; color: var(--danger); font-size: 0.8rem; margin-top: 4px;">
                        <i class="fas fa-exclamation-circle"></i> {{ $message }}
                    </div>
                @enderror
                <small id="helperBuktiFoto" style="color: var(--text-muted); display: block; margin-top: 4px;">
                    {{ $proofHelper }}
                </small>
            </div>

            <button type="submit" class="btn btn-primary btn-block btn-lg" id="btnOnlineSubmit" style="margin-top: var(--space-6);" {{ ($eligibilityError || $member->sp_level >= 3) ? 'disabled' : '' }}>
                <i class="fas fa-paper-plane"></i> {{ $existingAttendance ? 'Perbarui Presensi' : 'Kirim Presensi Kehadiran' }}
            </button>
        </form>
    </div>
@endif
@endsection

@push('styles')
<style>
    @keyframes pulse-red {
        0%, 100% { opacity: 1; transform: scale(1); }
        50% { opacity: 0.4; transform: scale(1.15); }
    }
    .pulse-cam {
        animation: pulse-red 1.5s infinite ease-in-out;
    }
    .camera-viewport-box {
        background: #050e18;
        border: 2px dashed rgba(56, 189, 248, 0.4);
        border-radius: 16px;
        padding: 16px;
        text-align: center;
        position: relative;
        transition: all 0.2s ease;
    }
    .camera-viewport-box.active {
        border-color: #38bdf8;
        border-style: solid;
        box-shadow: 0 0 15px rgba(56, 189, 248, 0.15);
    }
    .camera-viewport-box.captured {
        border-color: #22c55e;
        border-style: solid;
        box-shadow: 0 0 15px rgba(34, 197, 94, 0.15);
    }
</style>
@endpush

@push('scripts')
<script>
    const hasExistingProof = {{ ($existingAttendance && filled($existingAttendance->bukti_foto)) ? 'true' : 'false' }};
    const isKegiatan = {{ $isKegiatan ? 'true' : 'false' }};
    const proofHelperHadir = @json($proofHelper);

    let currentStream = null;
    let currentFacingMode = 'environment'; // default kamera belakang untuk dokumentasi lokasi kegiatan

    async function startCamera(facingMode = 'environment') {
        stopCamera();
        currentFacingMode = facingMode;

        const videoEl = document.getElementById('cameraVideo');
        const container = document.getElementById('videoContainer');
        const placeholder = document.getElementById('cameraPlaceholder');
        const controls = document.getElementById('cameraControls');
        const errorBox = document.getElementById('cameraErrorBox');
        const previewBox = document.getElementById('capturedPreviewBox');

        if (errorBox) errorBox.style.display = 'none';
        if (previewBox) previewBox.style.display = 'none';

        try {
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                throw new Error('Browser tidak mendukung WebRTC camera stream');
            }

            const constraints = {
                video: {
                    facingMode: { ideal: facingMode },
                    width: { ideal: 1280 },
                    height: { ideal: 720 }
                },
                audio: false
            };

            const stream = await navigator.mediaDevices.getUserMedia(constraints);
            currentStream = stream;
            if (videoEl) {
                videoEl.srcObject = stream;
                videoEl.setAttribute('playsinline', '');
                videoEl.setAttribute('muted', '');
                videoEl.setAttribute('autoplay', '');
                await videoEl.play();
            }

            if (placeholder) placeholder.style.display = 'none';
            if (container) container.style.display = 'block';
            if (controls) controls.style.display = 'flex';
            document.getElementById('cameraBox')?.classList.add('active');
            document.getElementById('cameraBox')?.classList.remove('captured');
        } catch (err) {
            console.warn('WebRTC camera error, triggering fallback UI:', err);
            if (container) container.style.display = 'none';
            if (placeholder) placeholder.style.display = 'block';
            if (controls) controls.style.display = 'none';
            if (errorBox) {
                errorBox.style.display = 'block';
                const msgEl = document.getElementById('cameraErrorMessage');
                if (msgEl) {
                    if (err.name === 'NotAllowedError' || err.name === 'PermissionDeniedError') {
                        msgEl.textContent = 'Izin kamera ditolak. Silakan izinkan kamera di browser atau gunakan tombol Kamera Bawaan HP.';
                    } else {
                        msgEl.textContent = 'Kamera langsung tidak dapat diakses (' + (err.message || 'error') + '). Silakan gunakan tombol Kamera Bawaan HP.';
                    }
                }
            }
        }
    }

    function stopCamera() {
        if (currentStream) {
            currentStream.getTracks().forEach(track => track.stop());
            currentStream = null;
        }
        const videoEl = document.getElementById('cameraVideo');
        if (videoEl) {
            videoEl.srcObject = null;
        }
    }

    function switchCamera() {
        const nextMode = currentFacingMode === 'user' ? 'environment' : 'user';
        startCamera(nextMode);
    }

    function closeCamera() {
        stopCamera();
        const container = document.getElementById('videoContainer');
        const placeholder = document.getElementById('cameraPlaceholder');
        const controls = document.getElementById('cameraControls');
        if (container) container.style.display = 'none';
        if (controls) controls.style.display = 'none';
        if (placeholder) placeholder.style.display = 'block';
        document.getElementById('cameraBox')?.classList.remove('active');
    }

    function captureSnapshot() {
        const videoEl = document.getElementById('cameraVideo');
        const canvas = document.getElementById('snapshotCanvas');
        if (!videoEl || !canvas) return;

        const width = videoEl.videoWidth || 640;
        const height = videoEl.videoHeight || 480;

        canvas.width = width;
        canvas.height = height;

        const ctx = canvas.getContext('2d');
        ctx.drawImage(videoEl, 0, 0, width, height);

        const dataUrl = canvas.toDataURL('image/jpeg', 0.88);

        stopCamera();

        const previewImg = document.getElementById('capturedPhotoImg');
        if (previewImg) previewImg.src = dataUrl;

        const base64Input = document.getElementById('bukti_foto_base64');
        if (base64Input) base64Input.value = dataUrl;

        canvas.toBlob(function(blob) {
            if (blob) {
                const fileName = 'foto_kehadiran_' + Date.now() + '.jpg';
                const file = new File([blob], fileName, { type: 'image/jpeg' });
                const inputBukti = document.getElementById('bukti_foto');
                if (inputBukti && window.DataTransfer) {
                    try {
                        const dt = new DataTransfer();
                        dt.items.add(file);
                        inputBukti.files = dt.files;
                    } catch(e) {}
                }
            }
        }, 'image/jpeg', 0.88);

        document.getElementById('videoContainer').style.display = 'none';
        document.getElementById('cameraPlaceholder').style.display = 'none';
        document.getElementById('cameraControls').style.display = 'none';
        document.getElementById('capturedPreviewBox').style.display = 'block';
        document.getElementById('cameraBox')?.classList.remove('active');
        document.getElementById('cameraBox')?.classList.add('captured');
    }

    function retakePhoto() {
        const base64Input = document.getElementById('bukti_foto_base64');
        if (base64Input) base64Input.value = '';
        const inputBukti = document.getElementById('bukti_foto');
        if (inputBukti) inputBukti.value = '';

        document.getElementById('capturedPreviewBox').style.display = 'none';
        startCamera(currentFacingMode);
    }

    function triggerNativeCamera() {
        const nativeInput = document.getElementById('nativeCameraInput');
        if (nativeInput) {
            nativeInput.click();
        }
    }

    function handleNativeCameraChange(e) {
        const file = e.target.files?.[0];
        if (!file) return;

        const reader = new FileReader();
        reader.onload = function(evt) {
            const dataUrl = evt.target.result;
            const base64Input = document.getElementById('bukti_foto_base64');
            if (base64Input) base64Input.value = dataUrl;

            const previewImg = document.getElementById('capturedPhotoImg');
            if (previewImg) previewImg.src = dataUrl;

            const inputBukti = document.getElementById('bukti_foto');
            if (inputBukti && window.DataTransfer) {
                try {
                    const dt = new DataTransfer();
                    dt.items.add(file);
                    inputBukti.files = dt.files;
                } catch(err) {}
            }

            stopCamera();
            document.getElementById('videoContainer').style.display = 'none';
            document.getElementById('cameraPlaceholder').style.display = 'none';
            document.getElementById('cameraControls').style.display = 'none';
            document.getElementById('cameraErrorBox').style.display = 'none';
            document.getElementById('capturedPreviewBox').style.display = 'block';
            document.getElementById('cameraBox')?.classList.remove('active');
            document.getElementById('cameraBox')?.classList.add('captured');
        };
        reader.readAsDataURL(file);
    }

    function updateDocRequirement() {
        const isHadir = document.getElementById('statusHadir')?.checked ?? true;
        const inputBukti = document.getElementById('bukti_foto');
        const reqBadge = document.getElementById('reqBuktiFoto');
        const helper = document.getElementById('helperBuktiFoto');
        const cameraSection = document.getElementById('cameraKegiatanWrapper');
        const fileSection = document.getElementById('fileUploadWrapper');

        if (isKegiatan) {
            if (isHadir) {
                if (cameraSection) cameraSection.style.display = 'block';
                if (fileSection) fileSection.style.display = 'none';
                if (inputBukti) inputBukti.required = false;
            } else {
                stopCamera();
                if (cameraSection) cameraSection.style.display = 'none';
                if (fileSection) fileSection.style.display = 'block';
                if (reqBadge) reqBadge.style.display = 'none';
                if (inputBukti) inputBukti.required = false;
                if (helper) {
                    helper.textContent = 'Lampirkan surat keterangan izin atau surat sakit jika ada (opsional, maksimal 5MB).';
                }
            }
        } else {
            if (cameraSection) cameraSection.style.display = 'none';
            if (fileSection) fileSection.style.display = 'block';
            if (isHadir) {
                if (reqBadge) reqBadge.style.display = 'inline';
                if (!hasExistingProof && inputBukti) {
                    inputBukti.required = true;
                }
                if (helper) {
                    helper.textContent = proofHelperHadir;
                }
            } else {
                if (reqBadge) reqBadge.style.display = 'none';
                if (inputBukti) inputBukti.required = false;
                if (helper) {
                    helper.textContent = 'Lampirkan surat keterangan izin atau surat sakit jika ada (opsional, maksimal 5MB).';
                }
            }
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        updateDocRequirement();

        document.getElementById('btnStartCamera')?.addEventListener('click', () => startCamera('environment'));
        document.getElementById('btnSwitchCamera')?.addEventListener('click', switchCamera);
        document.getElementById('btnCloseCamera')?.addEventListener('click', closeCamera);
        document.getElementById('btnCapturePhoto')?.addEventListener('click', captureSnapshot);
        document.getElementById('btnRetakePhoto')?.addEventListener('click', retakePhoto);
        document.getElementById('btnTriggerNativeCamera')?.addEventListener('click', triggerNativeCamera);
        document.getElementById('btnTriggerNativeDirect')?.addEventListener('click', triggerNativeCamera);

        document.getElementById('nativeCameraInput')?.addEventListener('change', handleNativeCameraChange);

        const form = document.querySelector('form');
        if (form) {
            form.addEventListener('submit', function(e) {
                const isHadir = document.getElementById('statusHadir')?.checked ?? true;
                if (isKegiatan && isHadir && !hasExistingProof) {
                    const inputBukti = document.getElementById('bukti_foto');
                    const hasFile = inputBukti && inputBukti.files && inputBukti.files.length > 0;
                    const hasBase64 = document.getElementById('bukti_foto_base64')?.value;
                    if (!hasFile && !hasBase64) {
                        e.preventDefault();
                        alert('Wajib mengambil foto kehadiran menggunakan kamera terlebih dahulu!');
                        const cameraBox = document.getElementById('cameraKegiatanWrapper');
                        if (cameraBox) cameraBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        return false;
                    }
                }
            });
        }
    });

    window.addEventListener('beforeunload', stopCamera);
</script>
@endpush
