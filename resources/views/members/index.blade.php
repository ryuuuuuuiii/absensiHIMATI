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
    </form>
    
    <select class="form-control" style="max-width: 200px;" onchange="location.href='{{ route('members.index') }}?dept=' + this.value + '{{ $search ? '&q=' . urlencode($search) : '' }}'">
        <option value="">Semua Departemen</option>
        @foreach ($departments as $key => $label)
            <option value="{{ $key }}" {{ $dept === $key ? 'selected' : '' }}>{{ $label }}</option>
        @endforeach
    </select>
    
    <a href="{{ route('members.create') }}" class="btn btn-primary">
        <i class="fas fa-user-plus"></i> Tambah Anggota
    </a>
    
    <a href="{{ route('members.generate_qr') }}" class="btn btn-secondary">
        <i class="fas fa-qrcode"></i> Generate QR
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
                        <th>QR Code</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($members as $m)
                    <tr>
                        <td>
                            <div class="d-flex align-center gap-3">
                                <div class="header-user-avatar" style="width: 38px; height: 38px; font-size: var(--font-sm);">
                                    {{ $m->initials }}
                                </div>
                                <div>
                                    <div style="font-weight: 600;">
                                        {{ $m->nama_lengkap }}
                                        @if($m->is_bph)
                                            <span class="badge badge-warning" style="font-size: 10px; margin-left: 4px;">BPH</span>
                                        @endif
                                    </div>
                                    <small style="color: var(--text-muted);">{{ $m->email ?? 'Tanpa email' }}</small>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="badge badge-dept">{{ $m->departemen }}</span>
                        </td>
                        <td>{{ $m->jabatan }}</td>
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
                            <div style="display: flex; gap: var(--space-2);">
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
@endsection
