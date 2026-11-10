@extends('layouts.app')

@section('title', 'Detail Event: ' . $event->nama_event)

@section('page_icon')
    <i class="fas fa-calendar-alt"></i>
@endsection

@section('content')
<div class="card mb-6" style="border-left: 4px solid {{ $event->isCompleted() ? '#10B981' : 'var(--primary)' }};">
    <div class="d-flex align-center justify-between flex-wrap gap-4 mb-3">
        <div>
            <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-bottom: 4px;">
                <h2 style="font-size: var(--font-xl); color: var(--primary); margin: 0;">
                    {{ $event->nama_event }}
                </h2>
                @if($event->isCompleted())
                    <span class="badge" style="background: rgba(16, 185, 129, 0.15); color: #10B981; border: 1px solid rgba(16, 185, 129, 0.3); font-size: var(--font-xs); padding: 4px 10px; border-radius: 9999px;">
                        <i class="fas fa-check-circle"></i> Event Selesai
                    </span>
                @elseif($event->status === 'archived')
                    <span class="badge badge-secondary" style="font-size: var(--font-xs); padding: 4px 10px; border-radius: 9999px;">
                        <i class="fas fa-archive"></i> Diarsipkan
                    </span>
                @else
                    <span class="badge badge-success" style="font-size: var(--font-xs); padding: 4px 10px; border-radius: 9999px;">
                        <i class="fas fa-circle" style="font-size: 8px;"></i> Aktif
                    </span>
                @endif
            </div>
            <p style="font-size: var(--font-sm); color: var(--text-muted); margin: 0;">
                {{ $event->deskripsi ?: 'Tidak ada deskripsi.' }}
            </p>
            @if($event->isCompleted())
                <div style="margin-top: 6px; font-size: var(--font-xs); color: #10B981; display: flex; align-items: center; gap: 6px;">
                    <i class="fas fa-medal"></i>
                    <span>Diselesaikan pada <strong>{{ $event->completed_at ? $event->completed_at->format('d M Y, H:i') : '-' }} WITA</strong> &bull; Seluruh panitia telah menerima lencana kepanitiaan dan bonus EXP.</span>
                </div>
            @endif
        </div>
        <div class="event-header-actions">
            <a href="{{ route('events.index') }}" class="btn btn-sm btn-secondary">
                <i class="fas fa-arrow-left"></i> Kembali ke Event
            </a>

            @if($event->isCompleted())
                <form action="{{ route('events.reopen', $event->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin membuka kembali event ini? Lencana kepanitiaan dan bonus EXP yang telah dibagikan ke panitia akan ditarik kembali.')" style="margin: 0;">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-warning">
                        <i class="fas fa-undo"></i> Buka Kembali Event
                    </button>
                </form>
            @elseif($event->status === 'active')
                <form action="{{ route('events.complete', $event->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menyelesaikan event ini?\n\nSeluruh anggota yang terdaftar dalam kepanitiaan event ini akan langsung mendapatkan:\n1. Lencana Kepanitiaan Resmi di profil masing-masing\n2. Bonus EXP besar (+500 EXP Ketua, +350 EXP Koordinator/BPH/Sekretaris/Bendahara, +250 EXP Anggota)')" style="margin: 0;">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-success" style="box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);">
                        <i class="fas fa-award"></i> Selesaikan Event & Bagikan Lencana
                    </button>
                </form>
                <button class="btn btn-sm btn-primary" onclick="openModal('addDivModal')">
                    <i class="fas fa-plus"></i> Tambah Divisi
                </button>
            @endif
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
                <div class="card-header division-card-header">
                    <div class="division-title-wrap">
                        <span class="badge badge-primary" style="font-size: var(--font-sm);">
                            <i class="fas fa-layer-group"></i> {{ $div->nama_divisi }}
                        </span>
                        <small style="color: var(--text-muted); font-weight: 600;">{{ $div->members->count() }} orang</small>
                    </div>
                    <div class="division-actions-wrap">
                        <button class="btn btn-sm btn-outline-primary btn-add-panitia" onclick="openAddMemberModal({{ $div->id }}, '{{ addslashes($div->nama_divisi) }}')">
                            <i class="fas fa-user-plus"></i> Tambah Panitia
                        </button>
                        <form action="{{ route('events.divisions.destroy', [$event->id, $div->id]) }}" method="POST" onsubmit="return confirm('Hapus divisi ini beserta seluruh anggota di dalamnya?')" style="margin: 0;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-ghost text-danger btn-del-divisi" title="Hapus Divisi">
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
                    <!-- Desktop View: Table -->
                    <div class="table-wrapper mt-3 panitia-desktop-table">
                        <table class="table" style="margin: 0; min-width: 520px;">
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
                                        @php
                                            $memberExp = app(\App\Services\GamificationService::class)->calculateEventMemberExp($em->jabatan_panitia);
                                        @endphp
                                        <span class="badge" style="background: rgba(245, 158, 11, 0.15); color: #F59E0B; font-weight: 600; font-size: 11px; margin-left: 4px;" title="Bonus EXP penyelesaian event">
                                            <i class="fas fa-bolt"></i> +{{ $memberExp }} EXP
                                        </span>
                                        @if($event->isCompleted())
                                            <span class="badge" style="background: rgba(16, 185, 129, 0.15); color: #10B981; font-size: 11px; margin-left: 4px;" title="Lencana telah disematkan ke profil panitia">
                                                <i class="fas fa-medal"></i> Lencana Diterima
                                            </span>
                                        @endif
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

                    <!-- Mobile View: Clean Card List -->
                    <div class="panitia-mobile-cards mt-3">
                        @foreach($div->members as $em)
                        @php
                            $memberExp = app(\App\Services\GamificationService::class)->calculateEventMemberExp($em->jabatan_panitia);
                        @endphp
                        <div class="panitia-mobile-item">
                            <div class="panitia-mobile-top">
                                <div style="flex: 1; min-width: 0;">
                                    <strong class="panitia-mobile-name">{{ $em->member ? $em->member->nama_lengkap : 'Unknown' }}</strong>
                                    <div class="panitia-mobile-email">{{ $em->member ? $em->member->email : '-' }}</div>
                                </div>
                                <form action="{{ route('events.members.destroy', [$event->id, $em->id]) }}" method="POST" onsubmit="return confirm('Keluarkan anggota ini dari kepanitiaan?')" style="margin: 0; flex-shrink: 0;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="panitia-mobile-del-btn" title="Keluarkan dari Divisi">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </form>
                            </div>
                            <div class="panitia-mobile-badges">
                                <span class="badge badge-dept" style="font-size: 0.72rem;">{{ $em->member ? $em->member->departemen : '-' }}</span>
                                <span class="badge badge-info" style="font-size: 0.72rem;">{{ $em->jabatan_panitia }}</span>
                                <span class="badge" style="background: rgba(245, 158, 11, 0.15); color: #F59E0B; font-weight: 700; font-size: 0.72rem; padding: 2px 7px;">
                                    <i class="fas fa-bolt"></i> +{{ $memberExp }} EXP
                                </span>
                                @if($event->isCompleted())
                                    <span class="badge" style="background: rgba(16, 185, 129, 0.15); color: #10B981; font-size: 0.72rem; padding: 2px 7px;">
                                        <i class="fas fa-medal"></i> Lencana Diterima
                                    </span>
                                @endif
                            </div>
                        </div>
                        @endforeach
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
                    @if($event->isCompleted())
                        <span class="badge" style="background: rgba(16, 185, 129, 0.15); color: #10B981; border: 1px solid rgba(16, 185, 129, 0.3);">
                            <i class="fas fa-trophy"></i> Selesai
                        </span>
                        <div style="font-size: var(--font-xs); color: var(--text-muted); margin-top: 4px;">
                            {{ $event->completed_at ? $event->completed_at->format('d M Y, H:i') : '-' }} WITA
                        </div>
                    @elseif($event->status === 'active')
                        <span class="badge badge-success">Aktif</span>
                    @else
                        <span class="badge badge-secondary">Diarsipkan</span>
                    @endif
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
                            <i class="fas fa-calendar-alt" style="color: var(--blue-primary); font-size: 10px;"></i>
                            {{ $m->tanggal->locale('id')->translatedFormat('d M Y') }} • {{ substr($m->waktu_mulai, 0, 5) }} WITA
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
                        <div class="d-flex justify-between align-center mb-2">
                            <label class="form-label mb-0">Pilih Anggota <span class="required">*</span></label>
                            <span class="badge badge-info" style="font-size: var(--font-xs);" id="availableBadge">
                                {{ $availableMembers->count() }} Anggota Tersedia
                            </span>
                        </div>

                        @if($availableMembers->isNotEmpty())
                        <div style="margin-bottom: var(--space-2);">
                            <input type="text" id="memberSearchInput" class="form-control" placeholder="Cari nama atau departemen..." style="padding: 7px 12px; font-size: var(--font-sm);" onkeyup="filterMemberCheckboxes()">
                        </div>
                        <div class="d-flex justify-between align-center mb-2" style="font-size: var(--font-xs);">
                            <span style="color: var(--text-muted);"><span id="selectedMemberCount">0</span> anggota dipilih</span>
                            <div style="display: flex; gap: 8px;">
                                <a href="javascript:void(0)" onclick="toggleSelectAllMembers(true)" style="color: var(--blue-primary); text-decoration: none; font-weight: 500;">Pilih Semua</a>
                                <span style="color: var(--border-color);">|</span>
                                <a href="javascript:void(0)" onclick="toggleSelectAllMembers(false)" style="color: var(--text-muted); text-decoration: none;">Batal Pilih</a>
                            </div>
                        </div>
                        @endif

                        <div id="memberListContainer" style="max-height: 220px; overflow-y: auto; background: var(--bg-surface-subtle); border: 1.5px solid var(--border-color); padding: 8px 12px; border-radius: var(--radius-md);">
                            @forelse($availableMembers as $m)
                                <label class="member-checkbox-row" data-name="{{ strtolower($m->nama_lengkap) }}" data-dept="{{ strtolower($m->departemen) }}" style="display: flex; align-items: center; gap: 10px; padding: 6px 4px; border-bottom: 1px solid var(--border-color); cursor: pointer; font-size: var(--font-sm); transition: background var(--transition-fast);">
                                    <input type="checkbox" name="user_ids[]" value="{{ $m->id }}" onchange="updateSelectedCount()" style="width: 16px; height: 16px; accent-color: var(--blue-primary); cursor: pointer;">
                                    <span style="font-weight: 500; color: var(--text-primary);">{{ $m->nama_lengkap }}</span>
                                    <span class="badge badge-dept" style="margin-left: auto; font-size: 0.72rem; padding: 2px 8px;">
                                        {{ $m->departemen }}
                                    </span>
                                </label>
                            @empty
                                <div style="text-align: center; padding: var(--space-5); color: var(--text-muted);">
                                    <i class="fas fa-check-circle" style="color: var(--success); font-size: 1.8rem; display: block; margin-bottom: 8px;"></i>
                                    <strong>Semua Anggota Sudah Terdaftar</strong>
                                    <p style="margin: 4px 0 0 0; font-size: var(--font-xs);">Tidak ada lagi anggota HIMA TI yang belum memiliki divisi di event ini.</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="jabatan_panitia">Jabatan dalam Divisi</label>
                        <input type="text" id="jabatan_panitia" name="jabatan_panitia" class="form-control" placeholder="Contoh: Koordinator / Anggota" value="Anggota">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('addMemberModal')">Batal</button>
                    <button type="submit" class="btn btn-primary" id="btnSubmitMember" @if($availableMembers->isEmpty()) disabled @endif>
                        <i class="fas fa-check"></i> Tambahkan
                    </button>
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
    .member-checkbox-row:hover {
        background: var(--blue-subtle);
    }

    /* Event Header Actions on Mobile */
    .event-header-actions {
        display: flex;
        gap: var(--space-3);
        align-items: center;
        flex-wrap: wrap;
    }
    @media (max-width: 640px) {
        .event-header-actions {
            width: 100% !important;
            display: flex !important;
            flex-direction: column !important;
            gap: 8px !important;
        }
        .event-header-actions a,
        .event-header-actions button,
        .event-header-actions form,
        .event-header-actions form button {
            width: 100% !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            box-sizing: border-box !important;
        }
    }

    /* Division Header Responsive Layout */
    .division-card-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 10px;
    }
    .division-title-wrap {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .division-actions-wrap {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    @media (max-width: 576px) {
        .division-card-header {
            flex-direction: column !important;
            align-items: stretch !important;
            gap: 10px !important;
        }
        .division-title-wrap {
            justify-content: space-between !important;
            width: 100% !important;
        }
        .division-actions-wrap {
            width: 100% !important;
            display: flex !important;
            justify-content: space-between !important;
            gap: 8px !important;
        }
        .division-actions-wrap .btn-add-panitia {
            flex: 1 !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            padding: 8px 12px !important;
            font-size: 0.82rem !important;
        }
        .division-actions-wrap .btn-del-divisi {
            flex-shrink: 0 !important;
        }
    }

    /* Panitia View Switching: Desktop Table vs Mobile Cards */
    .panitia-desktop-table {
        display: block;
    }
    .panitia-mobile-cards {
        display: none;
    }

    @media (max-width: 640px) {
        .panitia-desktop-table {
            display: none !important;
        }
        .panitia-mobile-cards {
            display: flex !important;
            flex-direction: column;
            gap: 10px;
        }
    }

    .panitia-mobile-item {
        background: var(--bg-surface-subtle);
        border: 1.5px solid var(--border-color);
        border-radius: var(--radius-md);
        padding: 12px 14px;
        box-sizing: border-box;
        transition: all var(--transition-fast);
    }

    .panitia-mobile-top {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 8px;
        margin-bottom: 8px;
    }

    .panitia-mobile-name {
        font-size: 0.92rem;
        font-weight: 700;
        color: var(--text-primary);
        display: block;
        line-height: 1.25;
    }

    .panitia-mobile-email {
        font-size: 0.75rem;
        color: var(--text-muted);
        margin-top: 2px;
        word-break: break-all;
    }

    .panitia-mobile-del-btn {
        width: 30px;
        height: 30px;
        border-radius: 8px;
        background: rgba(239, 68, 68, 0.12);
        border: 1px solid rgba(239, 68, 68, 0.3);
        color: var(--danger);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.85rem;
        cursor: pointer;
        transition: all var(--transition-fast);
    }

    .panitia-mobile-del-btn:hover {
        background: var(--danger);
        color: #FFFFFF;
    }

    .panitia-mobile-badges {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 6px;
    }

    [data-theme="dark"] .panitia-mobile-item {
        background: #0D1B2A;
        border-color: rgba(48, 175, 255, 0.25);
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
    const searchInput = document.getElementById('memberSearchInput');
    if (searchInput) {
        searchInput.value = '';
        filterMemberCheckboxes();
    }
    toggleSelectAllMembers(false);
    openModal('addMemberModal');
}
function updateSelectedCount() {
    const checked = document.querySelectorAll('input[name="user_ids[]"]:checked').length;
    const counter = document.getElementById('selectedMemberCount');
    if (counter) counter.textContent = checked;
}
function toggleSelectAllMembers(selectAll) {
    const rows = document.querySelectorAll('.member-checkbox-row');
    rows.forEach(row => {
        if (row.style.display !== 'none') {
            const cb = row.querySelector('input[name="user_ids[]"]');
            if (cb) cb.checked = selectAll;
        }
    });
    updateSelectedCount();
}
function filterMemberCheckboxes() {
    const query = (document.getElementById('memberSearchInput')?.value || '').toLowerCase().trim();
    const rows = document.querySelectorAll('.member-checkbox-row');
    rows.forEach(row => {
        const name = row.getAttribute('data-name') || '';
        const dept = row.getAttribute('data-dept') || '';
        if (name.includes(query) || dept.includes(query)) {
            row.style.display = 'flex';
        } else {
            row.style.display = 'none';
        }
    });
}
</script>
@endpush
