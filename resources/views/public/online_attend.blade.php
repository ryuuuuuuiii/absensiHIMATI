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
            <p style="color: var(--text-muted);">Batas Waktu Absensi Telah Berakhir. Sesi absensi untuk rapat ini telah diselesaikan dan ditutup oleh pembuat rapat.</p>
        @elseif($meeting->status === 'cancelled')
            <i class="fas fa-times-circle" style="font-size: 2.5rem; color: var(--danger); margin-bottom: var(--space-4);"></i>
            <h3>Rapat Dibatalkan</h3>
            <p style="color: var(--text-muted);">Sesi absensi untuk rapat ini telah dibatalkan.</p>
        @else
            <i class="fas fa-hourglass-end" style="font-size: 2.5rem; color: var(--danger); margin-bottom: var(--space-4);"></i>
            <h3>Batas Waktu Absensi Telah Berakhir</h3>
            <p style="color: var(--text-muted);">Link presensi untuk rapat ini sudah ditutup karena telah melewati batas waktu absensi. Silakan hubungi admin atau pembuat rapat jika ada kendala.</p>
        @endif
    </div>
@else
    <div class="card mb-6">
        <h3 class="card-title mb-4"><i class="fas fa-user-check"></i> Isi Presensi Kehadiran</h3>
        
        <form action="{{ route('public.online.submit', $meeting->unique_link) }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="form-group">
                <label class="form-label" for="user_id">Pilih Nama Anda <span class="required">*</span></label>
                <select name="user_id" id="user_id" class="form-control" required onchange="updateOnlineSpNotice(this)">
                    <option value="" data-sp="0">— Cari & Pilih Nama Anda —</option>
                    @foreach($participants as $p)
                        @php
                            $pSp = $p->member ? (int) $p->member->sp_level : 0;
                            $pSpLabel = $p->member ? $p->member->sp_label : '';
                            $pSpReason = $p->member ? $p->member->sp_reason : '';
                        @endphp
                        <option value="{{ $p->user_id }}"
                                data-sp="{{ $pSp }}"
                                data-sp-label="{{ $pSpLabel }}"
                                data-sp-reason="{{ $pSpReason }}"
                                {{ old('user_id') == $p->user_id ? 'selected' : '' }}>
                            {{ $p->member ? $p->member->nama_lengkap : 'Unknown' }} ({{ $p->member ? $p->member->departemen : '' }}{{ !empty($p->member->jabatan) ? ' — ' . $p->member->jabatan : '' }}){{ !empty($p->is_optional_inti) ? ' [Pengurus Inti - Opsional]' : '' }}
                            @if($pSp > 0) [{{ $pSpLabel }}] @endif
                            @if($p->status === 'hadir') [Sudah Hadir] @endif
                        </option>
                    @endforeach
                </select>
                <div id="onlineSpNotice" class="sp-attend-notice" style="display: none; margin-top: 10px;"></div>
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

            <button type="submit" class="btn btn-primary btn-block btn-lg" id="btnOnlineSubmit" style="margin-top: var(--space-6);">
                <i class="fas fa-paper-plane"></i> Kirim Presensi
            </button>
        </form>
    </div>
    <script>
        function updateOnlineSpNotice(selectEl) {
            const box = document.getElementById('onlineSpNotice');
            const btn = document.getElementById('btnOnlineSubmit');
            if (!box || !selectEl) return;
            const opt = selectEl.options[selectEl.selectedIndex];
            const sp = parseInt(opt?.getAttribute('data-sp') || '0', 10);
            const label = opt?.getAttribute('data-sp-label') || '';
            const reason = opt?.getAttribute('data-sp-reason') || '';

            if (sp >= 3) {
                box.style.display = 'flex';
                box.className = 'alert alert-danger';
                box.innerHTML = `<i class="fas fa-user-times"></i><div><strong>Status Akun: ${label}</strong><div style="font-size: 0.82rem;">Akun pengurus ini telah dinonaktifkan (Pemberhentian) dan tidak dapat melakukan presensi.</div></div>`;
                if (btn) btn.disabled = true;
            } else if (sp > 0) {
                box.style.display = 'flex';
                box.className = 'alert alert-warning';
                box.innerHTML = `<i class="fas fa-exclamation-triangle"></i><div><strong>Informasi Status Akun: ${label}</strong><div style="font-size: 0.82rem;">${reason ? reason : 'Harap tingkatkan kedisiplinan kehadiran rapat Anda.'}</div></div>`;
                if (btn) btn.disabled = false;
            } else {
                box.style.display = 'none';
                box.innerHTML = '';
                if (btn) btn.disabled = false;
            }
        }
        document.addEventListener('DOMContentLoaded', function() {
            const sel = document.getElementById('user_id');
            if (sel && sel.value) updateOnlineSpNotice(sel);
        });
    </script>
@endif
@endsection
