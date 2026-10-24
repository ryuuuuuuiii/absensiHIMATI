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
    </div>
</div>
@endsection
