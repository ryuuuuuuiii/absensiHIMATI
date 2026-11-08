@extends('layouts.app')

@section('title', 'Pengumuman & Notifikasi Pesan')

@section('page_icon')
    <i class="fas fa-bullhorn"></i>
@endsection

@push('styles')
<style>
.announcement-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: var(--space-4);
    margin-bottom: var(--space-6);
}

.announcement-stat-card {
    background: var(--bg-surface);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-xl);
    padding: var(--space-4);
    display: flex;
    align-items: center;
    gap: var(--space-3);
    box-shadow: var(--shadow-sm);
    transition: transform var(--transition-fast), box-shadow var(--transition-fast);
}

.announcement-stat-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}

.announcement-stat-icon {
    width: 48px;
    height: 48px;
    border-radius: var(--radius-lg);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.35rem;
    flex-shrink: 0;
}

.announcement-stat-icon.blue { background: rgba(37, 99, 235, 0.12); color: var(--blue-primary); }
.announcement-stat-icon.green { background: rgba(16, 185, 129, 0.14); color: var(--success); }
.announcement-stat-icon.orange { background: rgba(249, 115, 22, 0.14); color: var(--orange-primary); }

.announcement-stat-number {
    font-size: 1.45rem;
    font-weight: 800;
    color: var(--text-primary);
    line-height: 1.1;
}

.announcement-stat-label {
    font-size: 0.8rem;
    color: var(--text-muted);
    font-weight: 600;
    margin-top: 2px;
}

/* Radio Pill Selector for Type & Target */
.pill-options-group {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.pill-option {
    cursor: pointer;
    margin: 0;
}

.pill-option input[type="radio"] {
    position: absolute;
    opacity: 0;
    pointer-events: none;
}

.pill-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 14px;
    border-radius: var(--radius-full);
    font-size: 0.84rem;
    font-weight: 600;
    background: var(--bg-surface-subtle);
    border: 1.5px solid var(--border-color);
    color: var(--text-secondary);
    transition: all 0.2s ease;
}

.pill-option input[type="radio"]:checked + .pill-badge {
    background: var(--blue-primary);
    border-color: var(--blue-primary);
    color: #FFFFFF;
    box-shadow: 0 2px 8px rgba(37, 99, 235, 0.3);
}

.pill-option.tipe-mendesak input[type="radio"]:checked + .pill-badge {
    background: var(--danger);
    border-color: var(--danger);
    box-shadow: 0 2px 8px rgba(239, 68, 68, 0.3);
}

.pill-option.tipe-penting input[type="radio"]:checked + .pill-badge {
    background: var(--orange-primary);
    border-color: var(--orange-primary);
    box-shadow: 0 2px 8px rgba(249, 115, 22, 0.3);
}

/* Announcement Detail / Edit Modal */
.announcement-modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.65);
    backdrop-filter: blur(4px);
    z-index: 9999;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 16px;
}

.announcement-modal-overlay.active {
    display: flex;
}

.announcement-modal {
    background: var(--bg-surface);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-2xl);
    width: 100%;
    max-width: 640px;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: var(--shadow-xl);
    animation: modalIn 0.25s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes modalIn {
    from { opacity: 0; transform: scale(0.96) translateY(8px); }
    to { opacity: 1; transform: scale(1) translateY(0); }
}

.announcement-modal-header {
    padding: 18px 22px;
    border-bottom: 1px solid var(--border-color);
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.announcement-modal-body {
    padding: 22px;
}

.announcement-modal-footer {
    padding: 14px 22px;
    border-top: 1px solid var(--border-color);
    background: var(--bg-surface-subtle);
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    border-bottom-left-radius: var(--radius-2xl);
    border-bottom-right-radius: var(--radius-2xl);
}
</style>
@endpush

@section('content')
<!-- Stat Cards -->
<div class="announcement-stats-grid">
    <div class="announcement-stat-card">
        <div class="announcement-stat-icon blue">
            <i class="fas fa-bullhorn"></i>
        </div>
        <div>
            <div class="announcement-stat-number">{{ $stats['total'] }}</div>
            <div class="announcement-stat-label">Total Pengumuman & Pesan</div>
        </div>
    </div>
    <div class="announcement-stat-card">
        <div class="announcement-stat-icon green">
            <i class="fas fa-check-circle"></i>
        </div>
        <div>
            <div class="announcement-stat-number">{{ $stats['active'] }}</div>
            <div class="announcement-stat-label">Sedang Aktif Tampil</div>
        </div>
    </div>
    <div class="announcement-stat-card">
        <div class="announcement-stat-icon orange">
            <i class="fas fa-thumbtack"></i>
        </div>
        <div>
            <div class="announcement-stat-number">{{ $stats['pinned'] }}</div>
            <div class="announcement-stat-label">Disematkan di Puncak (Pin)</div>
        </div>
    </div>
</div>

<div class="row" style="display: grid; grid-template-columns: 1fr; gap: var(--space-6);">
    <!-- Card Form Buat Pengumuman -->
    <div class="card">
        <div class="card-header" style="border-bottom: 1px solid var(--border-color); padding-bottom: var(--space-4); margin-bottom: var(--space-5);">
            <div style="display: flex; align-items: center; gap: 10px;">
                <div style="width: 36px; height: 36px; border-radius: var(--radius-md); background: rgba(37, 99, 235, 0.12); color: var(--blue-primary); display: flex; align-items: center; justify-content: center; font-size: 1.1rem;">
                    <i class="fas fa-paper-plane"></i>
                </div>
                <div>
                    <h3 style="margin: 0; font-size: 1.15rem; font-weight: 700; color: var(--text-primary);">Buat Pengumuman & Notifikasi Pesan Baru</h3>
                    <p style="margin: 2px 0 0; font-size: 0.84rem; color: var(--text-muted);">Pesan ini akan langsung muncul di halaman utama portal dan lonceng notifikasi pengurus tujuan.</p>
                </div>
            </div>
        </div>

        <form action="{{ route('announcements.store') }}" method="POST" id="formAnnouncement">
            @csrf

            <!-- Baris 1: Judul -->
            <div class="form-group mb-4">
                <label class="form-label" for="judul">
                    Judul Pengumuman / Pesan <span class="required">*</span>
                </label>
                <input type="text"
                       name="judul"
                       id="judul"
                       class="form-control"
                       placeholder="Contoh: Pengingat Rapat Pleno Evaluasi Semester Ganjil"
                       value="{{ old('judul') }}"
                       required>
            </div>

            <!-- Baris 2: Prioritas & Target Penerima -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: var(--space-4); margin-bottom: var(--space-4);">
                <!-- Prioritas / Tipe -->
                <div class="form-group mb-0">
                    <label class="form-label">
                        Prioritas / Kategori <span class="required">*</span>
                    </label>
                    <div class="pill-options-group">
                        <label class="pill-option">
                            <input type="radio" name="tipe" value="info" {{ old('tipe', 'info') === 'info' ? 'checked' : '' }}>
                            <span class="pill-badge">
                                <i class="fas fa-info-circle" style="color: #60a5fa;"></i> Informasi Biasa
                            </span>
                        </label>
                        <label class="pill-option tipe-penting">
                            <input type="radio" name="tipe" value="penting" {{ old('tipe') === 'penting' ? 'checked' : '' }}>
                            <span class="pill-badge">
                                <i class="fas fa-bell" style="color: #fb923c;"></i> Penting
                            </span>
                        </label>
                        <label class="pill-option tipe-mendesak">
                            <input type="radio" name="tipe" value="mendesak" {{ old('tipe') === 'mendesak' ? 'checked' : '' }}>
                            <span class="pill-badge">
                                <i class="fas fa-exclamation-triangle" style="color: #f87171;"></i> Mendesak / Urgent
                            </span>
                        </label>
                    </div>
                </div>

                <!-- Target Penerima -->
                <div class="form-group mb-0">
                    <label class="form-label">
                        Target Penerima Pesan <span class="required">*</span>
                    </label>
                    <div class="pill-options-group">
                        <label class="pill-option">
                            <input type="radio" name="target_type" value="all" onchange="toggleTargetFields()" {{ old('target_type', 'all') === 'all' ? 'checked' : '' }}>
                            <span class="pill-badge">
                                <i class="fas fa-users"></i> Semua Pengurus
                            </span>
                        </label>
                        <label class="pill-option">
                            <input type="radio" name="target_type" value="departemen" onchange="toggleTargetFields()" {{ old('target_type') === 'departemen' ? 'checked' : '' }}>
                            <span class="pill-badge">
                                <i class="fas fa-building"></i> Per Departemen
                            </span>
                        </label>
                        <label class="pill-option">
                            <input type="radio" name="target_type" value="personal" onchange="toggleTargetFields()" {{ old('target_type') === 'personal' ? 'checked' : '' }}>
                            <span class="pill-badge">
                                <i class="fas fa-user"></i> Anggota Tertentu
                            </span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Field Tambahan Dinamis Target -->
            <div id="targetDepartemenWrapper" class="form-group mb-4" style="display: {{ old('target_type') === 'departemen' ? 'block' : 'none' }};">
                <label class="form-label" for="target_departemen">
                    Pilih Departemen Tujuan <span class="required">*</span>
                </label>
                <select name="target_departemen" id="target_departemen" class="form-control">
                    <option value="">-- Pilih Departemen / Lingkup --</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept }}" {{ old('target_departemen') === $dept ? 'selected' : '' }}>
                            {{ $dept === 'BPH' ? 'BPH (Seluruh BPH)' : 'Departemen ' . $dept }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div id="targetPersonalWrapper" class="form-group mb-4" style="display: {{ old('target_type') === 'personal' ? 'block' : 'none' }};">
                <label class="form-label" for="target_user_id">
                    Pilih Pengurus Penerima Pesan <span class="required">*</span>
                </label>
                <select name="target_user_id" id="target_user_id" class="form-control">
                    <option value="">-- Pilih Nama Pengurus --</option>
                    @foreach($members as $m)
                        <option value="{{ $m->id }}" {{ (int) old('target_user_id') === $m->id ? 'selected' : '' }}>
                            {{ $m->nama_lengkap }} ({{ $m->departemen }} — {{ $m->jabatan }})
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Isi Pesan -->
            <div class="form-group mb-4">
                <label class="form-label" for="konten">
                    Isi Pengumuman / Pesan <span class="required">*</span>
                </label>
                <textarea name="konten"
                          id="konten"
                          rows="4"
                          class="form-control"
                          placeholder="Tuliskan isi pengumuman atau instruksi pesan lengkap di sini..."
                          required>{{ old('konten') }}</textarea>
            </div>

            <!-- Opsi Tampilan & Batas Waktu -->
            <div style="display: flex; flex-wrap: wrap; gap: var(--space-4); align-items: center; justify-content: space-between; background: var(--bg-surface-subtle); padding: 12px 16px; border-radius: var(--radius-lg); margin-bottom: var(--space-5);">
                <div style="display: flex; flex-wrap: wrap; gap: 18px; align-items: center;">
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 0.88rem; font-weight: 600; color: var(--text-primary); margin: 0;">
                        <input type="checkbox" name="is_banner" value="1" {{ old('is_banner', '1') ? 'checked' : '' }}>
                        <span>Tampilkan sebagai Banner di Atas Dashboard</span>
                    </label>

                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 0.88rem; font-weight: 600; color: var(--text-primary); margin: 0;">
                        <input type="checkbox" name="pinned" value="1" {{ old('pinned') ? 'checked' : '' }}>
                        <span>Sematkan di Paling Atas (Pin)</span>
                    </label>
                </div>

                <div style="display: flex; align-items: center; gap: 10px;">
                    <label for="expires_at" style="font-size: 0.82rem; color: var(--text-muted); font-weight: 600; margin: 0;">
                        Kedaluwarsa (Opsional):
                    </label>
                    <input type="datetime-local"
                           name="expires_at"
                           id="expires_at"
                           class="form-control"
                           style="width: auto; padding: 6px 12px; font-size: 0.82rem;"
                           value="{{ old('expires_at') }}">
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end;">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-paper-plane"></i> Publikasikan Pengumuman
                </button>
            </div>
        </form>
    </div>

    <!-- Card Riwayat Pengumuman -->
    <div class="card">
        <div class="card-header" style="border-bottom: 1px solid var(--border-color); padding-bottom: var(--space-4); margin-bottom: var(--space-5); display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: var(--space-3);">
            <div>
                <h3 style="margin: 0; font-size: 1.15rem; font-weight: 700; color: var(--text-primary);">Riwayat Pengumuman & Notifikasi</h3>
                <p style="margin: 2px 0 0; font-size: 0.84rem; color: var(--text-muted);">Daftar seluruh pesan siaran dan notifikasi yang telah dibuat oleh admin.</p>
            </div>

            <!-- Filter & Search Bar -->
            <form action="{{ route('announcements.index') }}" method="GET" style="display: flex; flex-wrap: wrap; gap: 8px;">
                <input type="text"
                       name="search"
                       class="form-control"
                       placeholder="Cari pengumuman..."
                       value="{{ $search }}"
                       style="width: 180px; padding: 7px 12px; font-size: 0.84rem;">

                <select name="tipe" class="form-control" style="width: auto; padding: 7px 12px; font-size: 0.84rem;" onchange="this.form.submit()">
                    <option value="">Semua Kategori</option>
                    <option value="info" {{ $filterTipe === 'info' ? 'selected' : '' }}>Informasi</option>
                    <option value="penting" {{ $filterTipe === 'penting' ? 'selected' : '' }}>Penting</option>
                    <option value="mendesak" {{ $filterTipe === 'mendesak' ? 'selected' : '' }}>Mendesak</option>
                </select>

                <select name="status" class="form-control" style="width: auto; padding: 7px 12px; font-size: 0.84rem;" onchange="this.form.submit()">
                    <option value="">Semua Status</option>
                    <option value="active" {{ $filterStatus === 'active' ? 'selected' : '' }}>Aktif</option>
                    <option value="inactive" {{ $filterStatus === 'inactive' ? 'selected' : '' }}>Nonaktif</option>
                </select>

                @if($search || $filterTipe || $filterStatus)
                    <a href="{{ route('announcements.index') }}" class="btn btn-secondary btn-sm" title="Reset Filter">
                        <i class="fas fa-times"></i>
                    </a>
                @endif
            </form>
        </div>

        @if($announcements->count() > 0)
            <div class="table-wrapper" style="overflow-x: auto;">
                <table class="table" style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr>
                            <th style="width: 32%;">Judul & Prioritas</th>
                            <th style="width: 18%;">Target Penerima</th>
                            <th style="width: 12%;">Tampilan</th>
                            <th style="width: 12%;">Status</th>
                            <th style="width: 12%;">Dibaca</th>
                            <th style="width: 14%; text-align: right;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($announcements as $item)
                            <tr>
                                <td>
                                    <div style="display: flex; align-items: flex-start; gap: 8px;">
                                        @if($item->pinned)
                                            <span title="Disematkan di Puncak (Pin)" style="color: var(--orange-primary); font-size: 0.85rem; margin-top: 3px;">
                                                <i class="fas fa-thumbtack"></i>
                                            </span>
                                        @endif
                                        <div>
                                            <div style="font-weight: 700; color: var(--text-primary); font-size: 0.92rem; line-height: 1.35;">
                                                {{ $item->judul }}
                                            </div>
                                            <div style="margin-top: 4px; display: flex; align-items: center; gap: 6px;">
                                                <span class="badge {{ $item->badge_class }}" style="font-size: 0.7rem; padding: 2px 7px;">
                                                    <i class="fas {{ $item->icon }}"></i> {{ $item->tipe_label }}
                                                </span>
                                                <span style="font-size: 0.75rem; color: var(--text-muted);">
                                                    {{ $item->created_at->diffForHumans() }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    @if($item->target_type === 'all')
                                        <span class="badge badge-info" style="font-size: 0.74rem;">
                                            <i class="fas fa-users"></i> Semua Pengurus
                                        </span>
                                    @elseif($item->target_type === 'departemen')
                                        <span class="badge badge-dept" style="font-size: 0.74rem;">
                                            <i class="fas fa-building"></i> Dept. {{ $item->target_departemen }}
                                        </span>
                                    @elseif($item->target_type === 'personal')
                                        <span class="badge badge-primary" style="font-size: 0.74rem;">
                                            <i class="fas fa-user"></i> {{ $item->targetUser?->nama_lengkap ?? 'Anggota #' . $item->target_user_id }}
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    @if($item->is_banner)
                                        <span class="badge badge-success" style="font-size: 0.74rem;">
                                            <i class="fas fa-desktop"></i> Banner + Bell
                                        </span>
                                    @else
                                        <span class="badge badge-secondary" style="font-size: 0.74rem;">
                                            <i class="fas fa-bell"></i> Bell Saja
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    <form action="{{ route('announcements.toggle_status', $item->id) }}" method="POST" style="display: inline;">
                                        @csrf
                                        <button type="submit"
                                                class="badge {{ $item->is_active ? 'badge-success' : 'badge-secondary' }}"
                                                style="border: none; cursor: pointer; font-size: 0.74rem; padding: 4px 8px;"
                                                title="Klik untuk {{ $item->is_active ? 'Nonaktifkan' : 'Aktifkan' }}">
                                            <i class="fas {{ $item->is_active ? 'fa-toggle-on' : 'fa-toggle-off' }}"></i>
                                            {{ $item->is_active ? 'Aktif' : 'Nonaktif' }}
                                        </button>
                                    </form>
                                </td>
                                <td>
                                    @php
                                        $readCount = $item->reads()->whereNotNull('read_at')->count();
                                    @endphp
                                    <span style="font-size: 0.8rem; font-weight: 600; color: var(--text-secondary);">
                                        <i class="fas fa-eye" style="color: var(--blue-primary); margin-right: 2px;"></i>
                                        {{ $readCount }} dibuka
                                    </span>
                                </td>
                                <td style="text-align: right;">
                                    <div style="display: inline-flex; gap: 5px;">
                                        <!-- Pin / Unpin Button -->
                                        <form action="{{ route('announcements.toggle_pin', $item->id) }}" method="POST" style="display: inline;">
                                            @csrf
                                            <button type="submit"
                                                    class="btn btn-secondary btn-sm"
                                                    style="padding: 5px 8px; {{ $item->pinned ? 'color: var(--orange-primary);' : '' }}"
                                                    title="{{ $item->pinned ? 'Lepas Pin' : 'Sematkan di Atas (Pin)' }}">
                                                <i class="fas fa-thumbtack"></i>
                                            </button>
                                        </form>

                                        <!-- Edit Modal Trigger -->
                                        <button type="button"
                                                class="btn btn-secondary btn-sm"
                                                style="padding: 5px 8px;"
                                                onclick="openEditModal({{ json_encode($item) }})"
                                                title="Edit Pengumuman">
                                            <i class="fas fa-edit"></i>
                                        </button>

                                        <!-- Delete Button -->
                                        <form action="{{ route('announcements.destroy', $item->id) }}"
                                              method="POST"
                                              style="display: inline;"
                                              onsubmit="return confirm('Apakah Anda yakin ingin menghapus pengumuman ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="btn btn-danger btn-sm"
                                                    style="padding: 5px 8px;"
                                                    title="Hapus Pengumuman">
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

            <div style="margin-top: var(--space-4);">
                {{ $announcements->links() }}
            </div>
        @else
            <div style="text-align: center; padding: var(--space-8) 0; color: var(--text-muted);">
                <i class="fas fa-bullhorn" style="font-size: 2.5rem; opacity: 0.4; margin-bottom: var(--space-2); display: block;"></i>
                <h4 style="margin: 0; color: var(--text-primary);">Belum ada pengumuman</h4>
                <p style="margin: 4px 0 0; font-size: 0.88rem;">Gunakan formulir di atas untuk membuat pengumuman pertama Anda.</p>
            </div>
        @endif
    </div>
</div>

<!-- Modal Edit Pengumuman -->
<div class="announcement-modal-overlay" id="editModalOverlay">
    <div class="announcement-modal">
        <div class="announcement-modal-header">
            <h4 style="margin: 0; font-weight: 700; color: var(--text-primary); font-size: 1.1rem;">
                <i class="fas fa-edit" style="color: var(--blue-primary); margin-right: 6px;"></i> Edit Pengumuman / Pesan
            </h4>
            <button type="button" onclick="closeEditModal()" style="background: none; border: none; font-size: 1.25rem; color: var(--text-muted); cursor: pointer;">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form id="editForm" method="POST" action="">
            @csrf
            @method('PUT')

            <div class="announcement-modal-body">
                <div class="form-group mb-4">
                    <label class="form-label" for="edit_judul">Judul Pengumuman <span class="required">*</span></label>
                    <input type="text" name="judul" id="edit_judul" class="form-control" required>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: var(--space-4);">
                    <div class="form-group mb-0">
                        <label class="form-label" for="edit_tipe">Kategori / Prioritas <span class="required">*</span></label>
                        <select name="tipe" id="edit_tipe" class="form-control" required>
                            <option value="info">Informasi</option>
                            <option value="penting">Penting</option>
                            <option value="mendesak">Mendesak / Urgent</option>
                        </select>
                    </div>

                    <div class="form-group mb-0">
                        <label class="form-label" for="edit_target_type">Target Penerima <span class="required">*</span></label>
                        <select name="target_type" id="edit_target_type" class="form-control" onchange="toggleEditTargetFields()" required>
                            <option value="all">Semua Pengurus</option>
                            <option value="departemen">Per Departemen</option>
                            <option value="personal">Anggota Tertentu</option>
                        </select>
                    </div>
                </div>

                <div id="editTargetDeptWrapper" class="form-group mb-4" style="display: none;">
                    <label class="form-label" for="edit_target_departemen">Pilih Departemen <span class="required">*</span></label>
                    <select name="target_departemen" id="edit_target_departemen" class="form-control">
                        @foreach($departments as $dept)
                            <option value="{{ $dept }}">{{ $dept === 'BPH' ? 'BPH (Seluruh BPH)' : 'Departemen ' . $dept }}</option>
                        @endforeach
                    </select>
                </div>

                <div id="editTargetPersonalWrapper" class="form-group mb-4" style="display: none;">
                    <label class="form-label" for="edit_target_user_id">Pilih Pengurus <span class="required">*</span></label>
                    <select name="target_user_id" id="edit_target_user_id" class="form-control">
                        @foreach($members as $m)
                            <option value="{{ $m->id }}">{{ $m->nama_lengkap }} ({{ $m->departemen }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group mb-4">
                    <label class="form-label" for="edit_konten">Isi Pengumuman <span class="required">*</span></label>
                    <textarea name="konten" id="edit_konten" rows="4" class="form-control" required></textarea>
                </div>

                <div style="display: flex; flex-wrap: wrap; gap: 14px; background: var(--bg-surface-subtle); padding: 10px 14px; border-radius: var(--radius-md);">
                    <label style="display: flex; align-items: center; gap: 6px; cursor: pointer; font-size: 0.84rem; font-weight: 600; color: var(--text-primary); margin: 0;">
                        <input type="checkbox" name="is_banner" id="edit_is_banner" value="1">
                        <span>Banner Dashboard</span>
                    </label>
                    <label style="display: flex; align-items: center; gap: 6px; cursor: pointer; font-size: 0.84rem; font-weight: 600; color: var(--text-primary); margin: 0;">
                        <input type="checkbox" name="pinned" id="edit_pinned" value="1">
                        <span>Sematkan (Pin)</span>
                    </label>
                    <label style="display: flex; align-items: center; gap: 6px; cursor: pointer; font-size: 0.84rem; font-weight: 600; color: var(--text-primary); margin: 0;">
                        <input type="checkbox" name="is_active" id="edit_is_active" value="1">
                        <span>Status Aktif</span>
                    </label>
                </div>
            </div>

            <div class="announcement-modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function toggleTargetFields() {
        const targetType = document.querySelector('input[name="target_type"]:checked')?.value || 'all';
        const deptWrapper = document.getElementById('targetDepartemenWrapper');
        const personalWrapper = document.getElementById('targetPersonalWrapper');

        if (deptWrapper) {
            deptWrapper.style.display = targetType === 'departemen' ? 'block' : 'none';
        }
        if (personalWrapper) {
            personalWrapper.style.display = targetType === 'personal' ? 'block' : 'none';
        }
    }

    function toggleEditTargetFields() {
        const targetType = document.getElementById('edit_target_type')?.value || 'all';
        const deptWrapper = document.getElementById('editTargetDeptWrapper');
        const personalWrapper = document.getElementById('editTargetPersonalWrapper');

        if (deptWrapper) {
            deptWrapper.style.display = targetType === 'departemen' ? 'block' : 'none';
        }
        if (personalWrapper) {
            personalWrapper.style.display = targetType === 'personal' ? 'block' : 'none';
        }
    }

    function openEditModal(item) {
        const modal = document.getElementById('editModalOverlay');
        const form = document.getElementById('editForm');
        form.action = "{{ url('/announcements') }}/" + item.id;

        document.getElementById('edit_judul').value = item.judul || '';
        document.getElementById('edit_tipe').value = item.tipe || 'info';
        document.getElementById('edit_target_type').value = item.target_type || 'all';
        document.getElementById('edit_konten').value = item.konten || '';
        document.getElementById('edit_is_banner').checked = Boolean(item.is_banner);
        document.getElementById('edit_pinned').checked = Boolean(item.pinned);
        document.getElementById('edit_is_active').checked = Boolean(item.is_active);

        if (item.target_departemen) {
            document.getElementById('edit_target_departemen').value = item.target_departemen;
        }
        if (item.target_user_id) {
            document.getElementById('edit_target_user_id').value = item.target_user_id;
        }

        toggleEditTargetFields();
        modal.classList.add('active');
    }

    function closeEditModal() {
        const modal = document.getElementById('editModalOverlay');
        modal.classList.remove('active');
    }

    document.getElementById('editModalOverlay')?.addEventListener('click', function(e) {
        if (e.target === this) {
            closeEditModal();
        }
    });
</script>
@endpush
