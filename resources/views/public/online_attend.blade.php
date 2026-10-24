@extends('layouts.public')

@section('title', 'Presensi Online — ' . $meeting->jenis_label)
@section('page_heading', config('himati.app_name'))
@section('page_subheading', 'Form Kehadiran Rapat Online')
@section('container_width', '650px')

@section('content')
<!-- Meeting Info -->
<div class="card mb-6" style="border-left: 4px solid var(--primary);">
    <h3 style="color: var(--primary); margin-bottom: 4px;">{{ $meeting->jenis_label }}</h3>
    <p style="font-size: var(--font-sm); color: var(--text-muted); margin: 0 0 var(--space-3) 0;">
        {{ $meeting->tanggal->format('d M Y') }} • {{ substr($meeting->waktu_mulai, 0, 5) }} WITA
        @if($meeting->creator) • Oleh: {{ $meeting->creator->nama_lengkap }} @endif
    </p>
    @if ($meeting->fokus_bahasan)
        <p style="font-size: var(--font-sm); color: var(--text-secondary); margin: 0;">
            <i class="fas fa-bookmark"></i> {{ $meeting->fokus_bahasan }}
        </p>
    @endif
</div>

@if($isExpired)
    <div class="card text-center" style="padding: var(--space-8);">
        <i class="fas fa-hourglass-end" style="font-size: 2.5rem; color: var(--danger); margin-bottom: var(--space-4);"></i>
        <h3>Batas Waktu Absensi Telah Berakhir</h3>
        <p style="color: var(--text-muted);">Link presensi untuk rapat ini sudah ditutup. Silakan hubungi admin atau pembuat rapat jika ada kendala.</p>
    </div>
@else
    <div class="card mb-6">
        <h3 class="card-title mb-4"><i class="fas fa-user-check"></i> Isi Presensi Kehadiran</h3>
        
        <form action="{{ route('public.online.submit', $meeting->unique_link) }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="form-group">
                <label class="form-label" for="user_id">Pilih Nama Anda <span class="required">*</span></label>
                <select name="user_id" id="user_id" class="form-control" required>
                    <option value="">— Cari & Pilih Nama Anda —</option>
                    @foreach($participants as $p)
                        <option value="{{ $p->user_id }}" {{ old('user_id') == $p->user_id ? 'selected' : '' }}>
                            {{ $p->member ? $p->member->nama_lengkap : 'Unknown' }} ({{ $p->member ? $p->member->departemen : '' }})
                            @if($p->status === 'hadir') [Sudah Hadir] @endif
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Status Kehadiran <span class="required">*</span></label>
                <div style="display: flex; gap: var(--space-4); flex-wrap: wrap;">
                    <label class="form-check">
                        <input type="radio" name="status" value="hadir" checked>
                        <span class="form-check-label"><i class="fas fa-check-circle text-success"></i> Hadir</span>
                    </label>
                    <label class="form-check">
                        <input type="radio" name="status" value="izin">
                        <span class="form-check-label"><i class="fas fa-info-circle text-info"></i> Izin</span>
                    </label>
                    <label class="form-check">
                        <input type="radio" name="status" value="sakit">
                        <span class="form-check-label"><i class="fas fa-medkit text-warning"></i> Sakit</span>
                    </label>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="keterangan">Keterangan (Opsional)</label>
                <textarea name="keterangan" id="keterangan" class="form-control" placeholder="Contoh: Mengikuti via Google Meet / Kendala jaringan" rows="2"></textarea>
            </div>

            <div class="form-group">
                <label class="form-label" for="bukti_foto">Foto Bukti / Selfie (Opsional)</label>
                <input type="file" name="bukti_foto" id="bukti_foto" class="form-control" accept="image/*">
                <small style="color: var(--text-muted); display: block; margin-top: 4px;">
                    Screenshot tampilan zoom/meet atau foto kehadiran Anda (maksimal 5MB).
                </small>
            </div>

            <button type="submit" class="btn btn-primary btn-block btn-lg" style="margin-top: var(--space-6);">
                <i class="fas fa-paper-plane"></i> Kirim Presensi
            </button>
        </form>
    </div>
@endif
@endsection
