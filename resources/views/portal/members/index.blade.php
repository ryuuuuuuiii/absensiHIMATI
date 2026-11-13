@extends('layouts.portal')

@section('title', $isBphInti ? 'Data Seluruh Pengurus' : 'Anggota Dept. ' . $current->departemen)
@section('page_icon')
    <i class="fas fa-users-cog"></i>
@endsection

@section('content')
<div class="portal-page-header">
    <div>
        <h1 class="portal-page-title">
            <i class="fas fa-users-cog"></i>
            {{ $isBphInti ? 'Data Seluruh Pengurus' : 'Anggota Departemen ' . $current->departemen }}
        </h1>
        <p class="portal-page-subtitle" style="margin: 4px 0 0; font-size: 0.86rem; color: var(--text-muted);">
            @if ($isBphInti)
                Pantau profil lengkap, track record rapat, bukti kehadiran, dan kedisiplinan seluruh pengurus HIMA TI.
            @else
                Pantau profil lengkap, track record rapat, bukti kehadiran, dan kedisiplinan seluruh anggota di Departemen {{ $current->departemen }}.
            @endif
        </p>
    </div>
</div>

<!-- Banner Indikator Read-Only Khusus -->
<div class="alert alert-info mb-6" style="display: flex; align-items: flex-start; gap: 12px; background: rgba(48, 175, 255, 0.08); border-left: 4px solid var(--blue-primary); border-radius: var(--radius-md); padding: 12px 16px;">
    <i class="fas fa-shield-alt" style="font-size: 1.25rem; color: var(--blue-primary); margin-top: 2px;"></i>
    <div style="font-size: 0.84rem; line-height: 1.5; color: var(--text-primary);">
        <strong>Akses Mode Pantau (Read-Only)</strong><br>
        <span style="color: var(--text-muted);">
            Sebagai {{ $isBphInti ? 'Pengurus Inti (BPH)' : 'Koordinator Departemen ' . $current->departemen }}, Anda dapat memeriksa detail riwayat kegiatan, presensi, surat keterangan, dan lencana anggota secara menyeluruh. Sesuai hak akses, data ini bersifat <em>lihat saja</em> dan tidak dapat diedit atau dihapus.
        </span>
    </div>
</div>

<!-- Filter & Pencarian -->
<div class="filter-bar mb-6" style="background: var(--bg-surface); padding: 14px 16px; border-radius: var(--radius-md); border: 1.5px solid var(--border-color);">
    <form method="GET" action="{{ route('portal.members.index') }}" style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap; width: 100%;">
        <div style="flex: 1; min-width: 200px;">
            <div style="position: relative;">
                <input
                    type="text"
                    name="q"
                    class="form-control"
                    placeholder="Cari nama, NIM, atau jabatan..."
                    value="{{ request('q') }}"
                    style="padding-left: 36px;"
                >
                <i class="fas fa-search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: 0.85rem;"></i>
            </div>
        </div>

        @if ($isBphInti)
            <div style="min-width: 170px;">
                <select name="departemen" class="form-control" onchange="this.form.submit()">
                    <option value="">Semua Departemen</option>
                    @foreach ($departments as $dept)
                        <option value="{{ $dept }}" {{ request('departemen') === $dept ? 'selected' : '' }}>
                            Dept. {{ $dept }}
                        </option>
                    @endforeach
                </select>
            </div>
        @endif

        <div style="min-width: 160px;">
            <select name="sp" class="form-control" onchange="this.form.submit()">
                <option value="">Semua Status SP</option>
                <option value="has_sp" {{ request('sp') === 'has_sp' ? 'selected' : '' }}>Sedang Terkena SP</option>
                <option value="1" {{ request('sp') === '1' ? 'selected' : '' }}>SP 1</option>
                <option value="2" {{ request('sp') === '2' ? 'selected' : '' }}>SP 2</option>
                <option value="3" {{ request('sp') === '3' ? 'selected' : '' }}>SP 3</option>
            </select>
        </div>

        <button type="submit" class="btn btn-primary btn-sm" style="height: 38px;">
            <i class="fas fa-filter"></i> Filter
        </button>

        @if (request()->hasAny(['q', 'departemen', 'sp']))
            <a href="{{ route('portal.members.index') }}" class="btn btn-secondary btn-sm" style="height: 38px; display: inline-flex; align-items: center; gap: 6px;">
                <i class="fas fa-times"></i> Reset
            </a>
        @endif

        <div style="margin-left: auto;">
            <span class="badge badge-primary">{{ $members->total() }} Pengurus Ditemukan</span>
        </div>
    </form>
</div>

<!-- Daftar Pengurus -->
@if ($members->isEmpty())
    <div class="card">
        <div class="empty-state" style="padding: 48px 20px; text-align: center;">
            <i class="fas fa-users-slash" style="font-size: 3rem; color: var(--text-muted); margin-bottom: 12px; display: block;"></i>
            <h3 style="margin-bottom: 6px;">Tidak Ada Anggota Ditemukan</h3>
            <p style="color: var(--text-muted); max-width: 420px; margin: 0 auto 16px;">
                @if (request()->hasAny(['q', 'departemen', 'sp']))
                    Coba sesuaikan kata kunci pencarian atau reset filter untuk menampilkan semua anggota.
                @else
                    Belum ada data anggota yang terdaftar pada sistem.
                @endif
            </p>
            @if (request()->hasAny(['q', 'departemen', 'sp']))
                <a href="{{ route('portal.members.index') }}" class="btn btn-secondary btn-sm">
                    <i class="fas fa-redo"></i> Reset Filter
                </a>
            @endif
        </div>
    </div>
@else
    <!-- Tampilan Desktop: Card Grid / Table -->
    <div class="portal-member-grid">
        @foreach ($members as $m)
            @php
                $lvl = $m->level_data;
            @endphp
            <div class="portal-member-card card">
                <div class="portal-member-card-header">
                    <div class="portal-member-avatar-box">
                        @if ($m->profile_photo_url)
                            <img src="{{ $m->profile_photo_url }}" alt="{{ $m->nama_lengkap }}" class="portal-member-avatar-img">
                        @else
                            <div class="portal-member-avatar-initials">{{ $m->initials }}</div>
                        @endif
                    </div>
                    <div class="portal-member-info">
                        <h3 class="portal-member-name" title="{{ $m->nama_lengkap }}">
                            {{ $m->nama_lengkap }}
                        </h3>
                        <div class="portal-member-sub">
                            <span><i class="fas fa-id-card"></i> {{ $m->nim ?? '-' }}</span>
                            <span class="meta-dot">&bull;</span>
                            <span>{{ $m->departemen ? 'Dept. ' . $m->departemen : 'Pengurus' }}</span>
                        </div>
                    </div>
                </div>

                <div class="portal-member-badges-row">
                    <span class="member-role-pill">
                        <i class="fas fa-id-badge"></i> {{ $m->jabatan }}
                    </span>
                    @if ($m->isBphInti())
                        <span class="member-role-pill bph-pill">
                            <i class="fas fa-crown"></i> Inti
                        </span>
                    @endif

                    @if ($m->sp_level == 1)
                        <span class="badge badge-sp-1" title="Surat Peringatan Pertama">SP 1</span>
                    @elseif ($m->sp_level == 2)
                        <span class="badge badge-sp-2" title="Surat Peringatan Kedua">SP 2</span>
                    @elseif ($m->sp_level >= 3)
                        <span class="badge badge-sp-3" title="Diberhentikan">SP 3</span>
                    @else
                        <span class="badge badge-success" style="font-size: 0.7rem;"><i class="fas fa-shield-alt"></i> Disiplin Aman</span>
                    @endif
                </div>

                <!-- Level & Gamifikasi Mini Bar -->
                <div class="portal-member-tier-box">
                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 8px;">
                        <div style="display: flex; align-items: center; gap: 6px;">
                            <img src="{{ $lvl['tier_icon_url'] }}" alt="{{ $lvl['title'] }}" class="tier-minecraft-icon-sm" style="width: 18px; height: 18px; object-fit: contain;">
                            <span style="font-weight: 700; font-size: 0.78rem; color: var(--text-primary);">
                                Lv. {{ $lvl['level'] }} &bull; {{ $lvl['title'] }}
                            </span>
                        </div>
                        <span style="font-size: 0.74rem; font-weight: 600; color: var(--text-muted);">
                            {{ number_format($lvl['exp']) }} EXP
                        </span>
                    </div>
                    <div class="level-bar-track" style="margin-top: 6px; height: 6px;">
                        <div class="level-bar-fill" style="width: {{ $lvl['percentage'] }}%;"></div>
                    </div>
                </div>

                <div class="portal-member-card-footer">
                    <div class="portal-member-status-text">
                        @if ($m->last_login_at)
                            <small title="{{ $m->last_login_at->format('d M Y H:i') }}">
                                <i class="fas fa-clock"></i> Aktif {{ $m->last_login_at->diffForHumans() }}
                            </small>
                        @else
                            <small><i class="far fa-clock"></i> Belum pernah login</small>
                        @endif
                    </div>
                    <a href="{{ route('portal.members.show', $m->id) }}" class="btn btn-primary btn-sm btn-track-record">
                        <i class="fas fa-clipboard-list"></i>
                        <span>Track Record</span>
                        <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>
        @endforeach
    </div>

    <div class="mt-6">
        {{ $members->links() }}
    </div>
@endif

@push('styles')
<style>
.portal-member-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: var(--space-4);
}

.portal-member-card {
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    padding: 16px;
    background: var(--bg-surface);
    border: 1.5px solid var(--border-color);
    border-radius: var(--radius-md);
    transition: transform 0.15s ease, box-shadow 0.15s ease, border-color 0.15s ease;
}

.portal-member-card:hover {
    transform: translateY(-2px);
    border-color: var(--blue-primary);
    box-shadow: 0 6px 16px rgba(0, 0, 0, 0.15);
}

.portal-member-card-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 12px;
}

.portal-member-avatar-box {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    overflow: hidden;
    flex-shrink: 0;
    border: 2px solid var(--blue-primary);
    background: var(--bg-surface-subtle);
    display: flex;
    align-items: center;
    justify-content: center;
}

.portal-member-avatar-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.portal-member-avatar-initials {
    font-size: 1rem;
    font-weight: 800;
    color: var(--blue-primary);
}

.portal-member-info {
    flex: 1;
    min-width: 0;
}

.portal-member-name {
    margin: 0 0 2px;
    font-size: 0.98rem;
    font-weight: 700;
    color: var(--text-primary);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.portal-member-sub {
    font-size: 0.78rem;
    color: var(--text-muted);
    display: flex;
    align-items: center;
    gap: 6px;
}

.portal-member-badges-row {
    display: flex;
    align-items: center;
    gap: 6px;
    flex-wrap: wrap;
    margin-bottom: 12px;
}

.member-role-pill {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 8px;
    border-radius: 6px;
    font-size: 0.74rem;
    font-weight: 600;
    background: var(--bg-surface-subtle);
    color: var(--text-primary);
    border: 1px solid var(--border-color);
}

.member-role-pill.bph-pill {
    background: rgba(245, 158, 11, 0.12);
    color: #F59E0B;
    border-color: rgba(245, 158, 11, 0.3);
}

.portal-member-tier-box {
    background: var(--bg-surface-subtle);
    padding: 10px 12px;
    border-radius: 8px;
    margin-bottom: 14px;
    border: 1px solid var(--border-color);
}

.portal-member-card-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    padding-top: 10px;
    border-top: 1px solid var(--border-color);
}

.portal-member-status-text {
    font-size: 0.75rem;
    color: var(--text-muted);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.btn-track-record {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 0.8rem;
    padding: 6px 12px;
    font-weight: 600;
    text-decoration: none;
    flex-shrink: 0;
}

@media (max-width: 640px) {
    .portal-member-grid {
        grid-template-columns: 1fr;
    }
}
</style>
@endpush
@endsection