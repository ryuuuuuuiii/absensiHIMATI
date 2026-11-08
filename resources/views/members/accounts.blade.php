@extends('layouts.app')

@section('title', 'Akun Pengurus')
@section('page_icon')
    <i class="fas fa-user-shield"></i>
@endsection

@section('content')
<div class="card mb-6" style="border-left: 4px solid var(--primary);">
    <div class="d-flex align-center justify-between flex-wrap gap-4">
        <div>
            <h3 style="color: var(--primary); margin-bottom: var(--space-1);">
                <i class="fas fa-key"></i> Akun Login Portal Pengurus
            </h3>
            <p style="font-size: var(--font-sm); color: var(--text-muted); margin: 0;">
                Setiap pengurus dapat login ke Portal Pengurus menggunakan email dan password untuk melihat track record, mengunduh QR, mengajukan absen, dan mengelola profil.
            </p>
        </div>
        <form action="{{ route('members.accounts.generate') }}" method="POST" style="margin: 0;">
            @csrf
            <button type="submit" class="btn btn-primary" onclick="return confirm('Buat akun untuk semua pengurus yang belum memiliki akun?')">
                <i class="fas fa-user-plus"></i> Buat Akun yang Belum Ada
            </button>
        </form>
    </div>

    <div class="alert alert-info mt-4" style="margin-bottom: 0;">
        <i class="fas fa-info-circle"></i>
        <span>
            Password awal untuk akun baru / reset: <strong>{{ $defaultPassword }}</strong>.
            Minta pengurus segera mengganti password melalui menu <strong>Profil Saya</strong> di Portal Pengurus.
        </span>
    </div>
</div>

<div class="filter-bar">
    <form method="GET" action="{{ route('members.accounts') }}" class="search-input-wrapper">
        <i class="fas fa-search"></i>
        <input type="text" name="q" class="form-control" placeholder="Cari nama, email, atau departemen..." value="{{ $search }}">
    </form>
    <a href="{{ route('members.index') }}" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Kembali ke Anggota
    </a>
    <span class="badge badge-warning" style="margin-left: auto;">
        <i class="fas fa-user-slash"></i> {{ $withoutAccount }} belum punya akun
    </span>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-users"></i> Daftar Akun Pengurus</h3>
        <span class="badge badge-primary">{{ $members->total() }} pengurus</span>
    </div>

    @if ($members->isEmpty())
        <div class="empty-state">
            <i class="fas fa-user-slash"></i>
            <h3>Tidak Ada Data</h3>
            <p>Tidak ditemukan pengurus yang sesuai dengan pencarian.</p>
        </div>
    @else
        <div class="table-wrapper">
            <table class="table">
                <thead>
                    <tr>
                        <th>Pengurus</th>
                        <th>Email Login</th>
                        <th>Status Akun</th>
                        <th>Login Terakhir</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($members as $member)
                        <tr>
                            <td>
                                <div class="d-flex align-center gap-3">
                                    <div class="header-user-avatar" style="width: 38px; height: 38px; font-size: var(--font-sm); overflow: hidden; padding: 0;">
                                        @if ($member->profile_photo_url)
                                            <img src="{{ $member->profile_photo_url }}" alt="{{ $member->nama_lengkap }}" style="width: 100%; height: 100%; object-fit: cover;">
                                        @else
                                            {{ $member->initials }}
                                        @endif
                                    </div>
                                    <div>
                                        <div style="font-weight: 600;">
                                            {{ $member->nama_lengkap }}
                                            @if ($member->sp_level == 1)
                                                <span class="badge badge-sp-1" style="font-size: 10px; margin-left: 4px;">SP 1</span>
                                            @elseif ($member->sp_level == 2)
                                                <span class="badge badge-sp-2" style="font-size: 10px; margin-left: 4px;">SP 2</span>
                                            @elseif ($member->sp_level >= 3)
                                                <span class="badge badge-sp-3" style="font-size: 10px; margin-left: 4px;">SP 3 (Diberhentikan)</span>
                                            @endif
                                        </div>
                                        <small style="color: var(--text-muted);">{{ $member->departemen }} — {{ $member->jabatan }}</small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                @if ($member->email)
                                    <span style="font-weight: 500;">{{ $member->email }}</span>
                                @else
                                    <span class="badge badge-danger">Email kosong</span>
                                @endif
                            </td>
                            <td>
                                @if ($member->isDismissed())
                                    <span class="badge badge-sp-3"><i class="fas fa-user-times"></i> Nonaktif (SP 3)</span>
                                @elseif ($member->hasAccount())
                                    <span class="badge badge-success"><i class="fas fa-check"></i> Aktif</span>
                                @else
                                    <span class="badge badge-danger"><i class="fas fa-ban"></i> Belum ada</span>
                                @endif
                            </td>
                            <td>
                                <small style="color: var(--text-muted);">
                                    {{ $member->last_login_at ? $member->last_login_at->locale('id')->translatedFormat('d M Y, H:i') : '-' }}
                                </small>
                            </td>
                            <td>
                                <div style="display: flex; gap: var(--space-2); flex-wrap: wrap;">
                                    @if ($member->email)
                                        <form action="{{ route('members.accounts.reset', $member->id) }}" method="POST" style="margin: 0;" onsubmit="return confirm('Reset password akun {{ addslashes($member->nama_lengkap) }} ke password default ({{ config('himati.default_member_password') }})?')">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-secondary" title="Reset Password ke Default ({{ config('himati.default_member_password') }})">
                                                <i class="fas fa-key"></i> Reset
                                            </button>
                                        </form>
                                    @endif
                                    @if ($member->hasAccount())
                                        <form action="{{ route('members.accounts.disable', $member->id) }}" method="POST" style="margin: 0;" onsubmit="return confirm('Nonaktifkan akun {{ $member->nama_lengkap }}?')">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-danger" title="Nonaktifkan Akun">
                                                <i class="fas fa-ban"></i> Nonaktifkan
                                            </button>
                                        </form>
                                    @endif
                                    <a href="{{ route('members.edit', $member->id) }}" class="btn btn-sm btn-ghost" title="Edit Anggota">
                                        <i class="fas fa-edit"></i>
                                    </a>
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
