@extends('layouts.app')

@section('title', 'Kelola Rapat')

@section('page_icon')
    <i class="fas fa-clipboard-list"></i>
@endsection

@section('content')
<!-- Filter and Search -->
<div class="filter-bar">
    <form method="GET" action="{{ route('meetings.index') }}" class="search-input-wrapper">
        <i class="fas fa-search"></i>
        <input type="text" name="q" class="form-control" placeholder="Cari agenda atau nama rapat..." value="{{ $search }}">
        @if($status)
            <input type="hidden" name="status" value="{{ $status }}">
        @endif
    </form>
    
    <select class="form-control" style="max-width: 180px;" onchange="location.href='{{ route('meetings.index') }}?status=' + this.value + '{{ $search ? '&q=' . urlencode($search) : '' }}'">
        <option value="">Semua Status</option>
        <option value="scanning" {{ $status === 'scanning' ? 'selected' : '' }}>Scanning (Aktif)</option>
        <option value="completed" {{ $status === 'completed' ? 'selected' : '' }}>Selesai</option>
        <option value="draft" {{ $status === 'draft' ? 'selected' : '' }}>Draft</option>
        <option value="cancelled" {{ $status === 'cancelled' ? 'selected' : '' }}>Dibatalkan</option>
    </select>
    
    <a href="{{ route('meetings.create') }}" class="btn btn-primary">
        <i class="fas fa-plus"></i> Buat Rapat Baru
    </a>
</div>

<!-- Meetings Card -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-clipboard-list"></i> Daftar Rapat HIMA TI</h3>
        <span class="badge badge-primary">{{ $meetings->total() }} rapat</span>
    </div>
    
    @if ($meetings->isEmpty())
        <div class="empty-state">
            <i class="fas fa-calendar-times"></i>
            <h3>Tidak Ada Rapat</h3>
            <p>Belum ada rapat yang tersimpan sesuai filter pencarian.</p>
            <a href="{{ route('meetings.create') }}" class="btn btn-primary mt-4">
                <i class="fas fa-plus"></i> Buat Rapat Sekarang
            </a>
        </div>
    @else
        <div class="table-wrapper">
            <table class="table">
                <thead>
                    <tr>
                        <th>Tanggal & Waktu</th>
                        <th>Jenis Rapat</th>
                        <th>Agenda / Pembahasan</th>
                        <th>Pembuat</th>
                        <th>Kehadiran</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($meetings as $m)
                    <tr>
                        <td>
                            <div style="font-weight: 600;">{{ $m->tanggal->format('d M Y') }}</div>
                            <small style="color: var(--text-muted);">{{ substr($m->waktu_mulai, 0, 5) }} WITA</small>
                        </td>
                        <td>
                            <span class="badge badge-primary">{{ $m->jenis_label }}</span>
                            @if($m->mode_rapat === 'online')
                                <span class="badge badge-info" style="font-size: 10px; margin-left: 4px;">Online</span>
                            @endif
                        </td>
                        <td>
                            <div style="font-weight: 500;">
                                {{ $m->fokus_bahasan ?? ($m->nama_event ?? '-') }}
                            </div>
                            @if($m->divisi_rapat)
                                <small style="color: var(--text-muted);">Divisi: {{ $m->divisi_rapat }}</small>
                            @endif
                        </td>
                        <td>{{ $m->creator ? $m->creator->nama_lengkap : 'Admin' }}</td>
                        <td>
                            <strong style="color: var(--success);">{{ $m->jumlah_hadir }}</strong> / {{ $m->total_peserta }}
                        </td>
                        <td>
                            @if($m->status === 'scanning')
                                <span class="badge badge-primary"><i class="fas fa-qrcode"></i> Scanning</span>
                            @elseif($m->status === 'completed')
                                <span class="badge badge-success"><i class="fas fa-check"></i> Selesai</span>
                            @elseif($m->status === 'draft')
                                <span class="badge badge-warning"><i class="fas fa-edit"></i> Draft</span>
                            @else
                                <span class="badge badge-danger"><i class="fas fa-ban"></i> Batal</span>
                            @endif
                        </td>
                        <td>
                            <div style="display: flex; gap: var(--space-2);">
                                <a href="{{ route('meetings.show', $m->id) }}" class="btn btn-ghost btn-sm" title="Detail Rapat">
                                    <i class="fas fa-eye"></i>
                                </a>
                                @if($m->status === 'scanning')
                                <a href="{{ route('meetings.scan', $m->id) }}" class="btn btn-sm btn-primary" title="Buka Scanner">
                                    <i class="fas fa-camera"></i>
                                </a>
                                @endif
                                <form action="{{ route('meetings.destroy', $m->id) }}" method="POST" onsubmit="return confirm('Hapus rapat ini beserta data kehadirannya?')" style="margin: 0;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-ghost btn-sm text-danger" title="Hapus Rapat">
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
            {{ $meetings->links() }}
        </div>
    @endif
</div>
@endsection
