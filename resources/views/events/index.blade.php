@extends('layouts.app')

@section('title', 'Kelola Event')

@section('page_icon')
    <i class="fas fa-calendar-alt"></i>
@endsection

@section('content')
<div class="d-flex align-center justify-between flex-wrap gap-4 mb-6">
    <div>
        <h2 style="font-size: var(--font-xl); margin-bottom: 4px;">Daftar Kepanitiaan & Event</h2>
        <p style="font-size: var(--font-sm); color: var(--text-muted); margin: 0;">Kelola divisi panitia dan anggota per kegiatan</p>
    </div>
    <button class="btn btn-primary" onclick="openModal('addEventModal')">
        <i class="fas fa-plus"></i> Tambah Event Baru
    </button>
</div>

<!-- Tabs -->
<div class="tabs mb-6">
    <button class="tab-btn active" onclick="switchEventTab('active', this)">
        Event Aktif ({{ $activeEvents->count() }})
    </button>
    <button class="tab-btn" onclick="switchEventTab('archived', this)">
        Arsip ({{ $archivedEvents->count() }})
    </button>
</div>

<!-- Active Events -->
<div id="tab-active" class="event-tab-content">
    @if ($activeEvents->isEmpty())
        <div class="card empty-state">
            <i class="fas fa-calendar-times"></i>
            <h3>Belum Ada Event Aktif</h3>
            <p>Klik tombol "Tambah Event Baru" untuk mulai mendata event dan panitia.</p>
        </div>
    @else
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: var(--space-4);">
            @foreach ($activeEvents as $evt)
            <div class="card">
                <div class="d-flex justify-between align-center mb-3">
                    <span class="badge badge-success"><i class="fas fa-circle" style="font-size: 8px;"></i> Aktif</span>
                    <small style="color: var(--text-muted);">{{ $evt->created_at ? $evt->created_at->format('d M Y') : '' }}</small>
                </div>
                <h3 style="font-size: var(--font-lg); margin-bottom: var(--space-2); color: var(--primary);">
                    {{ $evt->nama_event }}
                </h3>
                <p style="font-size: var(--font-sm); color: var(--text-secondary); min-height: 40px; margin-bottom: var(--space-4);">
                    {{ $evt->deskripsi ?: 'Tidak ada deskripsi.' }}
                </p>
                
                <div style="display: flex; gap: var(--space-4); margin-bottom: var(--space-4); padding: var(--space-3); background: var(--dark-surface-2); border-radius: var(--radius-md);">
                    <div>
                        <div style="font-size: var(--font-lg); font-weight: 700; color: var(--text-primary);">{{ $evt->divisions_count }}</div>
                        <small style="color: var(--text-muted);">Divisi</small>
                    </div>
                    <div>
                        <div style="font-size: var(--font-lg); font-weight: 700; color: var(--text-primary);">{{ $evt->members_count }}</div>
                        <small style="color: var(--text-muted);">Panitia</small>
                    </div>
                    <div>
                        <div style="font-size: var(--font-lg); font-weight: 700; color: var(--text-primary);">{{ $evt->meetings_count }}</div>
                        <small style="color: var(--text-muted);">Rapat</small>
                    </div>
                </div>

                <div style="display: flex; gap: var(--space-2); justify-content: space-between;">
                    <a href="{{ route('events.show', $evt->id) }}" class="btn btn-sm btn-primary" style="flex: 1; text-align: center;">
                        <i class="fas fa-cog"></i> Kelola Divisi & Panitia
                    </a>
                    <form action="{{ route('events.archive', $evt->id) }}" method="POST" style="margin: 0;">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-ghost" title="Arsipkan Event">
                            <i class="fas fa-archive"></i>
                        </button>
                    </form>
                </div>
            </div>
            @endforeach
        </div>
    @endif
</div>

<!-- Archived Events -->
<div id="tab-archived" class="event-tab-content" style="display: none;">
    @if ($archivedEvents->isEmpty())
        <div class="card empty-state">
            <i class="fas fa-archive"></i>
            <h3>Tidak Ada Event yang Diarsipkan</h3>
            <p>Event yang sudah selesai dapat diarsipkan agar tidak muncul di pilihan rapat.</p>
        </div>
    @else
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: var(--space-4);">
            @foreach ($archivedEvents as $evt)
            <div class="card" style="opacity: 0.85;">
                <div class="d-flex justify-between align-center mb-3">
                    <span class="badge badge-secondary"><i class="fas fa-archive"></i> Diarsipkan</span>
                </div>
                <h3 style="font-size: var(--font-lg); margin-bottom: var(--space-2);">
                    {{ $evt->nama_event }}
                </h3>
                <p style="font-size: var(--font-sm); color: var(--text-secondary); margin-bottom: var(--space-4);">
                    {{ $evt->deskripsi ?: 'Tidak ada deskripsi.' }}
                </p>

                <div style="display: flex; gap: var(--space-2);">
                    <a href="{{ route('events.show', $evt->id) }}" class="btn btn-sm btn-secondary" style="flex: 1; text-align: center;">
                        <i class="fas fa-eye"></i> Lihat Data
                    </a>
                    <form action="{{ route('events.restore', $evt->id) }}" method="POST" style="margin: 0;">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-success" title="Aktifkan Kembali">
                            <i class="fas fa-undo"></i>
                        </button>
                    </form>
                    <form action="{{ route('events.destroy', $evt->id) }}" method="POST" onsubmit="return confirm('Hapus permanen event ini?')" style="margin: 0;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-ghost text-danger" title="Hapus Permanen">
                            <i class="fas fa-trash"></i>
                        </button>
                    </form>
                </div>
            </div>
            @endforeach
        </div>
    @endif
</div>

<!-- Modal Add Event -->
<div class="modal" id="addEventModal" style="display: none;">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-calendar-plus"></i> Tambah Event / Kepanitiaan Baru</h3>
                <button class="modal-close" onclick="closeModal('addEventModal')">&times;</button>
            </div>
            <form action="{{ route('events.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label" for="nama_event">Nama Event / Kegiatan <span class="required">*</span></label>
                        <input type="text" id="nama_event" name="nama_event" class="form-control" placeholder="Contoh: Dies Natalis HIMA TI #7" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="deskripsi">Deskripsi Singkat</label>
                        <textarea id="deskripsi" name="deskripsi" class="form-control" placeholder="Tuliskan tujuan atau keterangan kegiatan..." rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('addEventModal')">Batal</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan Event</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function switchEventTab(tabName, btn) {
    document.querySelectorAll('.tabs .tab-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');

    document.querySelectorAll('.event-tab-content').forEach(c => c.style.display = 'none');
    document.getElementById('tab-' + tabName).style.display = 'block';
}

function openModal(id) {
    document.getElementById(id).style.display = 'flex';
}
function closeModal(id) {
    document.getElementById(id).style.display = 'none';
}
</script>
@endpush
