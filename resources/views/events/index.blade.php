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
<div class="event-tabs-wrapper mb-6">
    <div class="tabs">
        <button class="tab-btn active" onclick="switchEventTab('active', this)" title="Event Aktif">
            <i class="fas fa-calendar-check"></i>
            <span class="tab-label"><span class="tab-prefix">Event </span>Aktif</span>
            <span class="tab-badge">{{ $activeEvents->count() }}</span>
        </button>
        <button class="tab-btn" onclick="switchEventTab('completed', this)" title="Event Selesai">
            <i class="fas fa-trophy"></i>
            <span class="tab-label"><span class="tab-prefix">Event </span>Selesai</span>
            <span class="tab-badge tab-badge-success">{{ $completedEvents->count() }}</span>
        </button>
        <button class="tab-btn" onclick="switchEventTab('archived', this)" title="Arsip Event">
            <i class="fas fa-archive"></i>
            <span class="tab-label">Arsip</span>
            <span class="tab-badge">{{ $archivedEvents->count() }}</span>
        </button>
    </div>
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
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: var(--space-4);">
            @foreach ($activeEvents as $evt)
            <div class="card">
                <div class="d-flex justify-between align-center mb-3">
                    <span class="badge badge-success"><i class="fas fa-circle" style="font-size: 8px;"></i> Aktif</span>
                    <small style="color: var(--text-muted); font-size: 0.8rem;">
                        <i class="fas fa-calendar-alt" style="color: var(--blue-primary); font-size: 10px;"></i>
                        {{ $evt->created_at ? $evt->created_at->locale('id')->translatedFormat('d M Y') : '' }}
                    </small>
                </div>
                <h3 style="font-size: var(--font-lg); font-weight: 700; margin-bottom: var(--space-2); color: var(--text-primary);">
                    {{ $evt->nama_event }}
                </h3>
                <p style="font-size: var(--font-sm); color: var(--text-secondary); min-height: 40px; margin-bottom: var(--space-4);">
                    {{ $evt->deskripsi ?: 'Tidak ada deskripsi.' }}
                </p>
                
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); text-align: center; gap: var(--space-2); margin-bottom: var(--space-4); padding: var(--space-3); background: var(--bg-surface-subtle); border-radius: var(--radius-md); border: 1px solid var(--border-color);">
                    <div>
                        <div style="font-size: var(--font-lg); font-weight: 700; color: var(--blue-primary);">{{ $evt->divisions_count }}</div>
                        <small style="color: var(--text-muted);">Divisi</small>
                    </div>
                    <div>
                        <div style="font-size: var(--font-lg); font-weight: 700; color: var(--blue-primary);">{{ $evt->members_count }}</div>
                        <small style="color: var(--text-muted);">Panitia</small>
                    </div>
                    <div>
                        <div style="font-size: var(--font-lg); font-weight: 700; color: var(--blue-primary);">{{ $evt->meetings_count }}</div>
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

<!-- Completed Events -->
<div id="tab-completed" class="event-tab-content" style="display: none;">
    @if ($completedEvents->isEmpty())
        <div class="card empty-state">
            <i class="fas fa-trophy" style="color: #10B981;"></i>
            <h3>Belum Ada Event yang Diselesaikan</h3>
            <p>Selesaikan event yang telah rampung untuk membagikan lencana dan EXP kepanitiaan kepada seluruh panitia.</p>
        </div>
    @else
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: var(--space-4);">
            @foreach ($completedEvents as $evt)
            <div class="card" style="border-top: 3px solid #10B981;">
                <div class="d-flex justify-between align-center mb-3">
                    <span class="badge" style="background: rgba(16, 185, 129, 0.15); color: #10B981; border: 1px solid rgba(16, 185, 129, 0.3);">
                        <i class="fas fa-trophy"></i> Selesai
                    </span>
                    <small style="color: var(--text-muted); font-size: 0.8rem;">
                        <i class="fas fa-calendar-check" style="color: #10B981; font-size: 10px;"></i>
                        {{ $evt->completed_at ? $evt->completed_at->locale('id')->translatedFormat('d M Y') : '' }}
                    </small>
                </div>
                <h3 style="font-size: var(--font-lg); font-weight: 700; margin-bottom: var(--space-2); color: var(--text-primary);">
                    {{ $evt->nama_event }}
                </h3>
                <p style="font-size: var(--font-sm); color: var(--text-secondary); min-height: 40px; margin-bottom: var(--space-3);">
                    {{ $evt->deskripsi ?: 'Tidak ada deskripsi.' }}
                </p>

                <div style="padding: 6px 10px; background: rgba(16, 185, 129, 0.1); border-radius: var(--radius-sm); font-size: 11px; color: #10B981; margin-bottom: var(--space-3); display: flex; align-items: center; gap: 6px;">
                    <i class="fas fa-medal"></i>
                    <span>Lencana & EXP telah dibagikan ke {{ $evt->members_count }} panitia</span>
                </div>

                <div style="display: grid; grid-template-columns: repeat(3, 1fr); text-align: center; gap: var(--space-2); margin-bottom: var(--space-4); padding: var(--space-3); background: var(--bg-surface-subtle); border-radius: var(--radius-md); border: 1px solid var(--border-color);">
                    <div>
                        <div style="font-size: var(--font-lg); font-weight: 700; color: #10B981;">{{ $evt->divisions_count }}</div>
                        <small style="color: var(--text-muted);">Divisi</small>
                    </div>
                    <div>
                        <div style="font-size: var(--font-lg); font-weight: 700; color: #10B981;">{{ $evt->members_count }}</div>
                        <small style="color: var(--text-muted);">Panitia</small>
                    </div>
                    <div>
                        <div style="font-size: var(--font-lg); font-weight: 700; color: #10B981;">{{ $evt->meetings_count }}</div>
                        <small style="color: var(--text-muted);">Rapat</small>
                    </div>
                </div>

                <div style="display: flex; gap: var(--space-2); justify-content: space-between;">
                    <a href="{{ route('events.show', $evt->id) }}" class="btn btn-sm btn-primary" style="flex: 1; text-align: center;">
                        <i class="fas fa-eye"></i> Detail & Lencana
                    </a>
                    <form action="{{ route('events.reopen', $evt->id) }}" method="POST" onsubmit="return confirm('Buka kembali event ini? Lencana dan bonus EXP panitia akan ditarik kembali.')" style="margin: 0;">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-warning" title="Buka Kembali Event">
                            <i class="fas fa-undo"></i>
                        </button>
                    </form>
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
<div id="tab-archived" class="event-tab-content" style="display: none;">
    @if ($archivedEvents->isEmpty())
        <div class="card empty-state">
            <i class="fas fa-archive"></i>
            <h3>Tidak Ada Event yang Diarsipkan</h3>
            <p>Event yang sudah selesai dapat diarsipkan agar tidak muncul di pilihan rapat.</p>
        </div>
    @else
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: var(--space-4);">
            @foreach ($archivedEvents as $evt)
            <div class="card" style="opacity: 0.9;">
                <div class="d-flex justify-between align-center mb-3">
                    <span class="badge badge-secondary"><i class="fas fa-archive"></i> Diarsipkan</span>
                    <small style="color: var(--text-muted); font-size: 0.8rem;">
                        <i class="fas fa-calendar-alt" style="color: var(--blue-primary); font-size: 10px;"></i>
                        {{ $evt->created_at ? $evt->created_at->locale('id')->translatedFormat('d M Y') : '' }}
                    </small>
                </div>
                <h3 style="font-size: var(--font-lg); font-weight: 700; margin-bottom: var(--space-2); color: var(--text-primary);">
                    {{ $evt->nama_event }}
                </h3>
                <p style="font-size: var(--font-sm); color: var(--text-secondary); min-height: 40px; margin-bottom: var(--space-4);">
                    {{ $evt->deskripsi ?: 'Tidak ada deskripsi.' }}
                </p>

                <div style="display: grid; grid-template-columns: repeat(3, 1fr); text-align: center; gap: var(--space-2); margin-bottom: var(--space-4); padding: var(--space-3); background: var(--bg-surface-subtle); border-radius: var(--radius-md); border: 1px solid var(--border-color);">
                    <div>
                        <div style="font-size: var(--font-lg); font-weight: 700; color: var(--text-muted);">{{ $evt->divisions_count }}</div>
                        <small style="color: var(--text-muted);">Divisi</small>
                    </div>
                    <div>
                        <div style="font-size: var(--font-lg); font-weight: 700; color: var(--text-muted);">{{ $evt->members_count }}</div>
                        <small style="color: var(--text-muted);">Panitia</small>
                    </div>
                    <div>
                        <div style="font-size: var(--font-lg); font-weight: 700; color: var(--text-muted);">{{ $evt->meetings_count }}</div>
                        <small style="color: var(--text-muted);">Rapat</small>
                    </div>
                </div>

                <div style="display: flex; gap: var(--space-2); justify-content: space-between;">
                    <a href="{{ route('events.show', $evt->id) }}" class="btn btn-sm btn-secondary" style="flex: 1; text-align: center; justify-content: center;">
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

@push('styles')
<style>
.event-tabs-wrapper {
    width: 100%;
    margin-bottom: var(--space-6);
}

.event-tabs-wrapper .tabs {
    display: flex;
    width: 100%;
    max-width: 580px;
    padding: 5px !important;
    gap: 6px !important;
    box-sizing: border-box;
    border-radius: 9999px !important;
}

.event-tabs-wrapper .tab-btn {
    flex: 1;
    min-width: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 9px 12px !important;
    font-size: 0.85rem !important;
    font-weight: 700;
    gap: 7px;
    white-space: nowrap;
    border-radius: 9999px !important;
    cursor: pointer;
    transition: all var(--transition-fast);
}

.event-tabs-wrapper .tab-label {
    display: inline-flex;
    align-items: center;
    white-space: nowrap;
}

.event-tabs-wrapper .tab-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 20px;
    height: 20px;
    padding: 0 6px;
    border-radius: 9999px;
    font-size: 0.72rem;
    font-weight: 800;
    background: rgba(48, 175, 255, 0.15);
    color: var(--text-primary);
    transition: all var(--transition-fast);
    flex-shrink: 0;
}

.event-tabs-wrapper .tab-badge.tab-badge-success {
    background: rgba(16, 185, 129, 0.2) !important;
    color: #10B981 !important;
}

/* Contrast fix for active tab (fixes invisible icon and badge on cyan bg) */
.event-tabs-wrapper .tab-btn.active {
    background: #30AFFF !important;
    color: #061A2E !important;
}

.event-tabs-wrapper .tab-btn.active i {
    color: #061A2E !important;
}

.event-tabs-wrapper .tab-btn.active .tab-label {
    color: #061A2E !important;
}

.event-tabs-wrapper .tab-btn.active .tab-badge {
    background: #061A2E !important;
    color: #30AFFF !important;
    border: none !important;
}

.event-tabs-wrapper .tab-btn.active .tab-badge.tab-badge-success {
    background: #061A2E !important;
    color: #10B981 !important;
}

/* Mobile responsive tabs: Aktif | Selesai | Arsip (no clipping) */
@media (max-width: 640px) {
    .event-tabs-wrapper .tabs {
        max-width: 100% !important;
        padding: 4px !important;
        gap: 3px !important;
    }

    .event-tabs-wrapper .tab-btn {
        padding: 8px 4px !important;
        font-size: 0.78rem !important;
        gap: 4px !important;
    }

    /* Hide redundant "Event " prefix on mobile: Aktif | Selesai | Arsip */
    .event-tabs-wrapper .tab-prefix {
        display: none !important;
    }

    .event-tabs-wrapper .tab-btn i {
        font-size: 0.8rem;
    }

    .event-tabs-wrapper .tab-badge {
        min-width: 18px !important;
        height: 18px !important;
        padding: 0 4px !important;
        font-size: 0.68rem !important;
    }
}

@media (max-width: 380px) {
    .event-tabs-wrapper .tab-btn {
        padding: 7px 3px !important;
        font-size: 0.72rem !important;
        gap: 3px !important;
    }

    .event-tabs-wrapper .tab-btn i {
        font-size: 0.72rem;
    }

    .event-tabs-wrapper .tab-badge {
        min-width: 16px !important;
        height: 16px !important;
        padding: 0 3px !important;
        font-size: 0.64rem !important;
    }
}
</style>
@endpush

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
