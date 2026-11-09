@extends('layouts.portal')

@section('title', 'QR Saya')
@section('page_icon')
    <i class="fas fa-qrcode"></i>
@endsection

@section('content')
<style>
.qr-instruction-card {
    display: flex;
    align-items: flex-start;
    gap: 14px;
    padding: 16px 20px;
    background: var(--info-light, #F0F9FF);
    border: 1px solid var(--info-border, #BAE6FD);
    border-radius: var(--radius-lg, 12px);
    margin-bottom: var(--space-6, 1.5rem);
    box-shadow: 0 2px 8px rgba(2, 132, 199, 0.06);
    transition: all var(--transition-base, 0.25s ease);
}
.qr-instruction-card .instruction-icon {
    font-size: 1.25rem;
    color: var(--info, #0284C7);
    flex-shrink: 0;
    margin-top: 2px;
}
.qr-instruction-card .instruction-body {
    flex: 1;
    min-width: 0;
}
.qr-instruction-card .instruction-title {
    display: block;
    font-size: var(--font-sm, 0.875rem);
    font-weight: 700;
    color: #0369A1;
    margin-bottom: 4px;
}
.qr-instruction-card .instruction-text {
    font-size: var(--font-sm, 0.875rem);
    line-height: 1.55;
    color: var(--text-secondary, #334155);
    margin: 0;
}
[data-theme="dark"] .qr-instruction-card {
    background: rgba(2, 132, 199, 0.12) !important;
    border-color: rgba(56, 189, 248, 0.28) !important;
    box-shadow: 0 4px 14px rgba(0, 0, 0, 0.25) !important;
}
[data-theme="dark"] .qr-instruction-card .instruction-icon {
    color: #38BDF8 !important;
}
[data-theme="dark"] .qr-instruction-card .instruction-title {
    color: #7DD3FC !important;
}
[data-theme="dark"] .qr-instruction-card .instruction-text {
    color: #E2E8F0 !important;
}
</style>

<div class="portal-page-header">
    <h1 class="portal-page-title">
        <i class="fas fa-qrcode"></i> QR Code Saya
    </h1>
</div>

<div class="card mb-6" style="text-align: center;">
    <div class="qr-display">
        @php
            $qrSrc = $filename
                ? asset('assets/img/qrcodes/' . $filename)
                : 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($member->qr_data);
        @endphp

        <div class="qr-card" style="display: inline-block; padding: 18px; background: #fff; border-radius: 26px; box-shadow: 0 6px 0 #061A2E; border: 2px solid #061A2E;">
            <img src="{{ $qrSrc }}"
                 alt="QR Code {{ $member->nama_lengkap }}"
                 id="qrImage"
                 style="width: 240px; max-width: 100%; height: auto; aspect-ratio: 1/1; display: block; margin: 0 auto;">
        </div>

        <div class="qr-info mt-4">
            <h2 style="font-size: var(--font-xl); margin-bottom: 6px; color: var(--text-primary);">
                {{ $member->nama_lengkap }}
            </h2>
            <p style="margin-bottom: 6px;">
                @if ($member->isBphInti())
                    <span class="badge badge-warning" style="font-size: var(--font-sm);"><i class="fas fa-crown"></i> Pengurus Inti</span>
                @else
                    <span class="badge badge-dept" style="font-size: var(--font-sm);"><i class="fas fa-building"></i> Dept. {{ $member->departemen }}</span>
                @endif
                @if ($member->is_bph)
                    <span class="badge badge-warning" style="font-size: var(--font-sm); margin-left: 4px;"><i class="fas fa-shield-alt"></i> BPH</span>
                @endif
                @if ($member->sp_level == 1)
                    <span class="badge badge-sp-1" style="font-size: var(--font-sm); margin-left: 4px;"><i class="fas fa-exclamation-triangle"></i> SP 1</span>
                @elseif ($member->sp_level == 2)
                    <span class="badge badge-sp-2" style="font-size: var(--font-sm); margin-left: 4px;"><i class="fas fa-exclamation-circle"></i> SP 2</span>
                @elseif ($member->sp_level >= 3)
                    <span class="badge badge-sp-3" style="font-size: var(--font-sm); margin-left: 4px;"><i class="fas fa-user-times"></i> SP 3</span>
                @endif
            </p>
            <p style="font-size: var(--font-sm); color: var(--text-secondary);">
                <i class="fas fa-id-badge"></i> {{ $member->jabatan }}
            </p>
            <div class="mt-2">
                <code style="font-size: var(--font-xs); background: var(--dark-surface-2); padding: 4px 10px; border-radius: 4px;">
                    {{ $member->qr_code }}
                </code>
            </div>
        </div>

        <div style="display: flex; gap: var(--space-3); justify-content: center; margin-top: var(--space-6); flex-wrap: wrap;">
            <button class="btn btn-primary" onclick="downloadBadge()">
                <i class="fas fa-id-card"></i> Download Kartu QR
            </button>
            <a href="{{ route('portal.qr.download') }}" class="btn btn-secondary">
                <i class="fas fa-file-image"></i> Unduh File PNG
            </a>
        </div>
    </div>
</div>

<!-- Petunjuk Absensi Permanen (Tidak akan menghilang) -->
<div class="qr-instruction-card">
    <div class="instruction-icon">
        <i class="fas fa-info-circle"></i>
    </div>
    <div class="instruction-body">
        <strong class="instruction-title">Petunjuk Absensi:</strong>
        <p class="instruction-text">
            Simpan atau screenshot QR Code ini di smartphone kamu. Saat rapat, tunjukkan QR ini ke kamera scanner panitia / admin untuk langsung tercatat hadir.
        </p>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function downloadBadge() {
        const img = document.getElementById('qrImage');
        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d');

        canvas.width = 450;
        canvas.height = 560;

        ctx.fillStyle = '#FFFFFF';
        ctx.fillRect(0, 0, canvas.width, canvas.height);

        ctx.fillStyle = '#30AFFF';
        ctx.fillRect(0, 0, canvas.width, 16);

        ctx.fillStyle = '#061A2E';
        ctx.font = 'bold 16px Fredoka, sans-serif';
        ctx.textAlign = 'center';
        ctx.fillText('HIMA TI POLITEKNIK HASNUR', 225, 45);

        ctx.font = '11px Noto Sans, sans-serif';
        ctx.fillStyle = '#475569';
        ctx.fillText('Kartu Identitas Absensi Digital', 225, 65);

        const qrImg = new Image();
        qrImg.crossOrigin = 'anonymous';
        qrImg.onload = function () {
            ctx.drawImage(qrImg, 75, 90, 300, 300);

            ctx.fillStyle = '#061A2E';
            ctx.font = 'bold 22px Fredoka, sans-serif';
            ctx.fillText(@json($member->nama_lengkap), 225, 425);

            ctx.font = 'bold 15px Noto Sans, sans-serif';
            ctx.fillStyle = '#0284C7';
            ctx.fillText(@json($member->isBphInti() ? 'Pengurus Inti' : 'Dept. ' . $member->departemen), 225, 455);

            ctx.font = '13px Poppins, sans-serif';
            ctx.fillStyle = '#616161';
            ctx.fillText(@json($member->jabatan), 225, 480);

            ctx.font = '10px monospace';
            ctx.fillStyle = '#9E9E9E';
            ctx.fillText(@json($member->qr_code), 225, 520);

            const link = document.createElement('a');
            link.download = 'Badge_QR_' + {!! json_encode(\Illuminate\Support\Str::slug($member->nama_lengkap)) !!} + '.png';
            link.href = canvas.toDataURL('image/png');
            link.click();
        };
        qrImg.src = img.src;
    }
</script>
@endpush
