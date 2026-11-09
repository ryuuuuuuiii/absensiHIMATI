@extends('layouts.public')

@section('title', 'Cari & Download QR Code Anggota')
@section('page_heading', 'Kartu QR Code Anggota')
@section('page_subheading', 'Pencarian dan Unduh Kartu Identitas Absensi Pengurus HIMA TI')
@section('container_width', '600px')

@section('content')
<div class="card mb-6">
    <h3 class="card-title mb-4"><i class="fas fa-search"></i> Pilih atau Cari Nama Anda</h3>
    <form method="GET" action="{{ route('public.my_qr') }}" class="d-flex flex-column gap-3">
        <div class="form-group" style="margin-bottom: 0;">
            <select name="q" class="form-control" onchange="this.form.submit()">
                <option value="">-- Pilih Nama dari Daftar Anggota --</option>
                @foreach($allMembers as $m)
                    <option value="{{ $m->nama_lengkap }}" {{ ($member && $member->id == $m->id) || $search == $m->nama_lengkap ? 'selected' : '' }}>
                        {{ $m->nama_lengkap }} ({{ $m->departemen }} - {{ $m->jabatan }})
                    </option>
                @endforeach
            </select>
        </div>
        <div style="text-align: center; font-size: var(--font-xs); color: var(--text-muted);">
            — Atau ketikkan nama / email di bawah —
        </div>
        <div class="search-input-wrapper">
            <i class="fas fa-user"></i>
            <input type="text" name="q" class="form-control" placeholder="Ketik nama anggota..." value="{{ $search }}">
        </div>
        <button type="submit" class="btn btn-primary btn-block">
            <i class="fas fa-qrcode"></i> Tampilkan QR Code
        </button>
    </form>
</div>

@if($member)
    <div class="card mb-6" style="text-align: center;">
        <div class="qr-display">
            <!-- QR Card -->
            <div class="qr-card" style="display: inline-block; padding: 16px; background: #fff; border-radius: var(--radius-lg); box-shadow: 0 10px 25px rgba(0,0,0,0.2);">
                @php
                    $qrSrc = asset('assets/img/qrcodes/' . $member->qr_image);
                    if (!$member->qr_image || !file_exists(public_path('assets/img/qrcodes/' . $member->qr_image))) {
                        $qrSrc = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($member->qr_data);
                    }
                @endphp
                <img src="{{ $qrSrc }}" 
                     alt="QR Code {{ $member->nama_lengkap }}"
                     id="qrImage"
                     style="width: 240px; height: 240px; display: block; margin: 0 auto;">
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
                    @if($member->is_bph)
                        <span class="badge badge-warning" style="font-size: var(--font-sm); margin-left: 4px;"><i class="fas fa-shield-alt"></i> BPH</span>
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
                <a href="{{ route('public.my_qr.download', $member->id) }}" class="btn btn-secondary">
                    <i class="fas fa-file-image"></i> Unduh File PNG
                </a>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="alert alert-info" style="margin-bottom: 0;">
            <i class="fas fa-info-circle"></i>
            <div>
                <strong>Petunjuk Absensi:</strong><br>
                <small>Simpan atau screenshot QR Code ini di smartphone kamu. Saat rapat, tunjukkan QR ini ke kamera scanner panitia / admin untuk langsung tercatat hadir.</small>
            </div>
        </div>
    </div>

    <script>
    function downloadBadge() {
        const img = document.getElementById('qrImage');
        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d');
        
        canvas.width = 450;
        canvas.height = 560;
        
        // Card Background
        ctx.fillStyle = '#FFFFFF';
        ctx.fillRect(0, 0, canvas.width, canvas.height);
        
        // Header band
        ctx.fillStyle = '#F5921B';
        ctx.fillRect(0, 0, canvas.width, 16);

        // Header logo text
        ctx.fillStyle = '#1A1A2E';
        ctx.font = 'bold 16px Poppins, sans-serif';
        ctx.textAlign = 'center';
        ctx.fillText('HIMA TI POLITEKNIK HASNUR', 225, 45);

        ctx.font = '11px Poppins, sans-serif';
        ctx.fillStyle = '#757575';
        ctx.fillText('Kartu Identitas Absensi Digital', 225, 65);
        
        // Draw QR
        const qrImg = new Image();
        qrImg.crossOrigin = 'anonymous';
        qrImg.onload = function() {
            ctx.drawImage(qrImg, 75, 90, 300, 300);
            
            // Name
            ctx.fillStyle = '#1A1A2E';
            ctx.font = 'bold 22px Poppins, sans-serif';
            ctx.fillText(@json($member->nama_lengkap), 225, 425);
            
            // Department
            ctx.font = 'bold 15px Poppins, sans-serif';
            ctx.fillStyle = '#F5921B';
            ctx.fillText(@json($member->departemen), 225, 455);
            
            // Jabatan
            ctx.font = '13px Poppins, sans-serif';
            ctx.fillStyle = '#616161';
            ctx.fillText(@json($member->jabatan), 225, 480);
            
            // Code & footer
            ctx.font = '10px monospace';
            ctx.fillStyle = '#9E9E9E';
            ctx.fillText(@json($member->qr_code), 225, 520);
            
            // Trigger download
            const link = document.createElement('a');
            link.download = 'Badge_QR_' + {!! json_encode(\Illuminate\Support\Str::slug($member->nama_lengkap)) !!} + '.png';
            link.href = canvas.toDataURL('image/png');
            link.click();
        };
        qrImg.src = img.src;
    }
    </script>
@elseif($search)
    <div class="card text-center" style="padding: var(--space-8);">
        <i class="fas fa-search" style="font-size: 2.5rem; color: var(--text-muted); margin-bottom: var(--space-4);"></i>
        <h3>Anggota Tidak Ditemukan</h3>
        <p style="color: var(--text-muted);">Tidak ada pengurus dengan nama atau data "{{ $search }}".</p>
    </div>
@endif
@endsection
