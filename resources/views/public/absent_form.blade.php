@extends('layouts.public')

@section('title', 'Form Izin / Sakit — ' . $meeting->jenis_label)
@section('page_heading', config('himati.app_name'))
@section('page_subheading', 'Form Keterangan Ketidakhadiran Rapat')
@section('container_width', '650px')

@section('content')
<!-- Meeting Info -->
<div class="card mb-6" style="border-left: 4px solid var(--danger);">
    <h3 style="color: var(--danger); margin-bottom: 4px;">{{ $meeting->jenis_label }}</h3>
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
@endsection
