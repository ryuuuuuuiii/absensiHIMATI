@extends('layouts.app')

@section('title', 'Scan Absensi')

@section('page_icon')
    <i class="fas fa-qrcode"></i>
@endsection

@section('content')
<!-- Meeting Info Banner -->
<div class="card mb-6" style="border-left: 4px solid var(--primary);">
    <div class="d-flex align-center justify-between flex-wrap gap-4">
        <div>
            <h3 style="color: var(--primary); margin-bottom: var(--space-1);">
                <i class="fas fa-broadcast-tower"></i> 
                {{ $meeting->jenis_label }}
            </h3>
            <p style="font-size: var(--font-sm); color: var(--text-muted);">
                {{ $meeting->tanggal->format('d M Y') }} • {{ substr($meeting->waktu_mulai, 0, 5) }} WITA
            </p>
            @if ($meeting->fokus_bahasan)
                <p style="font-size: var(--font-sm); color: var(--text-secondary); margin-top: var(--space-2);">
                    <i class="fas fa-bookmark"></i> {{ $meeting->fokus_bahasan }}
                </p>
            @endif
            @if ($meeting->nama_event)
                <p style="font-size: var(--font-sm); color: var(--text-secondary); margin-top: var(--space-2);">
                    <i class="fas fa-calendar-star"></i> {{ $meeting->nama_event }}
                </p>
            @endif
        </div>
        <div class="text-center">
            <div style="font-size: var(--font-3xl); font-weight: 800; color: var(--success);">
                <span id="hadirCounter">{{ $hadirCount }}</span>/<span id="totalCounter">{{ $attendances->count() }}</span>
            </div>
            <small style="color: var(--text-muted);">Kehadiran Tercatat</small>
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
                <button class="btn btn-secondary" onclick="capturePhoto()" id="btnPhoto">
                    <i class="fas fa-camera-retro"></i> Foto Dokumentasi
                </button>
                <button class="btn btn-success" onclick="finishAttendance()" id="btnFinish">
                    <i class="fas fa-check-circle"></i> Selesai Absensi
                </button>
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
                    <i class="fas fa-users"></i> Daftar Hadir Masuk
                </h3>
                <span class="scanned-count" id="scannedCount">{{ $hadirCount }} hadir</span>
            </div>
            
            <div id="scannedList" style="max-height: 520px; overflow-y: auto;">
                @foreach ($attendances as $a)
                    @if ($a->status === 'hadir')
                    <div class="scanned-item" data-user-id="{{ $a->user_id }}">
                        <div class="scanned-item-avatar">
                            <i class="fas fa-check"></i>
                        </div>
                        <div class="scanned-item-info">
                            <h4>{{ $a->member ? $a->member->nama_lengkap : 'Unknown' }}</h4>
                            <p>{{ $a->member ? $a->member->departemen : '-' }} • {{ $a->member ? $a->member->jabatan : '' }}</p>
                        </div>
                        <span class="scanned-item-time">
                            {{ $a->scanned_at ? $a->scanned_at->format('H:i') : '-' }}
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
        'jenis_rapat' => $meeting->jenis_rapat,
        'tanggal'     => $meeting->tanggal->format('Y-m-d'),
        'status'      => $meeting->status,
    ]) !!};
    const SCANNED_USER_IDS = @json($scannedUserIds);
    const BASE_URL = '{{ url('/') }}';
    const CSRF_TOKEN = '{{ csrf_token() }}';
</script>
<script src="{{ asset('assets/js/scanner.js') }}"></script>
@endpush
