<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Event;
use App\Models\EventDivision;
use App\Models\EventMember;
use App\Models\Meeting;
use App\Models\Member;
use App\Services\GoogleSyncService;
use App\Services\MeetingSpamProtectionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PublicMeetingController extends Controller
{
    protected GoogleSyncService $syncService;

    protected MeetingSpamProtectionService $spamService;

    public function __construct(GoogleSyncService $syncService, MeetingSpamProtectionService $spamService)
    {
        $this->syncService = $syncService;
        $this->spamService = $spamService;
    }

    public function index()
    {
        $allMembers = cache()->remember('active_members_for_meeting', 300, function () {
            return Member::select(['id', 'nama_lengkap', 'departemen', 'jabatan', 'email', 'profile_photo'])
                ->orderBy('departemen')
                ->orderBy('nama_lengkap')
                ->get();
        });
        $usersByDept = $allMembers->groupBy('departemen');
        $departments = config('himati.departments', []);
        $events = cache()->remember('active_events_for_meeting', 300, function () {
            return Event::select(['id', 'nama_event', 'status'])
                ->where('status', 'active')
                ->orderBy('nama_event')
                ->get();
        });

        $loggedMember = auth('member')->user();
        $loggedAdmin = auth('web')->user();

        return view('public.meeting_create', compact(
            'allMembers',
            'usersByDept',
            'departments',
            'events',
            'loggedMember',
            'loggedAdmin'
        ));
    }

    public function store(Request $request)
    {
        $rules = [
            'jenis_rapat' => ['required', 'in:internal_dept,perdana,kepanitiaan,bph,lintas_dept'],
            'mode_rapat' => ['required', 'in:offline,online'],
            'tanggal' => ['required', 'date'],
            'waktu_mulai' => ['required'],
        ];

        if (auth('member')->check()) {
            $createdBy = auth('member')->id();
        } else {
            $rules['created_by'] = ['required', 'exists:users,id'];
            $createdBy = (int) $request->input('created_by');
        }

        $request->validate($rules);

        $spamError = $this->spamService->checkCreationSpam($request, $createdBy);
        if ($spamError) {
            return back()->withInput()->with('danger', $spamError);
        }

        $jenisRapat = $request->input('jenis_rapat');
        $modeRapat = $request->input('mode_rapat', 'offline');

        $departemen = null;
        $namaEvent = null;
        $divisiRapat = null;
        $tipeDivisi = null;
        $fokusBahasan = null;
        $departemenCollab = null;
        $eventId = null;

        switch ($jenisRapat) {
            case 'internal_dept':
                $departemen = $request->input('departemen');
                $fokusBahasan = $request->input('fokus_bahasan_internal');
                if (! $departemen || ! $fokusBahasan) {
                    return back()->withInput()->with('danger', 'Departemen dan fokus pembahasan wajib diisi.');
                }
                break;

            case 'perdana':
                $namaEvent = $request->input('nama_event_perdana');
                if (! $namaEvent) {
                    return back()->withInput()->with('danger', 'Nama/agenda rapat perdana wajib diisi.');
                }
                break;

            case 'kepanitiaan':
                $eventId = $request->input('event_id');
                $tipeDivisi = $request->input('tipe_divisi', 'semua');
                $divisiId = $request->input('divisi_id');
                $fokusBahasan = $request->input('fokus_bahasan_kepanitiaan');

                if (! $eventId || ! $fokusBahasan) {
                    return back()->withInput()->with('danger', 'Pilih event dan isi agenda pembahasan.');
                }

                $event = Event::find($eventId);
                $namaEvent = $event ? $event->nama_event : null;

                if ($tipeDivisi === 'perdivisi' && $divisiId) {
                    $div = EventDivision::find($divisiId);
                    $divisiRapat = $div ? $div->nama_divisi : null;
                }
                break;

            case 'bph':
                $fokusBahasan = $request->input('fokus_bahasan_bph');
                if (! $fokusBahasan) {
                    return back()->withInput()->with('danger', 'Fokus bahasan rapat BPH wajib diisi.');
                }
                break;

            case 'lintas_dept':
                $collab = $request->input('departemen_collab', []);
                if (count($collab) < 2) {
                    return back()->withInput()->with('danger', 'Pilih minimal 2 departemen untuk rapat lintas departemen.');
                }
                $departemenCollab = $collab;
                $fokusBahasan = $request->input('fokus_bahasan_lintas');
                if (! $fokusBahasan) {
                    return back()->withInput()->with('danger', 'Agenda bahasan wajib diisi.');
                }
                break;
        }

        $uniqueLink = Str::lower(Str::random(12));
        $absentLink = Str::lower(Str::random(12));

        $meeting = DB::transaction(function () use (
            $createdBy, $jenisRapat, $modeRapat, $eventId, $departemen, $namaEvent,
            $divisiRapat, $tipeDivisi, $fokusBahasan, $departemenCollab,
            $request, $uniqueLink, $absentLink
        ) {
            $m = Meeting::create([
                'created_by' => $createdBy,
                'jenis_rapat' => $jenisRapat,
                'mode_rapat' => $modeRapat,
                'event_id' => $eventId,
                'departemen' => $departemen,
                'nama_event' => $namaEvent,
                'divisi_rapat' => $divisiRapat,
                'tipe_divisi' => $tipeDivisi,
                'fokus_bahasan' => $fokusBahasan,
                'departemen_collab' => $departemenCollab,
                'tanggal' => $request->input('tanggal'),
                'waktu_mulai' => $request->input('waktu_mulai'),
                'status' => 'scanning',
                'unique_link' => $uniqueLink,
                'absent_link' => $absentLink,
            ]);

            // Auto-populate attendance roster
            $memberIds = collect();
            switch ($jenisRapat) {
                case 'internal_dept':
                    $deptLower = strtolower((string) $departemen);
                    if ($deptLower === 'inti') {
                        $memberIds = Member::where('departemen', 'Inti')->pluck('id');
                    } elseif ($deptLower === 'bph') {
                        $memberIds = Member::where('departemen', 'Inti')
                            ->orWhere('is_bph', true)
                            ->pluck('id');
                    } else {
                        $memberIds = Member::where('departemen', $departemen)->pluck('id');
                    }
                    break;

                case 'perdana':
                    $memberIds = Member::pluck('id');
                    break;

                case 'kepanitiaan':
                    if ($tipeDivisi === 'perdivisi' && ! empty($request->input('divisi_id'))) {
                        $memberIds = EventMember::where('event_id', $eventId)
                            ->where('division_id', $request->input('divisi_id'))
                            ->pluck('user_id');
                    } else {
                        $memberIds = EventMember::where('event_id', $eventId)
                            ->distinct()
                            ->pluck('user_id');
                    }
                    break;

                case 'bph':
                    $memberIds = Member::where('is_bph', true)->pluck('id');
                    break;

                case 'lintas_dept':
                    $collab = (array) $departemenCollab;
                    $query = Member::query();
                    if (in_array('BPH', $collab)) {
                        $query->where(function ($q) use ($collab) {
                            $q->whereIn('departemen', $collab)
                                ->orWhere('departemen', 'Inti')
                                ->orWhere('is_bph', true);
                        });
                    } else {
                        $query->whereIn('departemen', $collab);
                    }
                    $memberIds = $query->pluck('id');
                    break;
            }

            $now = now();
            $attendanceRecords = [];
            foreach ($memberIds as $mId) {
                $isCreator = ($m->created_by && (int) $mId === (int) $m->created_by);

                $attendanceRecords[] = [
                    'meeting_id' => $m->id,
                    'user_id' => (int) $mId,
                    'status' => $isCreator ? 'hadir' : 'alpha',
                    'scanned_at' => $isCreator ? $now : null,
                    'scan_method' => $isCreator ? 'manual' : 'qr_scan',
                    'created_at' => $now,
                ];
            }

            if (! empty($attendanceRecords)) {
                Attendance::insert($attendanceRecords);
            }

            return $m;
        });

        $this->spamService->recordCreation($request, $createdBy);

        return redirect()->route('public.hasil', $meeting->unique_link);
    }

    public function hasil(Request $request, $uniqueLink)
    {
        $meeting = Meeting::with(['creator', 'event'])
            ->where('unique_link', $uniqueLink)
            ->firstOrFail();

        $attendances = $meeting->attendances()
            ->with('member')
            ->get()
            ->sortBy([
                fn ($a, $b) => (['hadir' => 1, 'izin' => 2, 'sakit' => 3, 'alpha' => 4][$a->status] ?? 5)
                    <=> (['hadir' => 1, 'izin' => 2, 'sakit' => 3, 'alpha' => 4][$b->status] ?? 5),
                fn ($a, $b) => strcmp($a->member?->nama_lengkap ?? '', $b->member?->nama_lengkap ?? ''),
            ])
            ->values();

        $stats = [
            'total' => $attendances->count(),
            'hadir' => $attendances->where('status', 'hadir')->count(),
            'izin' => $attendances->where('status', 'izin')->count(),
            'sakit' => $attendances->where('status', 'sakit')->count(),
            'alpha' => $attendances->where('status', 'alpha')->count(),
        ];

        $attendancesData = $attendances->map(function ($a) {
            return [
                'id' => $a->id,
                'user_id' => $a->user_id,
                'nama' => $a->member ? $a->member->nama_lengkap : 'Unknown',
                'departemen' => $a->member ? $a->member->departemen : '-',
                'jabatan' => $a->member ? $a->member->jabatan : '',
                'sp_level' => $a->member ? (int) $a->member->sp_level : 0,
                'sp_label' => $a->member ? $a->member->sp_label : null,
                'is_active' => $a->member ? (bool) $a->member->is_active : true,
                'status' => $a->status,
                'scanned_at' => $a->scanned_at ? $a->scanned_at->format('H:i').' WITA' : null,
                'scan_method' => $a->scan_method,
                'keterangan' => $a->keterangan,
                'bukti_foto' => ($a->bukti_foto && file_exists(public_path('uploads/bukti/'.$a->bukti_foto))) ? asset('uploads/bukti/'.$a->bukti_foto) : null,
            ];
        });

        if ($request->ajax() || $request->wantsJson() || $request->query('json')) {
            return response()->json([
                'success' => true,
                'stats' => $stats,
                'attendances' => $attendancesData,
            ]);
        }

        $scanUrl = route('public.scanner', $meeting->unique_link);
        $absentUrl = route('public.absent', $meeting->absent_link);
        $onlineUrl = route('public.online', $meeting->unique_link);

        $rawPhotos = $meeting->foto_dokumentasi ? json_decode($meeting->foto_dokumentasi, true) : [];
        if (! is_array($rawPhotos)) {
            $rawPhotos = $meeting->foto_dokumentasi ? [$meeting->foto_dokumentasi] : [];
        }
        $docPhotos = array_values(array_filter(array_map(function ($p) {
            return file_exists(public_path('uploads/dokumentasi/'.$p)) ? asset('uploads/dokumentasi/'.$p) : null;
        }, $rawPhotos)));

        return view('public.hasil', compact(
            'meeting',
            'attendances',
            'attendancesData',
            'stats',
            'scanUrl',
            'absentUrl',
            'onlineUrl',
            'docPhotos'
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
        $scannedQrCodes = $attendances->where('status', 'hadir')->pluck('member.qr_code')->filter()->values()->toArray();

        $absentUrl = route('public.absent', $meeting->absent_link);
        $onlineUrl = route('public.online', $meeting->unique_link);

        $rawPhotos = $meeting->foto_dokumentasi ? json_decode($meeting->foto_dokumentasi, true) : [];
        if (! is_array($rawPhotos)) {
            $rawPhotos = $meeting->foto_dokumentasi ? [$meeting->foto_dokumentasi] : [];
        }
        $docPhotos = array_values(array_filter(array_map(function ($p) {
            return file_exists(public_path('uploads/dokumentasi/'.$p)) ? asset('uploads/dokumentasi/'.$p) : null;
        }, $rawPhotos)));

        return view('public.scanner', compact(
            'meeting',
            'attendances',
            'hadirCount',
            'scannedUserIds',
            'scannedQrCodes',
            'absentUrl',
            'onlineUrl',
            'docPhotos'
        ));
    }

    public function finish(Request $request, $uniqueLink)
    {
        $meeting = Meeting::where('unique_link', $uniqueLink)->firstOrFail();

        if ($meeting->status === 'completed') {
            $redirectUrl = route('public.hasil', $meeting->unique_link);
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Rapat telah diselesaikan sebelumnya.',
                    'redirect_url' => $redirectUrl,
                ]);
            }

            return redirect($redirectUrl)->with('info', 'Rapat telah diselesaikan sebelumnya.');
        }

        $meeting->update([
            'status' => 'completed',
            'waktu_selesai' => now()->format('H:i:s'),
        ]);

        // Sync ke Google Sheets secara asinkron di background (tidak menahan response pengguna)
        $this->syncService->syncMeetingAsync($meeting);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Rapat telah diselesaikan dan absensi ditutup.',
                'waktu_selesai' => substr($meeting->waktu_selesai, 0, 5).' WITA',
                'redirect_url' => route('public.hasil', $meeting->unique_link),
            ]);
        }

        return redirect()->route('public.hasil', $meeting->unique_link)
            ->with('success', 'Rapat telah diselesaikan. Absensi online telah ditutup.');
    }

    public function cancel(Request $request, $uniqueLink)
    {
        $meeting = Meeting::where('unique_link', $uniqueLink)->firstOrFail();

        if ($meeting->status === 'completed') {
            return back()->with('danger', 'Rapat yang sudah diselesaikan tidak dapat dibatalkan.');
        }

        $meeting->update([
            'status' => 'cancelled',
            'waktu_selesai' => now()->format('H:i:s'),
        ]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Sesi absensi telah dibatalkan.',
                'redirect_url' => route('public.hasil', $meeting->unique_link),
            ]);
        }

        return redirect()->route('public.hasil', $meeting->unique_link)
            ->with('warning', 'Sesi absensi telah dibatalkan. Anggota tidak akan dicatat Alpha untuk sesi ini.');
    }
}
