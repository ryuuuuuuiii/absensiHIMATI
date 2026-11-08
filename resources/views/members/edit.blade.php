@extends('layouts.app')

@section('title', 'Edit Anggota')

@section('page_icon')
    <i class="fas fa-user-edit"></i>
@endsection

@section('content')
<div style="max-width: 600px; margin: 0 auto;">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-edit"></i> Edit Data Anggota</h3>
            <a href="{{ route('members.index') }}" class="btn btn-ghost btn-sm">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
        </div>

        <form action="{{ route('members.update', $member->id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="form-group">
                <label class="form-label" for="nama_lengkap">
                    Nama Lengkap <span style="color: var(--danger);">*</span>
                </label>
                <input type="text" id="nama_lengkap" name="nama_lengkap" class="form-control" required value="{{ old('nama_lengkap', $member->nama_lengkap) }}">
            </div>

            <div class="form-group">
                <label class="form-label" for="departemen">
                    Departemen <span style="color: var(--danger);">*</span>
                </label>
                <select id="departemen" name="departemen" class="form-control" required>
                    @foreach($departments as $key => $label)
                        <option value="{{ $key }}" {{ old('departemen', $member->departemen) === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label class="form-label" for="jabatan">
                    Jabatan <span style="color: var(--danger);">*</span>
                </label>
                <input type="text" id="jabatan" name="jabatan" class="form-control" required value="{{ old('jabatan', $member->jabatan) }}">
            </div>

            <div class="form-group">
                <label class="form-label" for="email">Email</label>
                <input type="email" id="email" name="email" class="form-control" value="{{ old('email', $member->email) }}">
                <small style="color: var(--text-muted); display: block; margin-top: 4px;">
                    Email ini menjadi username login Portal Pengurus.
                </small>
            </div>

            <div class="form-group" style="padding: var(--space-4); border: 1px solid var(--dark-border); border-radius: var(--radius-md); background: var(--dark-surface-2);">
                <div class="d-flex align-center justify-between flex-wrap gap-2" style="margin-bottom: var(--space-3);">
                    <strong style="color: var(--text-primary);"><i class="fas fa-user-shield"></i> Akun Login Portal</strong>
                    @if ($member->isDismissed())
                        <span class="badge badge-danger">Nonaktif (SP 3)</span>
                    @elseif ($member->hasAccount())
                        <span class="badge badge-success">Aktif</span>
                    @else
                        <span class="badge badge-danger">Belum Ada Akun</span>
                    @endif
                </div>

                <label class="form-label" for="password">Password Baru</label>
                <input type="password" id="password" name="password" class="form-control" placeholder="Kosongkan jika tidak diubah" autocomplete="new-password">
                <small style="color: var(--text-muted); display: block; margin-top: 4px;">
                    Minimal 8 karakter. Login terakhir:
                    {{ $member->last_login_at ? $member->last_login_at->locale('id')->translatedFormat('d M Y, H:i') : 'belum pernah' }}.
                </small>

                @if ($member->email)
                    <div style="margin-top: var(--space-3); padding-top: var(--space-3); border-top: 1px dashed var(--dark-border); display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px;">
                        <span style="font-size: var(--font-xs); color: var(--text-muted);">
                            Pengurus lupa password? Reset ke default: <code style="color: var(--warning); background: var(--dark-surface-1); padding: 2px 6px; border-radius: var(--radius-sm);">{{ config('himati.default_member_password') }}</code>
                        </span>
                        <button type="submit" form="reset-password-form" class="btn btn-sm btn-secondary" style="font-size: 11px;" title="Reset password pengurus ke default">
                            <i class="fas fa-key"></i> Reset ke Password Default
                        </button>
                    </div>

                    <label style="display: flex; align-items: center; gap: 8px; margin-top: var(--space-2); cursor: pointer; font-size: var(--font-xs); color: var(--text-secondary);">
                        <input type="checkbox" name="reset_password_to_default" value="1">
                        <span>Atau centang opsi ini untuk mereset password ke default saat menekan tombol Perbarui Data</span>
                    </label>
                @endif
            </div>

            <div class="form-group" style="padding: var(--space-4); border: 1px solid var(--dark-border); border-radius: var(--radius-md); background: var(--dark-surface-2);">
                <div class="d-flex align-center justify-between flex-wrap gap-2" style="margin-bottom: var(--space-3);">
                    <strong style="color: var(--text-primary);"><i class="fas fa-exclamation-triangle text-warning"></i> Status Surat Peringatan (SP)</strong>
                    @if ($member->sp_level == 1)
                        <span class="badge badge-sp-1"><i class="fas fa-exclamation-triangle"></i> SP 1</span>
                    @elseif ($member->sp_level == 2)
                        <span class="badge badge-sp-2"><i class="fas fa-exclamation-circle"></i> SP 2</span>
                    @elseif ($member->sp_level >= 3)
                        <span class="badge badge-sp-3"><i class="fas fa-user-times"></i> SP 3 (Diberhentikan)</span>
                    @else
                        <span class="badge badge-success"><i class="fas fa-shield-alt"></i> Aman (Tanpa SP)</span>
                    @endif
                </div>

                <div class="form-group" style="margin-bottom: var(--space-3);">
                    <label class="form-label" for="sp_level">Tingkat SP</label>
                    <select id="sp_level" name="sp_level" class="form-control">
                        <option value="0" {{ (int) old('sp_level', $member->sp_level) === 0 ? 'selected' : '' }}>Aman — Tanpa Surat Peringatan (Aktif)</option>
                        <option value="1" {{ (int) old('sp_level', $member->sp_level) === 1 ? 'selected' : '' }}>SP 1 — Surat Peringatan Pertama</option>
                        <option value="2" {{ (int) old('sp_level', $member->sp_level) === 2 ? 'selected' : '' }}>SP 2 — Surat Peringatan Kedua</option>
                        <option value="3" {{ (int) old('sp_level', $member->sp_level) >= 3 ? 'selected' : '' }}>SP 3 — Pemberhentian & Nonaktifkan Akun</option>
                    </select>
                </div>

                <div class="form-group" style="margin-bottom: var(--space-3);">
                    <label class="form-label" for="sp_reason">Pelanggaran (Tampil di Dashboard Pengurus)</label>
                    <input type="text" id="sp_reason" name="sp_reason" class="form-control" placeholder="Contoh: Melanggar disiplin rapat / Tidak menjalankan tugas proker" value="{{ old('sp_reason', $member->sp_reason) }}">
                </div>

                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label" for="sp_detail">Rincian Pelanggaran (Tampil saat Diklik & di Notifikasi)</label>
                    <textarea id="sp_detail" name="sp_detail" class="form-control" rows="3" placeholder="Tuliskan rincian atau kronologi pelanggaran yang dilakukan pengurus...">{{ old('sp_detail', $member->sp_detail) }}</textarea>
                    <small style="color: var(--text-muted); display: block; margin-top: 4px;">
                        @if ($member->sp_level > 0)
                            Sumber SP saat ini: <strong>{{ $member->sp_source_label }}</strong>.
                        @endif
                        Alpha berturut-turut saat ini: <strong>{{ $member->consecutive_alpha_count }}/3</strong>. Memilih <strong>Aman</strong> akan mencabut SP dan mengaktifkan kembali akun.
                    </small>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Kode QR Terdaftar</label>
                <input type="text" class="form-control" value="{{ $member->qr_code }}" readonly style="background: var(--dark-surface-2); font-family: monospace;">
            </div>

            <div class="form-group">
                <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; color: var(--text-primary);">
                    <input type="checkbox" name="is_bph" value="1" {{ old('is_bph', $member->is_bph) ? 'checked' : '' }}>
                    <span>Tandai sebagai Pengurus Inti / BPH</span>
                </label>
            </div>

            <div style="display: flex; gap: var(--space-3); margin-top: var(--space-6);">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Perbarui Data
                </button>
                <a href="{{ route('members.index') }}" class="btn btn-secondary">Batal</a>
            </div>
        </form>

        @if ($member->email)
            <form id="reset-password-form" action="{{ route('members.accounts.reset', $member->id) }}" method="POST" style="display: none;" onsubmit="return confirm('Reset password akun {{ addslashes($member->nama_lengkap) }} ke password default ({{ config('himati.default_member_password') }})?')">
                @csrf
            </form>
        @endif
    </div>
</div>
@endsection
