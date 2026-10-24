@extends('layouts.app')

@section('title', 'Detail Event: ' . $event->nama_event)

@section('page_icon')
    <i class="fas fa-calendar-alt"></i>
@endsection

@section('content')
<div class="card mb-6" style="border-left: 4px solid var(--primary);">
    <div class="d-flex align-center justify-between flex-wrap gap-4 mb-3">
        <div>
            <h2 style="font-size: var(--font-xl); color: var(--primary); margin-bottom: 4px;">
                {{ $event->nama_event }}
            </h2>
            <p style="font-size: var(--font-sm); color: var(--text-muted); margin: 0;">
                {{ $event->deskripsi ?: 'Tidak ada deskripsi.' }}
            </p>
        </div>
        <div style="display: flex; gap: var(--space-3); align-items: center;">
            <a href="{{ route('events.index') }}" class="btn btn-sm btn-secondary">
                <i class="fas fa-arrow-left"></i> Kembali ke Event
            </a>
            <button class="btn btn-sm btn-primary" onclick="openModal('addDivModal')">
                <i class="fas fa-plus"></i> Tambah Divisi
            </button>
        </div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: var(--space-6);" class="event-detail-grid">
    <!-- Left Column: Divisions and Members -->
    <div>
        <h3 style="font-size: var(--font-lg); margin-bottom: var(--space-4);">
            <i class="fas fa-sitemap"></i> Struktur Divisi & Panitia
        </h3>

        @if($divisions->isEmpty())
            <div class="card empty-state">
                <i class="fas fa-folder-open"></i>
                <h3>Belum Ada Divisi</h3>
                <p>Klik tombol "Tambah Divisi" untuk membuat divisi panitia (contoh: Acara, Perlengkapan, Humas).</p>
                <button class="btn btn-primary mt-4" onclick="openModal('addDivModal')">
                    <i class="fas fa-plus"></i> Buat Divisi Pertama
                </button>
            </div>
        @else
            @foreach($divisions as $div)
            <div class="card mb-4">
                <div class="card-header" style="border-bottom: 1px solid var(--dark-border);">
                    <div class="d-flex align-center gap-3">
                        <span class="badge badge-primary" style="font-size: var(--font-sm);">
                            <i class="fas fa-layer-group"></i> {{ $div->nama_divisi }}
                        </span>
                        <small style="color: var(--text-muted);">{{ $div->members->count() }} orang</small>
                    </div>
                    <div style="display: flex; gap: var(--space-2);">
                        <button class="btn btn-sm btn-outline-primary" onclick="openAddMemberModal({{ $div->id }}, '{{ addslashes($div->nama_divisi) }}')">
                            <i class="fas fa-user-plus"></i> Tambah Panitia
                        </button>
                        <form action="{{ route('events.divisions.destroy', [$event->id, $div->id]) }}" method="POST" onsubmit="return confirm('Hapus divisi ini beserta seluruh anggota di dalamnya?')" style="margin: 0;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-ghost text-danger" title="Hapus Divisi">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </div>
                </div>

                @if($div->members->isEmpty())
                    <p style="color: var(--text-muted); font-size: var(--font-sm); margin: var(--space-4) 0 0 0;">
                        Belum ada anggota yang ditugaskan ke divisi ini.
                    </p>
                @else
                    <div class="table-wrapper mt-3">
                        <table class="table" style="margin: 0;">
                            <thead>
                                <tr>
                                    <th>Nama Panitia</th>
                                    <th>Departemen Asal</th>
                                    <th>Jabatan Kepanitiaan</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($div->members as $em)
                                <tr>
                                    <td>
                                        <strong>{{ $em->member ? $em->member->nama_lengkap : 'Unknown' }}</strong>
                                        <div style="font-size: var(--font-xs); color: var(--text-muted);">
                                            {{ $em->member ? $em->member->email : '' }}
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge badge-dept">{{ $em->member ? $em->member->departemen : '-' }}</span>
                                    </td>
                                    <td>
                                        <span class="badge badge-info">{{ $em->jabatan_panitia }}</span>
                                    </td>
                                    <td>
                                        <form action="{{ route('events.members.destroy', [$event->id, $em->id]) }}" method="POST" onsubmit="return confirm('Keluarkan anggota ini dari kepanitiaan?')" style="margin: 0;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-ghost text-danger" title="Keluarkan">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
            @endforeach
        @endif
    </div>

    <!-- Right Column: Event Info & Meetings linked -->
    <div>
        <div class="card mb-6">
            <h3 class="card-title mb-4"><i class="fas fa-info-circle"></i> Info Kegiatan</h3>
            <div style="font-size: var(--font-sm);">
                <div style="margin-bottom: var(--space-3);">
                    <span style="color: var(--text-muted); display: block;">Total Divisi:</span>
                    <strong>{{ $divisions->count() }} Divisi</strong>
                </div>
                <div style="margin-bottom: var(--space-3);">
                    <span style="color: var(--text-muted); display: block;">Total Panitia:</span>
                    <strong>{{ $event->members->count() }} Orang</strong>
                </div>
                <div>
                    <span style="color: var(--text-muted); display: block;">Status:</span>
                    <span class="badge {{ $event->status === 'active' ? 'badge-success' : 'badge-secondary' }}">
                        {{ ucfirst($event->status) }}
                    </span>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title" style="font-size: var(--font-base);">
                    <i class="fas fa-clipboard-list"></i> Rapat Terkait Event
                </h3>
            </div>
            @if($event->meetings->isEmpty())
                <p style="font-size: var(--font-sm); color: var(--text-muted); margin: 0;">
                    Belum ada rapat kepanitiaan yang dibuat untuk event ini.
                </p>
            @else
                <div style="display: flex; flex-direction: column; gap: var(--space-3);">
                    @foreach($event->meetings as $m)
                    <div style="padding: var(--space-3); background: var(--dark-surface-2); border-radius: var(--radius-md);">
                        <a href="{{ route('meetings.show', $m->id) }}" style="color: var(--primary); font-weight: 600; text-decoration: none;">
                            {{ $m->fokus_bahasan ?? 'Rapat Kepanitiaan' }}
                        </a>
                        <div style="font-size: var(--font-xs); color: var(--text-muted); margin-top: 2px;">
                            {{ $m->tanggal->format('d M Y') }} • {{ substr($m->waktu_mulai, 0, 5) }} WITA
                        </div>
                    </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Modal Add Division -->
<div class="modal" id="addDivModal" style="display: none;">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-layer-group"></i> Tambah Divisi Baru</h3>
                <button class="modal-close" onclick="closeModal('addDivModal')">&times;</button>
            </div>
            <form action="{{ route('events.divisions.store', $event->id) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label" for="nama_divisi">Nama Divisi <span class="required">*</span></label>
                        <input type="text" id="nama_divisi" name="nama_divisi" class="form-control" placeholder="Contoh: Divisi Acara / Divisi Pubdok" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('addDivModal')">Batal</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Tambah Divisi</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Add Member to Division -->
<div class="modal" id="addMemberModal" style="display: none;">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-user-plus"></i> Tambah Panitia ke <span id="targetDivName"></span></h3>
                <button class="modal-close" onclick="closeModal('addMemberModal')">&times;</button>
            </div>
            <form action="{{ route('events.members.store', $event->id) }}" method="POST">
                @csrf
                <input type="hidden" name="division_id" id="targetDivId">
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label">Pilih Anggota <span class="required">*</span></label>
                        <div style="max-height: 220px; overflow-y: auto; background: var(--dark-surface-2); padding: 10px; border-radius: var(--radius-md);">
                            @foreach($allMembers as $m)
                                <label style="display: flex; align-items: center; gap: 8px; padding: 4px 0; cursor: pointer; font-size: var(--font-sm);">
                                    <input type="checkbox" name="user_ids[]" value="{{ $m->id }}">
                                    <span>{{ $m->nama_lengkap }} <small style="color: var(--text-muted);">({{ $m->departemen }})</small></span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="jabatan_panitia">Jabatan dalam Divisi</label>
                        <input type="text" id="jabatan_panitia" name="jabatan_panitia" class="form-control" placeholder="Contoh: Koordinator / Anggota" value="Anggota">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('addMemberModal')">Batal</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-check"></i> Tambahkan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    @media (max-width: 850px) {
        .event-detail-grid {
            grid-template-columns: 1fr !important;
        }
    }
</style>
@endpush

@push('scripts')
<script>
function openModal(id) {
    document.getElementById(id).style.display = 'flex';
}
function closeModal(id) {
    document.getElementById(id).style.display = 'none';
}
function openAddMemberModal(divId, divName) {
    document.getElementById('targetDivId').value = divId;
    document.getElementById('targetDivName').textContent = divName;
    openModal('addMemberModal');
}
</script>
@endpush
