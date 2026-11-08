<?php

namespace App\Http\Controllers;

use App\Models\Meeting;
use App\Models\Member;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $currentMonth = now()->month;
        $currentYear = now()->year;

        $totalMembers = Member::count();
        $totalRapatBulanIni = Meeting::whereMonth('tanggal', $currentMonth)
            ->whereYear('tanggal', $currentYear)
            ->where('status', 'completed')
            ->count();

        $totalSemuaRapat = Meeting::where('status', 'completed')->count();
        $activeMeetingsCount = Meeting::where('status', 'scanning')->count();

        // Average attendance percentage
        $avgAttendance = DB::table(DB::raw("(
            SELECT m.id, (SUM(CASE WHEN a.status = 'hadir' THEN 1 ELSE 0 END) * 100.0 / COUNT(*)) as hadir_pct
            FROM meetings m
            JOIN attendances a ON a.meeting_id = m.id
            WHERE m.status = 'completed'
            GROUP BY m.id
        ) as sub"))->avg('hadir_pct') ?? 0;
        $avgAttendance = round($avgAttendance, 1);

        // Recent meetings
        $recentMeetings = Meeting::with('creator')
            ->withCount([
                'attendances as total_peserta',
                'attendances as jumlah_hadir' => function ($query) {
                    $query->where('status', 'hadir');
                },
            ])
            ->orderBy('tanggal', 'desc')
            ->orderBy('waktu_mulai', 'desc')
            ->take(5)
            ->get();

        // Active scanning meetings
        $activeMeetings = Meeting::with('creator')
            ->withCount([
                'attendances as total_peserta',
                'attendances as jumlah_hadir' => function ($query) {
                    $query->where('status', 'hadir');
                },
            ])
            ->where('status', 'scanning')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('dashboard.index', compact(
            'totalMembers',
            'totalRapatBulanIni',
            'totalSemuaRapat',
            'activeMeetingsCount',
            'avgAttendance',
            'recentMeetings',
            'activeMeetings'
        ));
    }
}
