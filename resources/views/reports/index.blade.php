@extends('layouts.app')

@section('title', 'Laporan Absensi')

@section('page_icon')
    <i class="fas fa-chart-bar"></i>
@endsection

@section('content')
<!-- Filter Bar -->
<div class="filter-bar">
    <form method="GET" action="{{ route('reports.index') }}" style="display: flex; gap: var(--space-3); flex-wrap: wrap; align-items: center; width: 100%;">
        <select name="month" class="form-control" style="max-width: 160px;" onchange="this.form.submit()">
            @foreach ($bulanList as $m => $namaBulan)
                <option value="{{ $m }}" {{ $filterMonth == $m ? 'selected' : '' }}>{{ $namaBulan }}</option>
            @endforeach
        </select>
        
        <select name="year" class="form-control" style="max-width: 120px;" onchange="this.form.submit()">
            @for ($y = date('Y'); $y >= date('Y') - 2; $y--)
                <option value="{{ $y }}" {{ $filterYear == $y ? 'selected' : '' }}>{{ $y }}</option>
            @endfor
        </select>
        
        <select name="dept" class="form-control" style="max-width: 220px;" onchange="this.form.submit()">
            <option value="">Semua Departemen</option>
            @foreach ($departments as $key => $label)
                <option value="{{ $key }}" {{ $filterDept == $key ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>

        <button type="button" class="btn btn-secondary" onclick="window.print()" style="margin-left: auto;">
            <i class="fas fa-print"></i> Cetak Laporan
        </button>
    </form>
</div>

<!-- Department Summary Cards -->
<div class="stat-cards mb-6">
    @foreach ($deptSummary as $ds)
    <div class="stat-card">
        <div class="stat-icon blue"><i class="fas fa-building"></i></div>
        <div class="stat-info">
            <h3>{{ data_get($ds, 'avg_hadir', 0) }}%</h3>
            <p>{{ data_get($ds, 'departemen', '-') }} • {{ data_get($ds, 'total_anggota', 0) }} orang</p>
        </div>
    </div>
    @endforeach
</div>

<!-- Recap Table Card -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-table"></i> Rekapitulasi Presensi Anggota — {{ $bulanList[$filterMonth] }} {{ $filterYear }}
        </h3>
        <span class="badge badge-primary">{{ $reportData->count() }} pengurus</span>
    </div>
    
    <div class="table-wrapper">
        <table class="table">
            <thead>
                <tr>
                    <th>Nama Anggota</th>
                    <th>Departemen</th>
                    <th>Total Rapat</th>
                    <th>Hadir</th>
                    <th>Izin</th>
                    <th>Sakit</th>
                    <th>Alpha</th>
                    <th>Tingkat Kehadiran</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($reportData as $row)
                @php
                    $pct = $row->total_rapat > 0 ? round(($row->hadir / $row->total_rapat) * 100) : 0;
                @endphp
                <tr>
                    <td>
                        <strong>{{ $row->nama_lengkap }}</strong>
                        <div style="font-size: var(--font-xs); color: var(--text-muted);">{{ $row->jabatan }}</div>
                    </td>
                    <td><span class="badge badge-dept">{{ $row->departemen }}</span></td>
                    <td><strong>{{ $row->total_rapat }}</strong></td>
                    <td><span class="badge badge-hadir">{{ $row->hadir }}</span></td>
                    <td><span class="badge badge-izin">{{ $row->izin }}</span></td>
                    <td><span class="badge badge-sakit">{{ $row->sakit }}</span></td>
                    <td><span class="badge badge-alpha">{{ $row->alpha }}</span></td>
                    <td>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <div style="flex: 1; height: 8px; background: var(--dark-surface-2); border-radius: 4px; overflow: hidden; min-width: 60px;">
                                <div style="width: {{ $pct }}%; height: 100%; background: {{ $pct >= 75 ? 'var(--success)' : ($pct >= 50 ? 'var(--warning)' : 'var(--danger)') }};"></div>
                            </div>
                            <strong style="font-size: var(--font-xs); min-width: 35px;">{{ $pct }}%</strong>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
