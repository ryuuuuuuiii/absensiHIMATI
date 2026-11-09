@extends('layouts.app')

@section('title', 'Scan Absensi')

@section('page_icon')
    <i class="fas fa-qrcode"></i>
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

        <div class="meeting-attendance-counter-card">
            <div class="counter-label"><i class="fas fa-users"></i> Kehadiran</div>
            <div class="counter-number">
                <span id="hadirCounter" class="counter-hadir">{{ $hadirCount }}</span>
                <span class="counter-divider">/</span>
                <span id="totalCounter" class="counter-total">{{ $attendances->count() }}</span>
            </div>
            <div class="counter-subtext">Peserta Tercatat</div>
        </div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-6);" class="scanner-grid-container">
    <!-- Left Column: Scanner -->
    <div style="min-width: 0;">
        <div class="card">
            <h3 class="card-title mb-4">
                <i class="fas fa-camera"></i> Scanner Kamera QR Code
            </h3>
            
            <div class="scanner-container">
                <div id="qr-reader" class="scanner-viewport"></div>
            </div>
            
            <!-- Scan Result Display -->
            <div id="scanResult" style="display: none;" class="mt-4"></div>
            
            <!-- Scanner Actions -->
            <div class="scan-actions mt-4">
                <button class="btn btn-secondary {{ !empty($docPhotos) ? 'btn-has-doc' : '' }}" onclick="capturePhoto()" id="btnPhoto">
                    @if(!empty($docPhotos))
                        <i class="fas fa-camera"></i> <span id="btnPhotoText">Ambil Ulang</span>
                    @else
                        <i class="fas fa-camera-retro"></i> <span id="btnPhotoText">Foto Dokumentasi</span>
                    @endif
                </button>
                <button class="btn btn-success" onclick="finishAttendance()" id="btnFinish">
                    <i class="fas fa-check-circle"></i> Selesai Absensi
                </button>
            </div>

            <!-- Dokumentasi Foto (Minimal Chip) -->
            <div id="docPhotoSection" class="doc-minimal-chip mt-3" onclick="openLatestDocPhoto()" style="{{ empty($docPhotos) ? 'display: none;' : '' }}" title="Ketuk untuk melihat foto dokumentasi">
                <div class="doc-chip-left">
                    <div class="doc-chip-thumbs" id="docPhotoThumbnails">
                        @if(!empty($docPhotos))
                            <img src="{{ end($docPhotos) }}" alt="Dokumentasi" class="doc-chip-thumb">
                        @endif
                    </div>
                    <div class="doc-chip-meta">
                        <div class="doc-chip-title">
                            <i class="fas fa-check-circle"></i> Foto Dokumentasi Tersimpan
                        </div>
                        <div class="doc-chip-sub" id="docChipSub">
                            {{ count($docPhotos ?? []) > 1 ? count($docPhotos) . ' foto tersimpan • Ketuk untuk lihat' : 'Ketuk untuk melihat ukuran penuh' }}
                        </div>
                    </div>
                </div>
                <div class="doc-chip-right">
                    <span class="doc-chip-btn-view"><i class="fas fa-eye"></i> Lihat</span>
                </div>
            </div>
        </div>
        
        <!-- Share Links -->
        <div class="card mt-4">
            <h4 class="card-title mb-4" style="font-size: var(--font-base);">
                <i class="fas fa-share-alt"></i> Bagikan Link
            </h4>
            
            <!-- Absent Link -->
            <div style="margin-bottom: var(--space-4);">
                <label class="form-label" style="font-size: var(--font-xs);">
                    <i class="fas fa-user-slash" style="color: var(--danger);"></i> Link Tidak Hadir (Izin / Sakit)
                </label>
                <div class="share-link-box">
                    <input type="text" value="{{ $absentUrl }}" readonly id="absentLink">
                    <button onclick="copyToClipboard('{{ $absentUrl }}', this)">
                        <i class="fas fa-copy"></i> Salin
                    </button>
                </div>
            </div>
            
            <!-- Online Attend Link (if online mode) -->
            @if ($meeting->mode_rapat === 'online')
            <div>
                <label class="form-label" style="font-size: var(--font-xs);">
                    <i class="fas fa-link" style="color: var(--info);"></i> Link Absensi Online
                </label>
                <div class="share-link-box">
                    <input type="text" value="{{ $onlineUrl }}" readonly id="onlineLink">
                    <button onclick="copyToClipboard('{{ $onlineUrl }}', this)">
                        <i class="fas fa-copy"></i> Salin
                    </button>
                </div>
            </div>
            @endif
        </div>
    </div>
    
    <!-- Right Column: Attendance List -->
    <div style="min-width: 0;">
        <div class="card">
            <div class="scanned-list-header">
                <h3>
                    <i class="fas fa-user-check" style="color: var(--blue-primary);"></i> Daftar Hadir Masuk
                </h3>
                <span class="scanned-count" id="scannedCount">
                    <i class="fas fa-check-circle"></i> {{ $hadirCount }} hadir
                </span>
            </div>
            
            <div id="scannedList" style="max-height: 520px; overflow-y: auto;">
                @foreach ($attendances as $a)
                    @if ($a->status === 'hadir')
                    <div class="scanned-item" data-user-id="{{ $a->user_id }}" data-qr="{{ $a->member ? $a->member->qr_code : '' }}">
                        <div class="scanned-item-avatar">
                            <i class="fas fa-check"></i>
                        </div>
                        <div class="scanned-item-info">
                            <h4 style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                                <span>{{ $a->member ? $a->member->nama_lengkap : 'Unknown' }}</span>
                                @if($a->member && $a->member->sp_level == 1)
                                    <span class="badge badge-sp-1" style="font-size: 10px; padding: 1px 6px;"><i class="fas fa-exclamation-triangle"></i> SP 1</span>
                                @elseif($a->member && $a->member->sp_level == 2)
                                    <span class="badge badge-sp-2" style="font-size: 10px; padding: 1px 6px;"><i class="fas fa-exclamation-circle"></i> SP 2</span>
                                @elseif($a->member && $a->member->sp_level >= 3)
                                    <span class="badge badge-sp-3" style="font-size: 10px; padding: 1px 6px;"><i class="fas fa-user-times"></i> SP 3</span>
                                @endif
                            </h4>
                            <p>
                                @if($a->member && $a->member->isBphInti())
                                    <span class="badge badge-warning" style="font-size: 11px; padding: 2px 7px;">
                                        <i class="fas fa-crown"></i> Pengurus Inti
                                    </span>
                                @else
                                    <span class="badge badge-dept" style="font-size: 11px; padding: 2px 7px;">
                                        {{ $a->member ? $a->member->departemen : '-' }}
                                    </span>
                                @endif
                                @if($a->member && $a->member->is_bph)
                                    <span class="badge badge-warning" style="font-size: 11px; padding: 2px 7px;">
                                        <i class="fas fa-shield-alt"></i> BPH
                                    </span>
                                @endif
                                @if($a->member && $a->member->jabatan)
                                    <span>{{ $a->member->jabatan }}</span>
                                @endif
                            </p>
                        </div>
                        <span class="scanned-item-time">
                            <i class="fas fa-clock" style="color: var(--text-muted); font-size: 10px;"></i>
                            {{ $a->scanned_at ? $a->scanned_at->format('H:i') . ' WITA' : '-' }}
                        </span>
                    </div>
                    @endif
                @endforeach
                
                @if ($hadirCount === 0)
                <div class="empty-state" id="emptyState" style="padding: var(--space-8) var(--space-4);">
                    <i class="fas fa-qrcode"></i>
                    <h3>Belum Ada Scan</h3>
                    <p>Arahkan kamera ke QR Code peserta untuk mulai memindai absensi</p>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Hidden Photo Capture -->
<input type="file" id="photoInput" accept="image/*" capture="environment" style="display: none;">

<!-- Doc Photo Preview Modal -->
<div id="docPhotoModal" class="doc-modal-backdrop" style="display: none;" onclick="closeDocPhotoModal(event)">
    <div class="doc-modal-content" onclick="event.stopPropagation()">
        <div class="doc-modal-header">
            <h4><i class="fas fa-image" style="color: var(--blue-primary);"></i> Foto Dokumentasi Rapat</h4>
            <button type="button" class="doc-modal-close" onclick="closeDocPhotoModalDirect()">&times;</button>
        </div>
        <div class="doc-modal-body">
            <img id="docModalImage" src="" alt="Preview Dokumentasi">
        </div>
        <div class="doc-modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeDocPhotoModalDirect()">Tutup</button>
            <button type="button" class="btn btn-primary" onclick="closeDocPhotoModalDirect(); capturePhoto();">
                <i class="fas fa-camera"></i> Ambil Ulang / Ganti Foto
            </button>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    @media (max-width: 768px) {
        .scanner-grid-container {
            grid-template-columns: 1fr !important;
        }
    }
</style>
@endpush

@push('scripts')
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
    const MEETING_ID = {{ $meeting->id }};
    const MEETING_DATA = {!! json_encode([
        'id'          => $meeting->id,
        'unique_link' => $meeting->unique_link,
        'jenis_rapat' => $meeting->jenis_rapat,
        'tanggal'     => $meeting->tanggal->format('Y-m-d'),
        'status'      => $meeting->status,
    ]) !!};
    const SCANNED_USER_IDS = @json($scannedUserIds);
    const SCANNED_QR_CODES = @json($scannedQrCodes ?? []);
    const DOC_PHOTOS = @json($docPhotos ?? []);
    const BASE_URL = '{{ url('/') }}';
    const CSRF_TOKEN = '{{ csrf_token() }}';
    const PUBLIC_HASIL_URL = '{{ route('meetings.show', $meeting->id) }}';
</script>
<script src="{{ asset('assets/js/scanner.js') }}"></script>
@endpush
