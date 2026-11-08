@extends('layouts.portal')

@section('title', 'Ajukan Absen')
@section('page_icon')
    <i class="fas fa-file-medical"></i>
@endsection

@section('content')
<div class="portal-page-header">
    <h1 class="portal-page-title">
        <i class="fas fa-file-medical"></i> Ajukan Izin / Sakit
    </h1>
    <a href="{{ route('portal.attendance') }}" class="btn btn-secondary btn-sm">
        <i class="fas fa-arrow-left"></i> Kembali ke Riwayat
    </a>
</div>

<div class="card mb-6">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-paper-plane"></i> Form Izin / Sakit</h3>
        <a href="{{ route('portal.attendance') }}" class="btn btn-ghost btn-sm">
            <i class="fas fa-history"></i> Riwayat Absensi
        </a>
    </div>

    <div class="alert alert-info">
        <i class="fas fa-info-circle"></i>
        <span>Pengajuan ini hanya untuk rapat yang belum diselesaikan. Setelah rapat selesai, status absensi tidak dapat diubah lagi.</span>
    </div>

    @if ($attendances->isEmpty())
        <div class="empty-state">
            <i class="fas fa-calendar-check"></i>
            <h3>Tidak Ada Rapat yang Perlu Diajukan</h3>
            <p>Saat ini tidak ada rapat aktif yang terdaftar untuk Anda. Pengajuan izin/sakit dapat dilakukan ketika rapat sudah dibuat.</p>
        </div>
    @else
        <form action="{{ route('portal.absence.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="form-group">
                <label class="form-label" for="attendance_id">Pilih Rapat <span class="required">*</span></label>
                <select name="attendance_id" id="attendance_id" class="form-control" required>
                    <option value="">— Pilih Rapat —</option>
                    @foreach ($attendances as $attendance)
                        @if ($attendance->meeting)
                            <option value="{{ $attendance->id }}" {{ old('attendance_id') == $attendance->id ? 'selected' : '' }}>
                                {{ $attendance->meeting->jenis_label }}
                                — {{ $attendance->meeting->fokus_bahasan ?? $attendance->meeting->nama_event ?? 'Tanpa agenda' }}
                                ({{ $attendance->meeting->tanggal->locale('id')->translatedFormat('d M Y') }})
                                [Status: {{ ucfirst($attendance->status) }}]
                            </option>
                        @endif
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Alasan Ketidakhadiran <span class="required">*</span></label>
                <div class="segmented-control">
                    <label class="segmented-option">
                        <input type="radio" name="status" value="izin" {{ old('status', 'izin') === 'izin' ? 'checked' : '' }}>
                        <span class="segmented-btn">
                            <i class="fas fa-info-circle"></i>
                            <span>Izin</span>
                        </span>
                    </label>
                    <label class="segmented-option">
                        <input type="radio" name="status" value="sakit" {{ old('status') === 'sakit' ? 'checked' : '' }}>
                        <span class="segmented-btn">
                            <i class="fas fa-medkit"></i>
                            <span>Sakit</span>
                        </span>
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
                <i class="fas fa-paper-plane"></i> Kirim Pengajuan
            </button>
        </form>
    @endif
</div>
@endsection
