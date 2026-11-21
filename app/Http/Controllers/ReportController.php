<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $filterMonth = (int) $request->query('month', now()->month);
        $filterYear = (int) $request->query('year', now()->year);
        $filterDept = $request->query('dept', '');

        $departments = config('himati.departments', []);

        // Department attendance summary
        $deptSummary = DB::table('users as u')
            ->leftJoin('attendances as a', 'a.user_id', '=', 'u.id')
            ->leftJoin('meetings as m', function ($join) use ($filterMonth, $filterYear) {
                $join->on('a.meeting_id', '=', 'm.id')
                    ->whereMonth('m.tanggal', '=', $filterMonth)
                    ->whereYear('m.tanggal', '=', $filterYear)
                    ->where('m.status', '=', 'completed');
            })
            ->select(
                'u.departemen',
                DB::raw('COUNT(DISTINCT u.id) as total_anggota'),
                DB::raw('COALESCE(ROUND(AVG(CASE WHEN m.id IS NOT NULL THEN CASE WHEN a.status = "hadir" THEN 100 ELSE 0 END ELSE NULL END), 1), 0) as avg_hadir')
            )
            ->groupBy('u.departemen')
            ->orderByDesc('avg_hadir')
            ->get();

        // Defensive check: pastikan kolom sifat_kegiatan sudah ada atau jalankan migrasi jika belum
        $hasSifat = false;
        try {
            $hasSifat = Schema::hasColumn('meetings', 'sifat_kegiatan');
            if (! $hasSifat) {
                Artisan::call('migrate', ['--force' => true]);
                $hasSifat = Schema::hasColumn('meetings', 'sifat_kegiatan');
            }
        } catch (\Throwable $e) {
            $hasSifat = false;
        }

        $sifatCondition = $hasSifat ? ' AND (m.sifat_kegiatan IS NULL OR m.sifat_kegiatan != "dibebaskan")' : '';
        $sifatDibebaskanCondition = $hasSifat ? ' OR m.sifat_kegiatan = "dibebaskan"' : '';

        // Individual attendance recap
        $query = DB::table('users as u')
            ->leftJoin('attendances as a', 'a.user_id', '=', 'u.id')
            ->leftJoin('meetings as m', function ($join) use ($filterMonth, $filterYear) {
                $join->on('a.meeting_id', '=', 'm.id')
                    ->whereMonth('m.tanggal', '=', $filterMonth)
                    ->whereYear('m.tanggal', '=', $filterYear)
                    ->where('m.status', '=', 'completed');
            })
            ->select(
                'u.id',
                'u.nama_lengkap',
                'u.departemen',
                'u.jabatan',
                DB::raw('COUNT(DISTINCT CASE WHEN m.id IS NOT NULL AND (a.status IN ("hadir", "izin", "sakit") OR (a.status = "alpha" AND (a.sp_ignored = 0 OR a.sp_ignored IS NULL)'.$sifatCondition.')) THEN m.id ELSE NULL END) as total_rapat'),
                DB::raw('SUM(CASE WHEN m.id IS NOT NULL AND a.status = "hadir" THEN 1 ELSE 0 END) as hadir'),
                DB::raw('SUM(CASE WHEN m.id IS NOT NULL AND a.status = "izin" THEN 1 ELSE 0 END) as izin'),
                DB::raw('SUM(CASE WHEN m.id IS NOT NULL AND a.status = "sakit" THEN 1 ELSE 0 END) as sakit'),
                DB::raw('SUM(CASE WHEN m.id IS NOT NULL AND a.status = "alpha" AND (a.sp_ignored = 0 OR a.sp_ignored IS NULL)'.$sifatCondition.' THEN 1 ELSE 0 END) as alpha'),
                DB::raw('SUM(CASE WHEN m.id IS NOT NULL AND a.status = "alpha" AND (a.sp_ignored = 0 OR a.sp_ignored IS NULL)'.$sifatCondition.' THEN 1 ELSE 0 END) as alpha_wajib'),
                DB::raw('SUM(CASE WHEN m.id IS NOT NULL AND a.status = "alpha" AND (a.sp_ignored = 1'.$sifatDibebaskanCondition.') THEN 1 ELSE 0 END) as alpha_dibebaskan')
            );

        if ($filterDept) {
            if ($filterDept === 'BPH') {
                $query->where(function ($q) {
                    $q->where('u.departemen', 'Inti')
                        ->orWhere('u.is_bph', true);
                });
            } else {
                $query->where('u.departemen', $filterDept);
            }
        }

        $reportData = $query->groupBy('u.id', 'u.nama_lengkap', 'u.departemen', 'u.jabatan')
            ->orderBy('u.departemen')
            ->orderBy('u.nama_lengkap')
            ->get();

        $bulanList = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        return view('reports.index', compact(
            'filterMonth',
            'filterYear',
            'filterDept',
            'departments',
            'deptSummary',
            'reportData',
            'bulanList'
        ));
    }
}
