@extends('layouts.portal')

@section('title', 'Riwayat Absensi')
@section('page_icon')
    <i class="fas fa-history"></i>
@endsection

@section('content')
<div class="portal-page-header">
    <h1 class="portal-page-title">
        <i class="fas fa-history"></i> Riwayat Absensi
    </h1>
    <a href="{{ route('portal.absence.create') }}" class="btn btn-primary btn-sm">
        <i class="fas fa-paper-plane"></i> Ajukan Izin / Sakit
    </a>
</div>

<div class="stat-cards">
    <div class="stat-card stat-card-total">
        <div class="stat-icon stat-icon-blue"><i class="fas fa-calendar-check"></i></div>
        <div class="stat-info">
            <h3 class="stat-num-blue">{{ $stats['total'] }}</h3>
            <p>Total Rapat</p>
        </div>
    </div>
    <div class="stat-card stat-card-hadir">
        <div class="stat-icon stat-icon-green"><i class="fas fa-user-check"></i></div>
        <div class="stat-info">
            <h3 class="stat-num-green">{{ $stats['hadir'] }}</h3>
            <p>Hadir</p>
        </div>
    </div>
    <div class="stat-card stat-card-izin">
        <div class="stat-icon stat-icon-orange"><i class="fas fa-file-medical"></i></div>
        <div class="stat-info">
            <h3 class="stat-num-orange">{{ $stats['izin'] + $stats['sakit'] }}</h3>
            <p>Izin / Sakit</p>
        </div>
    </div>
    <div class="stat-card stat-card-alpha">
        <div class="stat-icon stat-icon-red"><i class="fas fa-user-times"></i></div>
        <div class="stat-info">
            <h3 class="stat-num-red">{{ $stats['alpha'] }}</h3>
            <p>Alpha</p>
        </div>
    </div>
</div>

<div class="filter-bar">
    <form method="GET" action="{{ route('portal.attendance') }}" style="display: flex; gap: var(--space-3); align-items: center; flex-wrap: wrap;">
        <select name="status" class="form-control" style="max-width: 220px;" onchange="this.form.submit()">
            <option value="">Semua Status</option>
            <option value="hadir" {{ $status === 'hadir' ? 'selected' : '' }}>Hadir</option>
            <option value="izin" {{ $status === 'izin' ? 'selected' : '' }}>Izin</option>
            <option value="sakit" {{ $status === 'sakit' ? 'selected' : '' }}>Sakit</option>
            <option value="alpha" {{ $status === 'alpha' ? 'selected' : '' }}>Alpha</option>
        </select>
        @if ($status)
            <a href="{{ route('portal.attendance') }}" class="btn btn-secondary btn-sm">
                <i class="fas fa-times"></i> Reset Filter
            </a>
        @endif
    </form>
    <div style="margin-left: auto;">
        <span class="badge badge-primary">{{ $stats['percentage'] }}% kehadiran</span>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-list"></i> Track Record Kehadiran</h3>
        <span class="badge badge-primary">{{ $attendances->total() }} data</span>
    </div>

    @if ($attendances->isEmpty())
        <div class="empty-state">
            <i class="fas fa-history"></i>
            <h3>Belum Ada Riwayat Absensi</h3>
            <p>Riwayat akan muncul setelah Anda mengikuti rapat yang telah diselesaikan.</p>
        </div>
    @else
        <!-- Tampilan HP (Mobile Card List) -->
        <div class="portal-cards-mobile attendance-history-list mb-4">
            @foreach ($attendances as $attendance)
                @php
                    $catLabel = match($attendance->meeting?->jenis_rapat) {
                        'internal_dept' => 'Internal ' . ($attendance->meeting?->departemen ?? 'Dept'),
                        'kepanitiaan' => 'Kepanitiaan',
                        'bph' => 'BPH / Inti',
                        'lintas_dept' => 'Lintas Dept',
                        'perdana' => 'Rapat Perdana',
                        default => $attendance->meeting?->jenis_label ?? 'Rapat',
                    };
                    $methodLabel = match($attendance->scan_method) {
                        'qr_scan' => 'Scan QR',
                        'online_link' => 'Online',
                        'absent_form' => 'Form Izin',
                        default => 'Presensi',
                    };
                @endphp
                <div class="attendance-history-item" style="flex-direction: column; align-items: stretch; gap: 4px; padding: 11px 13px;">
                    <div style="display: flex; justify-content: space-between; width: 100%; align-items: center; gap: 8px;">
                        <h4 class="attendance-history-name" style="margin: 0; font-size: 0.92rem; font-weight: 700; color: var(--text-primary); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; flex: 1;">
                            {{ $attendance->meeting?->fokus_bahasan ?? $attendance->meeting?->nama_event ?? $attendance->meeting?->jenis_label ?? '-' }}
                        </h4>
                        @if ($attendance->status === 'hadir')
                            <span class="chip-status chip-hadir"><i class="fas fa-check"></i> Hadir</span>
                        @elseif ($attendance->status === 'izin')
                            <span class="chip-status chip-izin">Izin</span>
                        @elseif ($attendance->status === 'sakit')
                            <span class="chip-status chip-sakit">Sakit</span>
                        @else
                            <span class="chip-status chip-alpha">Alpha</span>
                        @endif
                    </div>

                    <div class="attendance-history-meta" style="display: flex; align-items: center; gap: 6px; font-size: 0.74rem; color: var(--text-muted); flex-wrap: wrap; margin: 0;">
                        <span class="meeting-meta-cat">{{ $catLabel }}</span>
                        <span class="meta-dot">&bull;</span>
                        <span>{{ $attendance->meeting?->tanggal?->locale('id')->translatedFormat('d M Y') ?? '-' }}{{ $attendance->meeting ? ', ' . substr($attendance->meeting->waktu_mulai, 0, 5) . ' WITA' : '' }}</span>
                        <span class="meta-dot">&bull;</span>
                        <span>{{ $methodLabel }}</span>
                    </div>

                    @if ($attendance->keterangan)
                        <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 4px; background: var(--bg-surface-subtle); padding: 5px 8px; border-radius: 4px; width: 100%; border-left: 2px solid var(--blue-primary);">
                            <strong>Ket:</strong> {{ $attendance->keterangan }}
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        <!-- Tampilan Desktop (Table View) -->
        <div class="table-wrapper portal-table-desktop">
            <table class="table">
                <thead>
                    <tr>
                        <th>Rapat / Agenda</th>
                        <th>Tanggal</th>
                        <th>Status</th>
                        <th>Metode</th>
                        <th>Keterangan</th>
                        <th>Bukti</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($attendances as $attendance)
                        <tr>
                            <td>
                                <div style="font-weight: 600; color: var(--text-primary);">
                                    {{ $attendance->meeting?->jenis_label ?? 'Rapat' }}
                                </div>
                                <small style="color: var(--text-muted);">
                                    {{ $attendance->meeting?->fokus_bahasan ?? $attendance->meeting?->nama_event ?? '-' }}
                                </small>
                            </td>
                            <td>
                                <div style="font-weight: 500;">
                                    {{ $attendance->meeting?->tanggal?->locale('id')->translatedFormat('d M Y') ?? '-' }}
                                </div>
                                <small style="color: var(--text-muted);">
                                    {{ $attendance->meeting ? substr($attendance->meeting->waktu_mulai, 0, 5) . ' WITA' : '' }}
                                </small>
                            </td>
                            <td>
                                <span class="badge badge-{{ $attendance->status }}">{{ ucfirst($attendance->status) }}</span>
                            </td>
                            <td>
                                <small style="color: var(--text-muted);">
                                    @switch($attendance->scan_method)
                                        @case('qr_scan') Scan QR @break
                                        @case('online_link') Link Online @break
                                        @case('absent_form') Form Izin @break
                                        @default Manual @break
                                    @endswitch
                                </small>
                            </td>
                            <td>
                                <small>{{ $attendance->keterangan ?: '-' }}</small>
                            </td>
                            <td>
                                @if ($attendance->bukti_foto && file_exists(public_path('uploads/bukti/' . $attendance->bukti_foto)))
                                    <a href="{{ asset('uploads/bukti/' . $attendance->bukti_foto) }}" target="_blank" class="btn btn-sm btn-ghost" title="Lihat Bukti">
                                        <i class="fas fa-image"></i>
                                    </a>
                                @else
                                    <span style="color: var(--text-muted);">-</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $attendances->links() }}
        </div>
    @endif
</div>
@endsection
