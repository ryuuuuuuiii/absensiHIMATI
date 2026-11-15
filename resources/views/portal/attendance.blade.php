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

<!-- Diagram Track Record Kehadiran -->
<div class="card mb-6" style="padding: 18px 20px;">
    <div class="card-header" style="padding-bottom: 12px; margin-bottom: 14px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px;">
        <div>
            <h3 class="card-title" style="margin: 0; font-size: 1.05rem; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-chart-pie" style="color: #38BDF8;"></i>
                <span>Diagram Track Record Kehadiran</span>
            </h3>
            <p style="margin: 2px 0 0 0; font-size: 0.78rem; color: var(--text-muted);">
                Visualisasi rasio status kehadiran dan statistik per kategori rapat Anda
            </p>
        </div>
        <span class="badge" style="background: rgba(56, 189, 248, 0.15); color: #38BDF8; font-weight: 700; font-size: 0.8rem; padding: 4px 10px;">
            <i class="fas fa-bullseye"></i> {{ $stats['percentage'] }}% Kedisiplinan
        </span>
    </div>

    @if($stats['total'] === 0)
        <div style="text-align: center; padding: 24px 16px; color: var(--text-muted); font-size: 0.85rem;">
            <i class="fas fa-chart-simple" style="font-size: 2rem; color: var(--text-muted); opacity: 0.5; margin-bottom: 8px; display: block;"></i>
            Belum ada data presensi rapat untuk menampilkan diagram track record.
        </div>
    @else
        <div class="track-record-grid">
            <!-- Donut Chart & Status Breakdown -->
            <div class="track-record-donut-box">
                <div style="font-size: 0.82rem; font-weight: 700; color: var(--text-primary); margin-bottom: 12px; display: flex; align-items: center; gap: 6px;">
                    <i class="fas fa-chart-donut" style="color: #10B981;"></i> Rasio Status Presensi
                </div>

                <div style="display: flex; align-items: center; justify-content: center; gap: 20px; flex-wrap: wrap;">
                    <div style="position: relative; width: 140px; height: 140px; flex-shrink: 0;">
                        <canvas id="attendanceStatusChart" width="140" height="140"></canvas>
                        <div class="donut-center-text">
                            <span class="donut-pct">{{ $stats['percentage'] }}%</span>
                            <span class="donut-label">Hadir</span>
                        </div>
                    </div>

                    <div class="track-record-legend-list">
                        <div class="track-legend-item">
                            <span class="legend-dot" style="background: #10B981;"></span>
                            <span class="legend-title">Hadir</span>
                            <strong class="legend-val">{{ $stats['hadir'] }}</strong>
                            <small class="legend-pct">({{ $stats['total'] > 0 ? round(($stats['hadir'] / $stats['total']) * 100) : 0 }}%)</small>
                        </div>
                        <div class="track-legend-item">
                            <span class="legend-dot" style="background: #38BDF8;"></span>
                            <span class="legend-title">Izin</span>
                            <strong class="legend-val">{{ $stats['izin'] }}</strong>
                            <small class="legend-pct">({{ $stats['total'] > 0 ? round(($stats['izin'] / $stats['total']) * 100) : 0 }}%)</small>
                        </div>
                        <div class="track-legend-item">
                            <span class="legend-dot" style="background: #F59E0B;"></span>
                            <span class="legend-title">Sakit</span>
                            <strong class="legend-val">{{ $stats['sakit'] }}</strong>
                            <small class="legend-pct">({{ $stats['total'] > 0 ? round(($stats['sakit'] / $stats['total']) * 100) : 0 }}%)</small>
                        </div>
                        <div class="track-legend-item">
                            <span class="legend-dot" style="background: #EF4444;"></span>
                            <span class="legend-title">Alpha</span>
                            <strong class="legend-val">{{ $stats['alpha'] }}</strong>
                            <small class="legend-pct">({{ $stats['total'] > 0 ? round(($stats['alpha'] / $stats['total']) * 100) : 0 }}%)</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Category Breakdown Bars -->
            <div class="track-record-cats-box">
                <div style="font-size: 0.82rem; font-weight: 700; color: var(--text-primary); margin-bottom: 12px; display: flex; align-items: center; justify-content: space-between;">
                    <span style="display: flex; align-items: center; gap: 6px;">
                        <i class="fas fa-layer-group" style="color: #6366F1;"></i> Distribusi per Kategori Rapat
                    </span>
                    <small style="color: var(--text-muted); font-size: 0.72rem;">{{ $categoryStats->count() }} Jenis Pertemuan</small>
                </div>

                @if($categoryStats->isEmpty())
                    <p style="font-size: 0.75rem; color: var(--text-muted); font-style: italic;">
                        Belum ada data kategori rapat yang selesai diikuti.
                    </p>
                @else
                    <div style="display: flex; flex-direction: column; gap: 10px;">
                        @foreach($categoryStats as $cat)
                            <div class="cat-stat-row">
                                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 4px; font-size: 0.78rem;">
                                    <span style="font-weight: 700; color: var(--text-primary); display: flex; align-items: center; gap: 6px;">
                                        <i class="fas {{ $cat->icon }}" style="color: #38BDF8; font-size: 0.85rem; width: 14px;"></i>
                                        {{ $cat->label }}
                                    </span>
                                    <div style="display: flex; align-items: center; gap: 6px;">
                                        <span style="font-size: 0.72rem; color: var(--text-muted);">
                                            {{ $cat->hadir }}/{{ $cat->total }} Rapat
                                        </span>
                                        <span class="badge" style="font-size: 0.68rem; padding: 1px 6px; background: {{ $cat->pct >= 75 ? 'rgba(16,185,129,0.15)' : ($cat->pct >= 50 ? 'rgba(245,158,11,0.15)' : 'rgba(239,68,68,0.15)') }}; color: {{ $cat->pct >= 75 ? '#10B981' : ($cat->pct >= 50 ? '#F59E0B' : '#EF4444') }}; font-weight: 700;">
                                            {{ $cat->pct }}%
                                        </span>
                                    </div>
                                </div>
                                <div class="cat-bar-track">
                                    @php
                                        $hadirPct = $cat->total > 0 ? ($cat->hadir / $cat->total) * 100 : 0;
                                        $izinPct = $cat->total > 0 ? (($cat->izin + $cat->sakit) / $cat->total) * 100 : 0;
                                        $alphaPct = $cat->total > 0 ? ($cat->alpha / $cat->total) * 100 : 0;
                                    @endphp
                                    <div class="cat-bar-segment" style="width: {{ $hadirPct }}%; background: #10B981;" title="Hadir: {{ $cat->hadir }}"></div>
                                    <div class="cat-bar-segment" style="width: {{ $izinPct }}%; background: #38BDF8;" title="Izin/Sakit: {{ $cat->izin + $cat->sakit }}"></div>
                                    <div class="cat-bar-segment" style="width: {{ $alphaPct }}%; background: #EF4444;" title="Alpha: {{ $cat->alpha }}"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    @endif
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
                        'foto_kegiatan' => 'Foto Kegiatan',
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
                                        @case('foto_kegiatan') Foto Kegiatan @break
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

@push('styles')
<style>
.track-record-grid {
    display: grid;
    grid-template-columns: 1fr 1.35fr;
    gap: 18px;
    align-items: stretch;
}
.track-record-donut-box,
.track-record-cats-box {
    background: var(--bg-surface-subtle);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-lg);
    padding: 14px 16px;
}
.donut-center-text {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    text-align: center;
    pointer-events: none;
    line-height: 1.1;
}
.donut-pct {
    display: block;
    font-size: 1.15rem;
    font-weight: 800;
    color: var(--text-primary);
}
.donut-label {
    display: block;
    font-size: 0.65rem;
    font-weight: 700;
    text-transform: uppercase;
    color: var(--text-muted);
    letter-spacing: 0.05em;
    margin-top: 1px;
}
.track-record-legend-list {
    display: flex;
    flex-direction: column;
    gap: 6px;
    min-width: 130px;
}
.track-legend-item {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.75rem;
}
.legend-dot {
    width: 9px;
    height: 9px;
    border-radius: 50%;
    flex-shrink: 0;
}
.legend-title {
    color: var(--text-muted);
    flex: 1;
}
.legend-val {
    color: var(--text-primary);
    font-weight: 700;
}
.legend-pct {
    color: var(--text-muted);
    font-size: 0.68rem;
}
.cat-stat-row {
    background: var(--bg-surface);
    border: 1px solid var(--border-color);
    border-radius: 8px;
    padding: 8px 10px;
}
.cat-bar-track {
    height: 7px;
    border-radius: 9999px;
    background: var(--bg-surface-subtle);
    overflow: hidden;
    display: flex;
    border: 1px solid var(--border-color);
}
.cat-bar-segment {
    height: 100%;
    transition: width 0.4s ease;
}

[data-theme="dark"] .track-record-donut-box,
[data-theme="dark"] .track-record-cats-box {
    background: #0E1A2B !important;
    border-color: #1A2E47 !important;
}
[data-theme="dark"] .cat-stat-row {
    background: #091322 !important;
    border-color: #1A2E47 !important;
}
[data-theme="dark"] .cat-bar-track {
    background: #050B14 !important;
    border-color: #1A2E47 !important;
}

@media (max-width: 768px) {
    .track-record-grid {
        grid-template-columns: 1fr !important;
        gap: 14px;
    }
}
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(function() {
    let statusChartInstance = null;

    function renderStatusChart() {
        const canvas = document.getElementById('attendanceStatusChart');
        if (!canvas) return;

        const hadir = {{ (int) $stats['hadir'] }};
        const izin = {{ (int) $stats['izin'] }};
        const sakit = {{ (int) $stats['sakit'] }};
        const alpha = {{ (int) $stats['alpha'] }};
        const total = {{ (int) $stats['total'] }};

        if (total === 0) return;

        if (typeof Chart === 'undefined') {
            const ctx = canvas.getContext('2d');
            if (ctx) {
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                ctx.beginPath();
                ctx.arc(70, 70, 55, 0, 2 * Math.PI);
                ctx.strokeStyle = '#10B981';
                ctx.lineWidth = 14;
                ctx.stroke();
            }
            return;
        }

        if (statusChartInstance) {
            statusChartInstance.destroy();
            statusChartInstance = null;
        }

        const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
        const borderColor = isDark ? '#0E1A2B' : '#FFFFFF';

        statusChartInstance = new Chart(canvas, {
            type: 'doughnut',
            data: {
                labels: ['Hadir', 'Izin', 'Sakit', 'Alpha'],
                datasets: [{
                    data: [hadir, izin, sakit, alpha],
                    backgroundColor: ['#10B981', '#38BDF8', '#F59E0B', '#EF4444'],
                    borderWidth: 2,
                    borderColor: borderColor,
                    hoverOffset: 4,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '72%',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(ctx) {
                                const val = ctx.raw || 0;
                                const pct = total > 0 ? Math.round((val / total) * 100) : 0;
                                return ` ${ctx.label}: ${val} (${pct}%)`;
                            }
                        }
                    }
                }
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', renderStatusChart);
    } else {
        renderStatusChart();
    }
    document.addEventListener('portal:page-loaded', renderStatusChart);
})();
</script>
@endpush
