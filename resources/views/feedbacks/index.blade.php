@extends('layouts.app')

@section('title', 'Kotak Feedback & Masukan')

@section('page_icon')
    <i class="fas fa-comment-dots"></i>
@endsection

@push('styles')
<style>
.feedback-stat-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
    gap: var(--space-4);
    margin-bottom: var(--space-5);
}

.feedback-stat-card {
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

.feedback-stat-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}

.feedback-stat-icon {
    width: 46px;
    height: 46px;
    border-radius: var(--radius-lg);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    flex-shrink: 0;
}

.feedback-stat-icon.blue { background: rgba(37, 99, 235, 0.12); color: var(--blue-primary); }
.feedback-stat-icon.yellow { background: rgba(245, 158, 11, 0.14); color: var(--warning); }
.feedback-stat-icon.indigo { background: rgba(99, 102, 241, 0.14); color: #6366f1; }
.feedback-stat-icon.green { background: rgba(16, 185, 129, 0.14); color: var(--success); }

.feedback-stat-number {
    font-size: 1.45rem;
    font-weight: 800;
    color: var(--text-primary);
    line-height: 1.1;
}

.feedback-stat-label {
    font-size: 0.8rem;
    color: var(--text-muted);
    font-weight: 600;
    margin-top: 2px;
}

.feedback-thumb-box {
    width: 52px;
    height: 52px;
    border-radius: var(--radius-md);
    overflow: hidden;
    border: 1px solid var(--border-color);
    cursor: pointer;
    background: var(--bg-surface-subtle);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: transform var(--transition-fast);
}

.feedback-thumb-box:hover {
    transform: scale(1.06);
    border-color: var(--blue-primary);
}

.feedback-thumb-box img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

/* Modal Feedback */
.feedback-admin-modal-overlay {
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

.feedback-admin-modal-overlay.active {
    display: flex;
}

.feedback-admin-modal {
    background: var(--bg-surface);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-xl);
    width: 100%;
    max-width: 680px;
    max-height: 90vh;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    box-shadow: 0 24px 48px rgba(0, 0, 0, 0.28);
    animation: modalScaleIn 0.2s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes modalScaleIn {
    from {
        opacity: 0;
        transform: scale(0.96);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}

.feedback-admin-modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 20px;
    border-bottom: 1px solid var(--border-color);
}

.feedback-admin-modal-body {
    padding: 20px;
    overflow-y: auto;
    flex: 1;
}

.feedback-admin-modal-footer {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 10px;
    padding: 14px 20px;
    border-top: 1px solid var(--border-color);
    background: var(--bg-surface-subtle);
}
</style>
@endpush

@section('content')
<!-- Stat Cards Summary -->
<div class="feedback-stat-grid">
    <div class="feedback-stat-card">
        <div class="feedback-stat-icon blue">
            <i class="fas fa-inbox"></i>
        </div>
        <div>
            <div class="feedback-stat-number">{{ $stats['total'] }}</div>
            <div class="feedback-stat-label">Total Masukan</div>
        </div>
    </div>

    <div class="feedback-stat-card">
        <div class="feedback-stat-icon yellow">
            <i class="fas fa-clock"></i>
        </div>
        <div>
            <div class="feedback-stat-number">{{ $stats['pending'] }}</div>
            <div class="feedback-stat-label">Menunggu Tindakan</div>
        </div>
    </div>

    <div class="feedback-stat-card">
        <div class="feedback-stat-icon indigo">
            <i class="fas fa-eye"></i>
        </div>
        <div>
            <div class="feedback-stat-number">{{ $stats['read'] }}</div>
            <div class="feedback-stat-label">Sedang Ditinjau</div>
        </div>
    </div>

    <div class="feedback-stat-card">
        <div class="feedback-stat-icon green">
            <i class="fas fa-check-circle"></i>
        </div>
        <div>
            <div class="feedback-stat-number">{{ $stats['resolved'] }}</div>
            <div class="feedback-stat-label">Selesai Ditindaklanjuti</div>
        </div>
    </div>
</div>

<!-- Filter and Search Bar -->
<div class="filter-bar">
    <form method="GET" action="{{ route('feedbacks.index') }}" style="display: flex; gap: var(--space-3); flex-wrap: wrap; align-items: center; width: 100%;">
        <div class="search-input-wrapper" style="flex: 1; min-width: 220px;">
            <i class="fas fa-search"></i>
            <input type="text" name="search" class="form-control" placeholder="Cari subjek, pesan, nama pengurus, departemen..." value="{{ $search }}">
        </div>

        <select name="status" class="form-control" style="max-width: 170px;" onchange="this.form.submit()">
            <option value="">Semua Status</option>
            <option value="pending" {{ $status === 'pending' ? 'selected' : '' }}>⏳ Menunggu ({{ $stats['pending'] }})</option>
            <option value="read" {{ $status === 'read' ? 'selected' : '' }}>👁️ Ditinjau ({{ $stats['read'] }})</option>
            <option value="resolved" {{ $status === 'resolved' ? 'selected' : '' }}>✅ Selesai ({{ $stats['resolved'] }})</option>
        </select>

        <select name="kategori" class="form-control" style="max-width: 180px;" onchange="this.form.submit()">
            <option value="">Semua Kategori</option>
            <option value="bug" {{ $kategori === 'bug' ? 'selected' : '' }}>🐞 Bug / Error</option>
            <option value="saran" {{ $kategori === 'saran' ? 'selected' : '' }}>💡 Saran Fitur</option>
            <option value="pertanyaan" {{ $kategori === 'pertanyaan' ? 'selected' : '' }}>❓ Pertanyaan</option>
            <option value="lainnya" {{ $kategori === 'lainnya' ? 'selected' : '' }}>💬 Lainnya</option>
        </select>

        @if($search || $status || $kategori)
            <a href="{{ route('feedbacks.index') }}" class="btn btn-secondary" title="Reset Filter">
                <i class="fas fa-redo"></i> Reset
            </a>
        @endif
    </form>
</div>

<!-- Table Card -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-comments text-primary"></i> Daftar Feedback & Masukan Pengurus
        </h3>
        <span class="badge badge-primary">{{ $feedbacks->total() }} masukan</span>
    </div>

    @if($feedbacks->isEmpty())
        <div class="empty-state" style="padding: 48px 20px; text-align: center;">
            <div style="font-size: 3rem; color: var(--text-muted); margin-bottom: 12px;">
                <i class="fas fa-comment-slash"></i>
            </div>
            <h3 style="font-size: 1.15rem; font-weight: 700; color: var(--text-primary); margin-bottom: 6px;">Belum Ada Feedback</h3>
            <p style="font-size: 0.86rem; color: var(--text-muted); max-width: 440px; margin: 0 auto;">
                @if($search || $status || $kategori)
                    Tidak ditemukan feedback yang sesuai dengan kriteria filter pencarian Anda.
                @else
                    Kotak feedback masih kosong. Masukan yang dikirim pengurus lewat Portal akan muncul di sini.
                @endif
            </p>
        </div>
    @else
        <div class="table-wrapper">
            <table class="table">
                <thead>
                    <tr>
                        <th style="width: 140px;">Waktu</th>
                        <th style="width: 180px;">Pengirim</th>
                        <th style="width: 130px;">Kategori</th>
                        <th>Subjek & Pesan</th>
                        <th style="width: 90px; text-align: center;">Bukti SS</th>
                        <th style="width: 120px; text-align: center;">Status</th>
                        <th style="width: 130px; text-align: right;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($feedbacks as $item)
                        <tr>
                            <!-- Waktu -->
                            <td>
                                <div style="font-weight: 600; color: var(--text-primary); font-size: 0.84rem;">
                                    {{ $item->created_at ? $item->created_at->locale('id')->translatedFormat('d M Y') : '-' }}
                                </div>
                                <small style="color: var(--text-muted); font-size: 0.74rem;">
                                    <i class="far fa-clock"></i> {{ $item->created_at ? $item->created_at->locale('id')->translatedFormat('H:i') . ' WITA' : '-' }}
                                </small>
                            </td>

                            <!-- Pengirim -->
                            <td>
                                @if($item->member)
                                    <div style="font-weight: 700; color: var(--text-primary); font-size: 0.86rem;">
                                        {{ $item->member->nama_lengkap }}
                                    </div>
                                    <div style="font-size: 0.74rem; color: var(--text-muted);">
                                        {{ $item->member->departemen }} • {{ $item->member->jabatan }}
                                    </div>
                                @else
                                    <span class="badge badge-secondary" style="font-size: 0.72rem;">Pengurus (Terhapus)</span>
                                @endif
                            </td>

                            <!-- Kategori -->
                            <td>
                                @if($item->kategori === 'bug')
                                    <span class="badge badge-danger" style="display: inline-flex; align-items: center; gap: 4px; font-size: 0.72rem;">
                                        <i class="fas fa-bug"></i> Bug / Error
                                    </span>
                                @elseif($item->kategori === 'saran')
                                    <span class="badge badge-warning" style="display: inline-flex; align-items: center; gap: 4px; font-size: 0.72rem;">
                                        <i class="fas fa-lightbulb"></i> Saran Fitur
                                    </span>
                                @elseif($item->kategori === 'pertanyaan')
                                    <span class="badge badge-info" style="display: inline-flex; align-items: center; gap: 4px; font-size: 0.72rem;">
                                        <i class="fas fa-question-circle"></i> Pertanyaan
                                    </span>
                                @else
                                    <span class="badge badge-secondary" style="display: inline-flex; align-items: center; gap: 4px; font-size: 0.72rem;">
                                        <i class="fas fa-comment"></i> Lainnya
                                    </span>
                                @endif
                            </td>

                            <!-- Subjek & Pesan -->
                            <td>
                                <div style="font-weight: 700; color: var(--text-primary); font-size: 0.88rem; margin-bottom: 2px;">
                                    {{ $item->subjek }}
                                </div>
                                <div style="color: var(--text-secondary); font-size: 0.8rem; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                    {{ $item->pesan }}
                                </div>
                                @if($item->admin_notes)
                                    <div style="margin-top: 4px; font-size: 0.74rem; color: var(--blue-primary); display: flex; align-items: center; gap: 4px;">
                                        <i class="fas fa-reply"></i> Balasan: {{ Str::limit($item->admin_notes, 60) }}
                                    </div>
                                @endif
                            </td>

                            <!-- Bukti Screenshot -->
                            <td style="text-align: center;">
                                @if($item->screenshot_url)
                                    <div class="feedback-thumb-box" onclick="openAdminDetailModal({{ json_encode([
                                        'id' => $item->id,
                                        'subjek' => $item->subjek,
                                        'pesan' => $item->pesan,
                                        'kategori' => $item->kategori_label,
                                        'status' => $item->status,
                                        'pengirim' => $item->member ? $item->member->nama_lengkap . ' (' . $item->member->departemen . ')' : 'Pengurus',
                                        'waktu' => $item->created_at ? $item->created_at->locale('id')->translatedFormat('d M Y, H:i') . ' WITA' : '-',
                                        'screenshot_url' => $item->screenshot_url,
                                        'admin_notes' => $item->admin_notes ?? '',
                                        'update_url' => route('feedbacks.update', $item),
                                    ]) }})" title="Klik untuk lihat screenshot">
                                        <img src="{{ $item->screenshot_url }}" alt="Screenshot">
                                    </div>
                                @else
                                    <span style="color: var(--text-muted); font-size: 0.76rem;">—</span>
                                @endif
                            </td>

                            <!-- Status -->
                            <td style="text-align: center;">
                                @if($item->status === 'resolved')
                                    <span class="badge badge-success" style="font-size: 0.72rem; padding: 4px 8px;">
                                        <i class="fas fa-check-circle"></i> Selesai
                                    </span>
                                @elseif($item->status === 'read')
                                    <span class="badge badge-info" style="font-size: 0.72rem; padding: 4px 8px;">
                                        <i class="fas fa-eye"></i> Ditinjau
                                    </span>
                                @else
                                    <span class="badge badge-warning" style="font-size: 0.72rem; padding: 4px 8px;">
                                        <i class="fas fa-clock"></i> Menunggu
                                    </span>
                                @endif
                            </td>

                            <!-- Aksi -->
                            <td style="text-align: right;">
                                <div style="display: inline-flex; align-items: center; gap: 6px;">
                                    <button type="button" class="btn btn-sm btn-primary" onclick="openAdminDetailModal({{ json_encode([
                                        'id' => $item->id,
                                        'subjek' => $item->subjek,
                                        'pesan' => $item->pesan,
                                        'kategori' => $item->kategori_label,
                                        'status' => $item->status,
                                        'pengirim' => $item->member ? $item->member->nama_lengkap . ' (' . $item->member->departemen . ')' : 'Pengurus',
                                        'waktu' => $item->created_at ? $item->created_at->locale('id')->translatedFormat('d M Y, H:i') . ' WITA' : '-',
                                        'screenshot_url' => $item->screenshot_url,
                                        'admin_notes' => $item->admin_notes ?? '',
                                        'update_url' => route('feedbacks.update', $item),
                                    ]) }})" title="Tindak Lanjuti Feedback">
                                        <i class="fas fa-edit"></i>
                                    </button>

                                    <form action="{{ route('feedbacks.destroy', $item) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus data feedback ini?');" style="margin: 0; display: inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" title="Hapus Feedback">
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

        @if($feedbacks->hasPages())
            <div style="padding: 16px 20px; border-top: 1px solid var(--border-color);">
                {{ $feedbacks->links() }}
            </div>
        @endif
    @endif
</div>

<!-- Modal Detail & Tindak Lanjut Feedback (Admin) -->
<div class="feedback-admin-modal-overlay" id="feedbackAdminModal" onclick="closeAdminDetailModal(event)">
    <div class="feedback-admin-modal" onclick="event.stopPropagation()">
        <div class="feedback-admin-modal-header">
            <h3 style="margin: 0; font-size: 1.05rem; font-weight: 700; color: var(--text-primary); display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-comment-dots text-primary"></i> Detail & Tindak Lanjut Feedback
            </h3>
            <button type="button" onclick="closeAdminDetailModalDirect()" style="background: none; border: none; font-size: 1.4rem; color: var(--text-muted); cursor: pointer; line-height: 1;">&times;</button>
        </div>

        <form id="feedbackUpdateForm" method="POST" action="">
            @csrf
            @method('PUT')

            <div class="feedback-admin-modal-body">
                <!-- Meta Info Pengirim -->
                <div style="background: var(--bg-surface-subtle); border-radius: var(--radius-lg); padding: 12px 14px; margin-bottom: 14px; border: 1px solid var(--border-color); display: flex; justify-content: space-between; flex-wrap: wrap; gap: 8px;">
                    <div>
                        <div style="font-size: 0.72rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700;">Pengirim</div>
                        <div style="font-size: 0.88rem; font-weight: 700; color: var(--text-primary);" id="modalPengirim">-</div>
                    </div>
                    <div>
                        <div style="font-size: 0.72rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700;">Kategori</div>
                        <div style="font-size: 0.86rem; font-weight: 600; color: var(--blue-primary);" id="modalKategori">-</div>
                    </div>
                    <div>
                        <div style="font-size: 0.72rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700;">Waktu Dikirim</div>
                        <div style="font-size: 0.84rem; color: var(--text-secondary);" id="modalWaktu">-</div>
                    </div>
                </div>

                <!-- Subjek -->
                <div style="margin-bottom: 12px;">
                    <label style="font-size: 0.78rem; font-weight: 700; color: var(--text-secondary); text-transform: uppercase;">Subjek</label>
                    <div style="font-size: 1rem; font-weight: 700; color: var(--text-primary); margin-top: 2px;" id="modalSubjek">-</div>
                </div>

                <!-- Pesan -->
                <div style="margin-bottom: 16px;">
                    <label style="font-size: 0.78rem; font-weight: 700; color: var(--text-secondary); text-transform: uppercase;">Pesan & Kendala</label>
                    <div style="font-size: 0.88rem; color: var(--text-secondary); line-height: 1.5; background: var(--bg-surface-subtle); padding: 12px 14px; border-radius: var(--radius-md); border: 1px solid var(--border-color); white-space: pre-line; margin-top: 4px;" id="modalPesan">-</div>
                </div>

                <!-- Bukti Screenshot -->
                <div id="modalScreenshotWrapper" style="margin-bottom: 16px; display: none;">
                    <label style="font-size: 0.78rem; font-weight: 700; color: var(--text-secondary); text-transform: uppercase; display: flex; justify-content: space-between; align-items: center;">
                        <span>Lampiran Screenshot</span>
                        <a id="modalScreenshotLink" href="#" target="_blank" class="btn-chip btn-chip-blue" style="font-size: 0.72rem; padding: 2px 8px; text-decoration: none;">
                            <i class="fas fa-external-link-alt"></i> Buka Gambar Penuh
                        </a>
                    </label>
                    <div style="background: #0b0f19; border-radius: var(--radius-lg); padding: 10px; text-align: center; margin-top: 4px; border: 1px solid var(--border-color);">
                        <img id="modalScreenshotImg" src="" alt="Screenshot Bukti" style="max-width: 100%; max-height: 320px; object-fit: contain; border-radius: var(--radius-md);">
                    </div>
                </div>

                <hr style="border: none; border-top: 1px solid var(--border-color); margin: 16px 0;">

                <!-- Form Update Status & Balasan Admin -->
                <div style="margin-bottom: 12px;">
                    <label for="modalStatusSelect" style="font-size: 0.82rem; font-weight: 700; color: var(--text-primary); margin-bottom: 6px; display: block;">
                        Ubah Status Tindak Lanjut <span class="text-danger">*</span>
                    </label>
                    <select name="status" id="modalStatusSelect" class="form-control" required style="font-weight: 600;">
                        <option value="pending">⏳ Menunggu (Belum Ditindaklanjuti)</option>
                        <option value="read">👁️ Ditinjau (Sedang Dipelajari / Dikerjakan)</option>
                        <option value="resolved">✅ Selesai (Sudah Diperbaiki / Dijawab)</option>
                    </select>
                </div>

                <div class="form-group" style="margin-bottom: 8px;">
                    <label for="modalAdminNotes" style="font-size: 0.82rem; font-weight: 700; color: var(--text-primary); margin-bottom: 6px; display: block;">
                        Tanggapan / Catatan Admin <span style="font-weight: 400; color: var(--text-muted); font-size: 0.75rem;">(Bisa dilihat oleh pengurus di Profil)</span>
                    </label>
                    <textarea name="admin_notes" id="modalAdminNotes" class="form-control" rows="3" placeholder="Tuliskan tanggapan atau catatan penyelesaian (misal: 'Terima kasih, bug kamera sudah diperbaiki pada rilis v1.2')..."></textarea>
                </div>
            </div>

            <div class="feedback-admin-modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeAdminDetailModalDirect()">Tutup</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
function openAdminDetailModal(data) {
    const modal = document.getElementById('feedbackAdminModal');
    const form = document.getElementById('feedbackUpdateForm');
    
    document.getElementById('modalPengirim').textContent = data.pengirim || 'Pengurus';
    document.getElementById('modalKategori').textContent = data.kategori || 'Feedback';
    document.getElementById('modalWaktu').textContent = data.waktu || '-';
    document.getElementById('modalSubjek').textContent = data.subjek || '-';
    document.getElementById('modalPesan').textContent = data.pesan || '-';
    document.getElementById('modalStatusSelect').value = data.status || 'pending';
    document.getElementById('modalAdminNotes').value = data.admin_notes || '';

    // Screenshot handling
    const ssWrapper = document.getElementById('modalScreenshotWrapper');
    const ssImg = document.getElementById('modalScreenshotImg');
    const ssLink = document.getElementById('modalScreenshotLink');

    if (data.screenshot_url) {
        ssImg.src = data.screenshot_url;
        ssLink.href = data.screenshot_url;
        ssWrapper.style.display = 'block';
    } else {
        ssImg.src = '';
        ssLink.href = '#';
        ssWrapper.style.display = 'none';
    }

    form.action = data.update_url;
    modal.classList.add('active');
}

function closeAdminDetailModalDirect() {
    const modal = document.getElementById('feedbackAdminModal');
    if (modal) modal.classList.remove('active');
}

function closeAdminDetailModal(event) {
    if (event.target.id === 'feedbackAdminModal') {
        closeAdminDetailModalDirect();
    }
}
</script>
@endpush
