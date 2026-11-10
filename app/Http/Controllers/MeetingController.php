<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Event;
use App\Models\EventDivision;
use App\Models\EventMember;
use App\Models\Meeting;
use App\Models\Member;
use App\Services\FileUploadService;
use App\Services\GamificationService;
use App\Services\GoogleSyncService;
use App\Services\MeetingSpamProtectionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MeetingController extends Controller
{
    protected GoogleSyncService $syncService;

    protected MeetingSpamProtectionService $spamService;

    public function __construct(
        GoogleSyncService $syncService,
        MeetingSpamProtectionService $spamService,
        protected FileUploadService $uploadService
    ) {
        $this->syncService = $syncService;
        $this->spamService = $spamService;
    }

    public function index(Request $request)
    {
        $status = $request->query('status');
        $search = $request->query('q');

        $query = Meeting::with('creator')
            ->withCount([
                'attendances as total_peserta',
                'attendances as jumlah_hadir' => function ($q) {
                    $q->where('status', 'hadir');
                },
            ]);

        if ($status && in_array($status, ['draft', 'scanning', 'completed', 'cancelled'])) {
            $query->where('status', $status);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('fokus_bahasan', 'like', "%{$search}%")
                    ->orWhere('nama_event', 'like', "%{$search}%")
                    ->orWhere('departemen', 'like', "%{$search}%");
            });
        }

        $meetings = $query->orderBy('tanggal', 'desc')
            ->orderBy('waktu_mulai', 'desc')
            ->paginate(15);

        return view('meetings.index', compact('meetings', 'status', 'search'));
    }

    public function create()
    {
        $allMembers = Member::orderBy('departemen')->orderBy('nama_lengkap')->get();
        $membersByDept = $allMembers->groupBy('departemen');
        $events = Event::where('status', 'active')->orderBy('nama_event')->get();
        $departments = config('himati.departments', []);
        $meetingTypes = config('himati.meeting_types', []);

        return view('meetings.create', compact('allMembers', 'membersByDept', 'events', 'departments', 'meetingTypes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'created_by' => ['required', 'exists:users,id'],
            'jenis_rapat' => ['required', 'in:internal_dept,perdana,kepanitiaan,bph,lintas_dept'],
            'mode_rapat' => ['required', 'in:offline,online'],
            'tanggal' => ['required', 'date'],
            'waktu_mulai' => ['required'],
            'waktu_selesai' => ['nullable', 'string'],
        ]);

        $createdBy = (int) $request->input('created_by');

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
            $request, $jenisRapat, $modeRapat, $eventId, $departemen, $namaEvent,
            $divisiRapat, $tipeDivisi, $fokusBahasan, $departemenCollab, $uniqueLink, $absentLink
        ) {
            $m = Meeting::create([
                'created_by' => $request->input('created_by'),
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
                'waktu_selesai' => $request->filled('waktu_selesai') ? date('H:i:s', strtotime($request->input('waktu_selesai'))) : null,
                'status' => 'scanning',
                'unique_link' => $uniqueLink,
                'absent_link' => $absentLink,
            ]);

            // Auto-populate participants based on meeting type
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
            }

            $now = now();
            $attendanceRecords = [];
            $hasCreatorAttended = false;
            foreach ($memberIds as $mId) {
                $isCreator = ($m->created_by && (int) $mId === (int) $m->created_by);
                if ($isCreator) {
                    $hasCreatorAttended = true;
                }

                $attendanceRecords[] = [
                    'meeting_id' => $m->id,
                    'user_id' => (int) $mId,
                    'status' => $isCreator ? 'hadir' : 'alpha',
                    'scanned_at' => $isCreator ? $now : null,
                    'scan_method' => $isCreator ? 'manual' : 'qr_scan',
                    'exp_awarded' => $isCreator,
                    'created_at' => $now,
                ];
            }

            if (! empty($attendanceRecords)) {
                Attendance::insert($attendanceRecords);
            }

            if ($hasCreatorAttended && $m->created_by) {
                Member::where('id', $m->created_by)->increment('exp', GamificationService::ATTENDANCE_EXP);
                cache()->forget("portal_stats_{$m->created_by}");
            }

            return $m;
        });

        $this->spamService->recordCreation($request, $createdBy);

        if ($modeRapat === 'offline') {
            return redirect()->route('meetings.scan', $meeting->id)
                ->with('success', 'Rapat berhasil dibuat! Scanner absensi siap digunakan.');
        }

        return redirect()->route('meetings.show', $meeting->id)
            ->with('success', 'Rapat online berhasil dibuat! Link kehadiran siap dibagikan.');
    }

    public function show($id)
    {
        $meeting = Meeting::with(['creator', 'event', 'attendances.member'])
            ->findOrFail($id);

        $attendances = $meeting->attendances()
            ->with('member')
            ->join('users', 'attendances.user_id', '=', 'users.id')
            ->orderBy('users.departemen')
            ->orderBy('users.nama_lengkap')
            ->select('attendances.*')
            ->get();

        $hadirCount = $attendances->where('status', 'hadir')->count();
        $izinCount = $attendances->where('status', 'izin')->count();
        $sakitCount = $attendances->where('status', 'sakit')->count();
        $alphaCount = $attendances->where('status', 'alpha')->count();

        return view('meetings.show', compact('meeting', 'attendances', 'hadirCount', 'izinCount', 'sakitCount', 'alphaCount'));
    }

    public function scan($id)
    {
        $meeting = Meeting::with('creator')->findOrFail($id);

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

        return view('meetings.scan', compact('meeting', 'attendances', 'hadirCount', 'scannedUserIds', 'scannedQrCodes', 'absentUrl', 'onlineUrl', 'docPhotos'));
    }

    public function processScan(Request $request, $id)
    {
        $meeting = Meeting::findOrFail($id);

        if (! in_array($meeting->status, ['scanning', 'draft'])) {
            return response()->json([
                'success' => false,
                'message' => 'Rapat ini tidak sedang aktif untuk scanning.',
            ], 422);
        }

        $userId = $request->input('user_id');
        $qrCode = $request->input('qr_code');

        if (! $userId && ! $qrCode) {
            return response()->json([
                'success' => false,
                'message' => 'Data scan tidak lengkap.',
            ], 422);
        }

        $query = Member::query();
        if ($userId && $qrCode) {
            $query->where('id', $userId)->where('qr_code', $qrCode);
        } elseif ($userId) {
            $query->where('id', $userId);
        } else {
            $query->where('qr_code', $qrCode);
        }
        $member = $query->first();

        if (! $member) {
            return response()->json([
                'success' => false,
                'message' => 'QR Code tidak cocok dengan anggota manapun.',
            ], 404);
        }

        $eligibilityError = $meeting->checkMemberEligibility($member);
        if ($eligibilityError) {
            return response()->json([
                'success' => false,
                'sp_level' => (int) $member->sp_level,
                'sp_label' => $member->sp_label,
                'message' => $eligibilityError,
            ], 403);
        }

        $now = now();

        $scanResult = DB::transaction(function () use ($meeting, $member, $now) {
            $attendance = Attendance::where('meeting_id', $meeting->id)
                ->where('user_id', $member->id)
                ->lockForUpdate()
                ->first();

            if ($attendance) {
                if ($attendance->status === 'hadir') {
                    return ['duplicate' => true, 'attendance' => $attendance, 'exp_awarded' => null];
                }

                $attendance->update([
                    'status' => 'hadir',
                    'scanned_at' => $now,
                    'scan_method' => 'qr_scan',
                ]);
            } else {
                $attendance = Attendance::create([
                    'meeting_id' => $meeting->id,
                    'user_id' => $member->id,
                    'status' => 'hadir',
                    'scanned_at' => $now,
                    'scan_method' => 'qr_scan',
                    'created_at' => $now,
                ]);
            }

            $expAwarded = app(GamificationService::class)->awardAttendanceExp($attendance, $member);

            return ['duplicate' => false, 'attendance' => $attendance, 'exp_awarded' => $expAwarded];
        });

        if ($scanResult['duplicate']) {
            return response()->json([
                'success' => false,
                'duplicate' => true,
                'message' => $member->nama_lengkap.' sudah tercatat hadir sebelumnya.',
            ]);
        }

        $spNotice = $member->sp_attendance_notice;
        $expNotice = ! empty($scanResult['exp_awarded']) ? " (+{$scanResult['exp_awarded']} EXP)" : '';

        return response()->json([
            'success' => true,
            'message' => 'Kehadiran berhasil dicatat!'.$expNotice.$spNotice,
            'scanned_at' => $now->format('H:i'),
            'scanned_at_iso' => $now->toIso8601String(),
            'user' => [
                'id' => $member->id,
                'nama' => $member->nama_lengkap,
                'qr_code' => $member->qr_code,
                'departemen' => $member->departemen,
                'jabatan' => $member->jabatan,
                'is_bph' => (bool) $member->is_bph,
                'is_bph_inti' => $member->isBphInti(),
                'sp_level' => (int) $member->sp_level,
                'sp_label' => $member->sp_label,
                'sp_source' => $member->sp_level > 0 ? ($member->isSpFromAlpha() ? 'alpha' : 'admin') : null,
                'sp_source_label' => $member->sp_source_label,
                'sp_reason' => $member->sp_level > 0 ? $member->sp_reason_display : null,
                'sp_warning' => $member->sp_warning_text,
            ],
        ]);
    }

    public function finish(Request $request, $id)
    {
        $meeting = Meeting::findOrFail($id);

        $isAjaxOrJson = $request->ajax()
            || $request->wantsJson()
            || $request->isJson()
            || $request->hasHeader('X-Requested-With')
            || $request->input('action') === 'finish';

        if ($meeting->status === 'completed') {
            $redirectUrl = route('public.hasil', $meeting->unique_link);
            if ($isAjaxOrJson) {
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

        if ($isAjaxOrJson) {
            return response()->json([
                'success' => true,
                'message' => 'Absensi selesai dan berhasil disimpan.',
                'redirect_url' => route('public.hasil', $meeting->unique_link),
            ]);
        }

        if (auth()->check()) {
            return redirect()->route('meetings.show', $meeting->id)
                ->with('success', 'Rapat telah diselesaikan dan absensi ditutup.');
        }

        return redirect()->route('public.hasil', $meeting->unique_link)
            ->with('success', 'Rapat telah diselesaikan dan absensi ditutup.');
    }

    public function cancel(Request $request, $id)
    {
        $meeting = Meeting::findOrFail($id);

        if ($meeting->status === 'completed') {
            return back()->with('danger', 'Rapat yang sudah diselesaikan tidak dapat dibatalkan.');
        }

        $meeting->update([
            'status' => 'cancelled',
            'waktu_selesai' => now()->format('H:i:s'),
        ]);

        return redirect()->route('meetings.show', $meeting->id)
            ->with('warning', 'Sesi rapat telah dibatalkan. Anggota tidak akan dicatat Alpha untuk sesi ini.');
    }

    public function updateExpiry(Request $request, $id)
    {
        $meeting = Meeting::findOrFail($id);

        if (in_array($meeting->status, ['completed', 'cancelled'], true)) {
            return back()->with('danger', 'Rapat sudah selesai atau dibatalkan. Batas waktu tidak dapat diubah.');
        }

        $validated = $request->validate([
            'waktu_selesai' => ['required', 'string'],
        ]);

        $time = date('H:i:s', strtotime($validated['waktu_selesai']));
        $meeting->update([
            'waktu_selesai' => $time,
        ]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Batas waktu absensi online berhasil diperbarui menjadi '.substr($time, 0, 5).' WITA.',
                'waktu_selesai' => substr($time, 0, 5).' WITA',
            ]);
        }

        return back()->with('success', 'Batas waktu absensi online berhasil diperbarui menjadi '.substr($time, 0, 5).' WITA.');
    }

    public function savePhoto(Request $request, $id)
    {
        $meeting = Meeting::findOrFail($id);

        $request->validate([
            'foto' => ['required', 'image', 'max:10240'], // 10MB
        ]);

        $file = $request->file('foto');
        $filename = 'doc_'.$meeting->id.'_'.time().'.'.$file->getClientOriginalExtension();
        $filename = $this->uploadService->store($file, 'dokumentasi', $filename);

        $photos = $meeting->foto_dokumentasi ? json_decode($meeting->foto_dokumentasi, true) : [];
        if (! is_array($photos)) {
            $photos = $meeting->foto_dokumentasi ? [$meeting->foto_dokumentasi] : [];
        }
        $photos[] = $filename;

        $meeting->update([
            'foto_dokumentasi' => json_encode($photos),
        ]);

        // Upload to Google Drive if configured
        try {
            $base64 = base64_encode(File::get($targetDir.'/'.$filename));
            $this->syncService->uploadPhotoToDrive($base64, $filename);
        } catch (\Throwable $e) {
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Foto dokumentasi berhasil disimpan!',
                'filename' => $filename,
            ]);
        }

        return back()->with('success', 'Foto dokumentasi berhasil disimpan!');
    }

    public function syncSheets($id)
    {
        $meeting = Meeting::findOrFail($id);
        $result = $this->syncService->syncMeeting($meeting);

        if ($result['success']) {
            return back()->with('success', 'Data rapat berhasil disinkronkan ke Google Sheets!');
        }

        return back()->with('danger', 'Gagal sinkronisasi: '.($result['message'] ?? 'Error tidak diketahui'));
    }

    public function updateAttendanceStatus(Request $request, $meetingId, $attendanceId)
    {
        $request->validate([
            'status' => ['required', 'in:hadir,izin,sakit,alpha'],
            'keterangan' => ['nullable', 'string', 'max:255'],
        ]);

        $attendance = Attendance::where('meeting_id', $meetingId)->findOrFail($attendanceId);

        $data = [
            'status' => $request->input('status'),
            'keterangan' => $request->input('keterangan'),
        ];

        if ($request->input('status') === 'hadir' && ! $attendance->scanned_at) {
            $data['scanned_at'] = now();
            $data['scan_method'] = 'manual';
        }

        $newStatus = $request->input('status');
        $oldStatus = $attendance->status;

        $attendance->update($data);

        $gamification = app(GamificationService::class);
        if ($newStatus === 'hadir' && $oldStatus !== 'hadir') {
            $gamification->awardAttendanceExp($attendance);
        } elseif ($newStatus !== 'hadir' && $oldStatus === 'hadir') {
            $gamification->revokeAttendanceExp($attendance);
        }

        return back()->with('success', 'Status kehadiran berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $meeting = Meeting::findOrFail($id);
        $meeting->delete();

        return redirect()->route('meetings.index')
            ->with('success', 'Rapat berhasil dihapus.');
    }

    public function getEventData(Request $request, $eventId = null)
    {
        $id = $eventId ?: $request->query('event_id');
        $event = Event::with('divisions')->findOrFail($id);

        $totalMembers = EventMember::where('event_id', $event->id)->distinct('user_id')->count('user_id');

        $divisions = $event->divisions->map(function ($d) {
            return [
                'id' => $d->id,
                'nama_divisi' => $d->nama_divisi,
                'total_member' => EventMember::where('division_id', $d->id)->count(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'event' => [
                    'id' => $event->id,
                    'nama_event' => $event->nama_event,
                    'total_members' => $totalMembers,
                ],
                'divisions' => $divisions,
            ],
        ]);
    }

    public function processScanLegacy(Request $request)
    {
        $meetingId = $request->input('meeting_id');
        $action = $request->input('action', 'scan');

        if ($action === 'finish') {
            return $this->finish($request, $meetingId);
        }

        return $this->processScan($request, $meetingId);
    }

    public function savePhotoLegacy(Request $request)
    {
        $meetingId = $request->input('meeting_id');
        $meeting = Meeting::findOrFail($meetingId);

        $file = $request->file('photo') ?? $request->file('foto');
        if (! $file) {
            return response()->json(['success' => false, 'message' => 'File foto tidak ditemukan.'], 422);
        }

        $filename = 'doc_'.$meeting->id.'_'.time().'.'.$file->getClientOriginalExtension();
        $filename = $this->uploadService->store($file, 'dokumentasi', $filename);

        $photos = $meeting->foto_dokumentasi ? json_decode($meeting->foto_dokumentasi, true) : [];
        if (! is_array($photos)) {
            $photos = $meeting->foto_dokumentasi ? [$meeting->foto_dokumentasi] : [];
        }
        $photos[] = $filename;

        $meeting->update([
            'foto_dokumentasi' => json_encode($photos),
        ]);

        try {
            $base64 = base64_encode(File::get($targetDir.'/'.$filename));
            $this->syncService->uploadPhotoToDrive($base64, $filename);
        } catch (\Throwable $e) {
        }

        return response()->json([
            'success' => true,
            'message' => 'Foto dokumentasi berhasil disimpan!',
            'filename' => $filename,
            'url' => asset('uploads/dokumentasi/'.$filename),
            'total_photos' => count($photos),
            'photos' => array_values(array_map(fn ($p) => asset('uploads/dokumentasi/'.$p), $photos)),
        ]);
    }
}
