<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
                DB::raw('ROUND(AVG(CASE WHEN a.status = "hadir" THEN 100 ELSE 0 END), 1) as avg_hadir')
            )
            ->groupBy('u.departemen')
            ->orderByDesc('avg_hadir')
            ->get();

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
                DB::raw('COUNT(DISTINCT m.id) as total_rapat'),
                DB::raw('SUM(CASE WHEN a.status = "hadir" THEN 1 ELSE 0 END) as hadir'),
                DB::raw('SUM(CASE WHEN a.status = "izin" THEN 1 ELSE 0 END) as izin'),
                DB::raw('SUM(CASE WHEN a.status = "sakit" THEN 1 ELSE 0 END) as sakit'),
                DB::raw('SUM(CASE WHEN a.status = "alpha" THEN 1 ELSE 0 END) as alpha')
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
