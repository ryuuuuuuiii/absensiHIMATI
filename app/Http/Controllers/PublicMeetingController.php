<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Event;
use App\Models\Meeting;
use App\Models\Member;
use App\Models\Setting;
use App\Services\GoogleSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PublicMeetingController extends Controller
{
    protected GoogleSyncService $syncService;

    public function __construct(GoogleSyncService $syncService)
    {
        $this->syncService = $syncService;
    }

    public function index()
    {
        $allMembers = Member::orderBy('departemen')->orderBy('nama_lengkap')->get();
        $usersByDept = $allMembers->groupBy('departemen');
        $departments = config('himati.departments', []);
        $events = Event::where('status', 'active')->orderBy('nama_event')->get();

        $logo1 = Setting::get('logo_1');
        $logo2 = Setting::get('logo_2');
        $logo3 = Setting::get('logo_3');

        return view('public.meeting_create', compact(
            'allMembers',
            'usersByDept',
            'departments',
            'events',
            'logo1',
            'logo2',
            'logo3'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'created_by'  => ['required', 'exists:users,id'],
            'jenis_rapat' => ['required', 'in:internal_dept,perdana,kepanitiaan,bph,lintas_dept'],
            'mode_rapat'  => ['required', 'in:offline,online'],
            'tanggal'     => ['required', 'date'],
            'waktu_mulai' => ['required'],
        ]);

        $createdBy = $request->input('created_by');
        $jenisRapat = $request->input('jenis_rapat');
        $modeRapat = $request->input('mode_rapat', 'offline');

        $departemen = null;
        $namaEvent = null;
        $divisiRapat = null;
        $tipeDivisi = null;
        $fokusBahasan = null;
        $departemenCollab = null;

        switch ($jenisRapat) {
            case 'internal_dept':
                $departemen = $request->input('departemen');
                $fokusBahasan = $request->input('fokus_bahasan_internal');
                break;
            case 'perdana':
                $namaEvent = $request->input('nama_event_perdana');
                break;
            case 'kepanitiaan':
                $namaEvent = $request->input('nama_event');
                $tipeDivisi = $request->input('tipe_divisi');
                $divisiRapat = $request->input('divisi_rapat');
                $fokusBahasan = $request->input('fokus_bahasan_kepanitiaan');
                break;
            case 'bph':
                $fokusBahasan = $request->input('fokus_bahasan_bph');
                break;
            case 'lintas_dept':
                $collab = $request->input('departemen_collab', []);
                $departemenCollab = $collab;
                $fokusBahasan = $request->input('fokus_bahasan_lintas');
                break;
        }

        $uniqueLink = Str::lower(Str::random(12));
        $absentLink = Str::lower(Str::random(12));

        $meeting = DB::transaction(function () use (
            $createdBy, $jenisRapat, $modeRapat, $departemen, $namaEvent,
            $divisiRapat, $tipeDivisi, $fokusBahasan, $departemenCollab,
            $request, $uniqueLink, $absentLink
        ) {
            $m = Meeting::create([
                'created_by'        => $createdBy,
                'jenis_rapat'       => $jenisRapat,
                'mode_rapat'        => $modeRapat,
                'departemen'        => $departemen,
                'nama_event'        => $namaEvent,
                'divisi_rapat'      => $divisiRapat,
                'tipe_divisi'       => $tipeDivisi,
                'fokus_bahasan'     => $fokusBahasan,
                'departemen_collab' => $departemenCollab,
                'tanggal'           => $request->input('tanggal'),
                'waktu_mulai'       => $request->input('waktu_mulai'),
                'status'            => 'scanning',
                'unique_link'       => $uniqueLink,
                'absent_link'       => $absentLink,
            ]);

            // Auto-populate attendance roster
            $memberIds = collect();
            switch ($jenisRapat) {
                case 'internal_dept':
                    $memberIds = Member::where('departemen', $departemen)->pluck('id');
                    break;
                case 'perdana':
                case 'kepanitiaan':
                    $memberIds = Member::pluck('id');
                    break;
                case 'bph':
                    $memberIds = Member::where('is_bph', true)->pluck('id');
                    break;
                case 'lintas_dept':
                    $memberIds = Member::whereIn('departemen', (array) $departemenCollab)->pluck('id');
                    break;
            }

            foreach ($memberIds as $mId) {
                Attendance::create([
                    'meeting_id' => $m->id,
                    'user_id'    => $mId,
                    'status'     => 'alpha',
                    'created_at' => now(),
                ]);
            }

            return $m;
        });

        return redirect()->route('public.hasil', $meeting->unique_link);
    }

    public function hasil($uniqueLink)
    {
        $meeting = Meeting::with(['creator', 'attendances.member'])
            ->where('unique_link', $uniqueLink)
            ->firstOrFail();

        $stats = [
            'total' => $meeting->attendances()->count(),
            'hadir' => $meeting->attendances()->where('status', 'hadir')->count(),
            'izin'  => $meeting->attendances()->where('status', 'izin')->count(),
            'sakit' => $meeting->attendances()->where('status', 'sakit')->count(),
            'alpha' => $meeting->attendances()->where('status', 'alpha')->count(),
        ];

        $scanUrl   = route('public.scanner', $meeting->unique_link);
        $absentUrl = route('public.absent', $meeting->absent_link);
        $onlineUrl = route('public.online', $meeting->unique_link);

        $logo1 = Setting::get('logo_1');
        $logo2 = Setting::get('logo_2');
        $logo3 = Setting::get('logo_3');

        return view('public.hasil', compact(
            'meeting',
            'stats',
            'scanUrl',
            'absentUrl',
            'onlineUrl',
            'logo1',
            'logo2',
            'logo3'
        ));
    }

    public function scanner($uniqueLink)
    {
        $meeting = Meeting::with('creator')
            ->where('unique_link', $uniqueLink)
            ->firstOrFail();

        $attendances = $meeting->attendances()
            ->with('member')
            ->orderBy('scanned_at', 'desc')
            ->get();

        $hadirCount = $attendances->where('status', 'hadir')->count();
        $scannedUserIds = $attendances->where('status', 'hadir')->pluck('user_id')->toArray();

        $absentUrl = route('public.absent', $meeting->absent_link);
        $onlineUrl = route('public.online', $meeting->unique_link);

        return view('public.scanner', compact(
            'meeting',
            'attendances',
            'hadirCount',
            'scannedUserIds',
            'absentUrl',
            'onlineUrl'
        ));
    }
}
