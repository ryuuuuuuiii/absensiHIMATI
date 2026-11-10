@extends('layouts.app')

@section('title', 'Kelola Anggota')

@section('page_icon')
    <i class="fas fa-users-cog"></i>
@endsection

@section('content')
<!-- Filter Bar -->
<div class="filter-bar">
    <form method="GET" action="{{ route('members.index') }}" class="search-input-wrapper">
        <i class="fas fa-search"></i>
        <input type="text" name="q" class="form-control" placeholder="Cari nama, jabatan, atau QR code..." value="{{ $search }}">
        @if($dept)
            <input type="hidden" name="dept" value="{{ $dept }}">
        @endif
        @if(isset($sp) && $sp !== null && $sp !== '')
            <input type="hidden" name="sp" value="{{ $sp }}">
        @endif
    </form>
    
    <select class="form-control" style="max-width: 190px;" onchange="location.href='{{ route('members.index') }}?dept=' + this.value + '{{ $search ? '&q=' . urlencode($search) : '' }}{{ isset($sp) && $sp !== null && $sp !== '' ? '&sp=' . urlencode($sp) : '' }}'">
        <option value="">Semua Departemen</option>
        @foreach ($departments as $key => $label)
            <option value="{{ $key }}" {{ $dept === $key ? 'selected' : '' }}>{{ $label }}</option>
        @endforeach
    </select>

    <select class="form-control" style="max-width: 190px;" onchange="location.href='{{ route('members.index') }}?sp=' + this.value + '{{ $dept ? '&dept=' . urlencode($dept) : '' }}{{ $search ? '&q=' . urlencode($search) : '' }}'">
        <option value="">Semua Status SP</option>
        <option value="0" {{ ($sp ?? '') === '0' ? 'selected' : '' }}>Aman (Tanpa SP)</option>
        <option value="any" {{ ($sp ?? '') === 'any' ? 'selected' : '' }}>Memiliki SP (1–3)</option>
        <option value="1" {{ ($sp ?? '') === '1' ? 'selected' : '' }}>SP 1</option>
        <option value="2" {{ ($sp ?? '') === '2' ? 'selected' : '' }}>SP 2</option>
        <option value="3" {{ ($sp ?? '') === '3' ? 'selected' : '' }}>SP 3 (Diberhentikan)</option>
    </select>
    
    <a href="{{ route('members.create') }}" class="btn btn-primary">
        <i class="fas fa-user-plus"></i> Tambah Anggota
    </a>
    
    <a href="{{ route('members.generate_qr') }}" class="btn btn-secondary">
        <i class="fas fa-qrcode"></i> Generate QR
    </a>

    <a href="{{ route('members.accounts') }}" class="btn btn-primary">
        <i class="fas fa-user-shield"></i> Kelola Akun
    </a>

    <a href="{{ route('members.download_qr_zip') }}" class="btn btn-success" title="Download Semua QR Code Anggota dalam format ZIP">
        <i class="fas fa-file-archive"></i> Download ZIP
    </a>
</div>

<!-- Members Table -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-users"></i> Daftar Anggota HIMA TI
        </h3>
        <span class="badge badge-primary">{{ $members->total() }} anggota terdaftar</span>
    </div>

    @if ($members->isEmpty())
        <div class="empty-state">
            <i class="fas fa-user-slash"></i>
            <h3>Tidak Ada Anggota</h3>
            <p>Tidak ditemukan anggota yang sesuai dengan kriteria pencarian.</p>
            <a href="{{ route('members.create') }}" class="btn btn-primary mt-4">
                <i class="fas fa-user-plus"></i> Tambah Anggota Sekarang
            </a>
        </div>
    @else
        <div class="table-wrapper">
            <table class="table">
                <thead>
                    <tr>
                        <th>Anggota</th>
                        <th>Departemen</th>
                        <th>Jabatan</th>
                        <th>Status SP</th>
                        <th>QR Code</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($members as $m)
                    @php
                        $alphaStreak = $m->consecutive_alpha_count;
                    @endphp
                    <tr style="{{ $m->isDismissed() ? 'opacity: 0.78;' : '' }}">
                        <td>
                            <div class="d-flex align-center gap-3">
                                <div class="header-user-avatar" style="width: 38px; height: 38px; font-size: var(--font-sm); overflow: hidden; padding: 0;">
                                    @if ($m->profile_photo_url)
                                        <img src="{{ $m->profile_photo_url }}" alt="{{ $m->nama_lengkap }}" style="width: 100%; height: 100%; object-fit: cover;">
                                    @else
                                        {{ $m->initials }}
                                    @endif
                                </div>
                                <div>
                                    <div style="font-weight: 600;">
                                        {{ $m->nama_lengkap }}
                                        @if($m->is_bph)
                                            <span class="badge badge-warning" style="font-size: 10px; margin-left: 4px;">BPH</span>
                                        @endif
                                        @if($m->isDismissed())
                                            <span class="badge badge-danger" style="font-size: 10px; margin-left: 4px;">Akun Nonaktif (SP 3)</span>
                                        @elseif($m->hasAccount())
                                            <span class="badge badge-success" style="font-size: 10px; margin-left: 4px;">Akun Aktif</span>
                                        @else
                                            <span class="badge badge-danger" style="font-size: 10px; margin-left: 4px;">Belum Ada Akun</span>
                                        @endif
                                    </div>
                                    <small style="color: var(--text-muted);">{{ $m->email ?? 'Tanpa email' }}</small>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="badge badge-dept">{{ $m->departemen }}</span>
                        </td>
                        <td>
                            <div>{{ $m->jabatan }}</div>
                            <div style="margin-top: 3px;">
                                @php $lvl = $m->level_data; @endphp
                                <span class="badge" style="background: rgba(59, 130, 246, 0.12); color: var(--blue-primary); font-size: 10.5px; padding: 2px 6px;" title="{{ $lvl['title'] }} &bull; {{ number_format($lvl['exp']) }} EXP">
                                    <i class="fas fa-{{ $lvl['icon'] }}" style="color: {{ $lvl['badge_color'] }};"></i> Lv. {{ $lvl['level'] }} &bull; {{ number_format($lvl['exp']) }} EXP
                                </span>
                            </div>
                        </td>
                        <td>
                            <div style="display: flex; flex-direction: column; align-items: flex-start; gap: 3px;">
                                @if($m->sp_level == 1)
                                    <span class="badge badge-sp-1" title="{{ $m->sp_reason }}">
                                        <i class="fas fa-exclamation-triangle"></i> SP 1
                                    </span>
                                @elseif($m->sp_level == 2)
                                    <span class="badge badge-sp-2" title="{{ $m->sp_reason }}">
                                        <i class="fas fa-exclamation-circle"></i> SP 2
                                    </span>
                                @elseif($m->sp_level >= 3)
                                    <span class="badge badge-sp-3" title="{{ $m->sp_reason }}">
                                        <i class="fas fa-user-times"></i> SP 3 (Diberhentikan)
                                    </span>
                                @else
                                    <span class="badge badge-success" style="font-size: 11px;">
                                        <i class="fas fa-shield-alt"></i> Aman
                                    </span>
                                @endif

                                @if($m->sp_level > 0)
                                    <small style="font-size: 10.5px; color: var(--text-muted); max-width: 210px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="{{ $m->sp_source_label }} — {{ $m->sp_reason_display }}">
                                        <i class="fas {{ $m->isSpFromAlpha() ? 'fa-calendar-times' : 'fa-user-shield' }}"></i>
                                        {{ $m->isSpFromAlpha() ? '3x Alpha' : 'Pelanggaran' }}: {{ $m->sp_reason_display }}
                                    </small>
                                @endif

                                @if($alphaStreak > 0 && $m->sp_level < 3)
                                    <small style="font-size: 11px; color: var(--warning); font-weight: 600;">
                                        <i class="fas fa-history"></i> Alpha berturut: {{ $alphaStreak }}/3
                                    </small>
                                @endif
                            </div>
                        </td>
                        <td>
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <code style="font-size: var(--font-xs); background: var(--dark-surface-2); padding: 4px 8px; border-radius: var(--radius-sm);">
                                    {{ $m->qr_code }}
                                </code>
                                <a href="{{ route('members.download_qr_single', $m->id) }}" class="btn btn-sm btn-ghost" title="Unduh Gambar QR Code">
                                    <i class="fas fa-download"></i>
                                </a>
                            </div>
                        </td>
                        <td>
                            <div style="display: flex; gap: 6px; align-items: center; flex-wrap: wrap;">
                                @if($m->sp_level < 3)
                                    @php
                                        $nextSp = ((int) $m->sp_level) + 1;
                                    @endphp
                                    <button
                                        type="button"
                                        class="btn btn-sm {{ $nextSp === 3 ? 'btn-danger' : 'btn-secondary' }}"
                                        style="padding: 4px 8px; font-size: 11px;"
                                        title="Tambahkan SP karena Pelanggaran (Naik ke SP {{ $nextSp }})"
                                        data-action-url="{{ route('members.sp.add', $m->id) }}"
                                        data-member-name="{{ $m->nama_lengkap }}"
                                        data-member-dept="{{ $m->departemen }} — {{ $m->jabatan }}"
                                        data-current-sp="{{ (int) $m->sp_level }}"
                                        data-next-sp="{{ $nextSp }}"
                                        onclick="openAddSpModal(this)">
                                        <i class="fas fa-exclamation-triangle"></i> +SP {{ $nextSp }}
                                    </button>
                                @endif

                                @if($m->sp_level > 0)
                                    <form action="{{ route('members.sp.remove', $m->id) }}" method="POST" onsubmit="return confirm('Cabut / hapus status {{ $m->sp_label }} dari {{ addslashes($m->nama_lengkap) }} dan aktifkan kembali?')" style="margin: 0;">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-ghost text-success" style="padding: 4px 8px; font-size: 11px;" title="Hapus / Cabut SP">
                                            <i class="fas fa-undo"></i> Cabut SP
                                        </button>
                                    </form>
                                @endif

                                @if($m->email)
                                    <form action="{{ route('members.accounts.reset', $m->id) }}" method="POST" onsubmit="return confirm('Reset password akun {{ addslashes($m->nama_lengkap) }} ke password default ({{ config('himati.default_member_password') }})?')" style="margin: 0; display: inline;">
                                        @csrf
                                        <button type="submit" class="btn btn-ghost btn-sm text-warning" title="Reset Password ke Default ({{ config('himati.default_member_password') }})">
                                            <i class="fas fa-key"></i>
                                        </button>
                                    </form>
                                @endif

                                <a href="{{ route('members.edit', $m->id) }}" class="btn btn-ghost btn-sm" title="Edit Anggota">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form action="{{ route('members.destroy', $m->id) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus anggota {{ $m->nama_lengkap }}?')" style="margin: 0;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-ghost btn-sm text-danger" title="Hapus Anggota">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        
        <div class="mt-4">
            {{ $members->links() }}
        </div>
    @endif
</div>

<!-- Pop-up Modal Tambah Surat Peringatan (SP) -->
<div class="sp-modal-overlay" id="spAddModalOverlay" onclick="if(event.target === this) closeAddSpModal()">
    <div class="sp-modal-card">
        <div class="sp-modal-header">
            <div style="display: flex; align-items: center; gap: 10px;">
                <span class="sp-modal-header-icon" id="spModalIconWrap">
                    <i class="fas fa-exclamation-triangle"></i>
                </span>
                <div>
                    <h3 class="sp-modal-title" id="spModalTitle">Berikan Surat Peringatan</h3>
                    <small style="color: var(--text-muted);" id="spModalSubtitle">Input pelanggaran dan rincian SP pengurus</small>
                </div>
            </div>
            <button type="button" class="btn btn-ghost btn-sm" onclick="closeAddSpModal()" title="Tutup">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form id="spAddModalForm" method="POST" action="">
            @csrf
            <div class="sp-modal-body">
                <div class="sp-modal-member-box">
                    <div>
                        <strong id="spModalMemberName" style="color: var(--text-primary); font-size: 0.96rem; display: block;">-</strong>
                        <small id="spModalMemberDept" style="color: var(--text-muted); font-size: 0.78rem;">-</small>
                    </div>
                    <span class="badge badge-sp-1" id="spModalTargetBadge">SP 1</span>
                </div>

                <div id="spModalWarningSp3" class="alert alert-danger" style="display: none; margin-bottom: 14px; font-size: 0.82rem; padding: 10px 12px;">
                    <i class="fas fa-user-times"></i>
                    <span><strong>Perhatian SP 3 (Pemecatan):</strong> Akun pengurus ini akan langsung dicap <strong>SP 3</strong> dan <strong>dinonaktifkan</strong> di database.</span>
                </div>

                <div class="form-group" style="margin-bottom: 14px;">
                    <label class="form-label" for="spModalReasonInput">
                        Pelanggaran <span style="color: var(--danger);">*</span>
                    </label>
                    <input
                        type="text"
                        id="spModalReasonInput"
                        name="sp_reason"
                        class="form-control"
                        required
                        maxlength="255"
                        placeholder="Contoh: Melanggar disiplin rapat / Tidak menjalankan tugas proker">
                    <small style="color: var(--text-muted); font-size: 0.75rem; display: block; margin-top: 4px;">
                        <i class="fas fa-eye"></i> Teks pelanggaran singkat ini adalah yang langsung ditampilkan di <strong>Dashboard Pengurus</strong>.
                    </small>
                </div>

                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label" for="spModalDetailInput">
                        Rincian Pelanggaran <span style="color: var(--danger);">*</span>
                    </label>
                    <textarea
                        id="spModalDetailInput"
                        name="sp_detail"
                        class="form-control"
                        rows="4"
                        required
                        maxlength="2000"
                        placeholder="Tuliskan kronologi atau rincian lengkap pelanggaran yang dilakukan pengurus..."></textarea>
                    <small style="color: var(--text-muted); font-size: 0.75rem; display: block; margin-top: 4px;">
                        <i class="fas fa-mouse-pointer"></i> Rincian ini hanya akan muncul saat pengurus mengklik tombol <strong>Lihat Rincian</strong> dan di halaman <strong>Notifikasi</strong>.
                    </small>
                </div>
            </div>

            <div class="sp-modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeAddSpModal()">
                    Batal
                </button>
                <button type="submit" class="btn btn-primary" id="spModalSubmitBtn">
                    <i class="fas fa-check-circle"></i> Simpan & Berikan SP
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('styles')
<style>
.sp-modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.68);
    backdrop-filter: blur(4px);
    z-index: 9999;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 16px;
}
.sp-modal-overlay.active {
    display: flex;
}
.sp-modal-card {
    background: var(--bg-surface, #1E293B);
    border: 1px solid var(--border-color, #334155);
    border-radius: var(--radius-xl, 16px);
    width: 100%;
    max-width: 520px;
    box-shadow: 0 20px 50px rgba(0, 0, 0, 0.4);
    overflow: hidden;
    animation: spModalFadeIn 0.2s ease-out;
}
@keyframes spModalFadeIn {
    from { opacity: 0; transform: scale(0.96) translateY(8px); }
    to { opacity: 1; transform: scale(1) translateY(0); }
}
.sp-modal-header {
    padding: 16px 20px;
    border-bottom: 1px solid var(--border-color, #334155);
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.sp-modal-header-icon {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    background: rgba(245, 158, 11, 0.16);
    color: #F59E0B;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
}
.sp-modal-title {
    margin: 0;
    font-size: 1rem;
    font-weight: 700;
    color: var(--text-primary);
}
.sp-modal-body {
    padding: 18px 20px;
}
.sp-modal-member-box {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 12px 14px;
    background: var(--dark-surface-2, rgba(15, 23, 42, 0.35));
    border: 1px solid var(--border-color, #334155);
    border-radius: var(--radius-md, 10px);
    margin-bottom: 14px;
}
.sp-modal-footer {
    padding: 14px 20px;
    border-top: 1px solid var(--border-color, #334155);
    background: var(--dark-surface-2, rgba(15, 23, 42, 0.25));
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}
</style>
@endpush

@push('scripts')
<script>
function openAddSpModal(btn) {
    const overlay = document.getElementById('spAddModalOverlay');
    const form = document.getElementById('spAddModalForm');
    const nameEl = document.getElementById('spModalMemberName');
    const deptEl = document.getElementById('spModalMemberDept');
    const badgeEl = document.getElementById('spModalTargetBadge');
    const titleEl = document.getElementById('spModalTitle');
    const submitBtn = document.getElementById('spModalSubmitBtn');
    const warningSp3 = document.getElementById('spModalWarningSp3');
    const reasonInput = document.getElementById('spModalReasonInput');
    const detailInput = document.getElementById('spModalDetailInput');

    const actionUrl = btn.getAttribute('data-action-url');
    const memberName = btn.getAttribute('data-member-name');
    const memberDept = btn.getAttribute('data-member-dept');
    const nextSp = parseInt(btn.getAttribute('data-next-sp') || '1', 10);

    form.setAttribute('action', actionUrl);
    nameEl.textContent = memberName;
    deptEl.textContent = memberDept;
    reasonInput.value = '';
    detailInput.value = '';

    badgeEl.className = 'badge badge-sp-' + nextSp;
    if (nextSp >= 3) {
        badgeEl.innerHTML = '<i class="fas fa-user-times"></i> SP 3 (Diberhentikan)';
        titleEl.textContent = 'Berikan SP 3 (Pemberhentian)';
        submitBtn.className = 'btn btn-danger';
        submitBtn.innerHTML = '<i class="fas fa-user-times"></i> Berikan SP 3 & Nonaktifkan';
        warningSp3.style.display = 'flex';
    } else {
        badgeEl.innerHTML = '<i class="fas fa-exclamation-triangle"></i> SP ' + nextSp;
        titleEl.textContent = 'Tambahkan Surat Peringatan (SP ' + nextSp + ')';
        submitBtn.className = 'btn btn-primary';
        submitBtn.innerHTML = '<i class="fas fa-check-circle"></i> Simpan & Berikan SP ' + nextSp;
        warningSp3.style.display = 'none';
    }

    overlay.classList.add('active');
    setTimeout(() => reasonInput.focus(), 60);
}

function closeAddSpModal() {
    const overlay = document.getElementById('spAddModalOverlay');
    if (overlay) {
        overlay.classList.remove('active');
    }
}

document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
        closeAddSpModal();
    }
});
</script>
@endpush
