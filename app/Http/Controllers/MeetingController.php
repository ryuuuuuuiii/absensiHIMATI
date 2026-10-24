<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Event;
use App\Models\EventDivision;
use App\Models\EventMember;
use App\Models\Meeting;
use App\Models\Member;
use App\Services\GoogleSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MeetingController extends Controller
{
    protected GoogleSyncService $syncService;

    public function __construct(GoogleSyncService $syncService)
    {
        $this->syncService = $syncService;
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
                }
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
            'created_by'    => ['required', 'exists:users,id'],
            'jenis_rapat'   => ['required', 'in:internal_dept,perdana,kepanitiaan,bph,lintas_dept'],
            'mode_rapat'    => ['required', 'in:offline,online'],
            'tanggal'       => ['required', 'date'],
            'waktu_mulai'   => ['required'],
        ]);

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
                if (!$departemen || !$fokusBahasan) {
                    return back()->withInput()->with('danger', 'Departemen dan fokus pembahasan wajib diisi.');
                }
                break;

            case 'perdana':
                $namaEvent = $request->input('nama_event_perdana');
                if (!$namaEvent) {
                    return back()->withInput()->with('danger', 'Nama/agenda rapat perdana wajib diisi.');
                }
                break;

            case 'kepanitiaan':
                $eventId = $request->input('event_id');
                $tipeDivisi = $request->input('tipe_divisi', 'semua');
                $divisiId = $request->input('divisi_id');
                $fokusBahasan = $request->input('fokus_bahasan_kepanitiaan');

                if (!$eventId || !$fokusBahasan) {
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
                if (!$fokusBahasan) {
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
                if (!$fokusBahasan) {
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
                'created_by'        => $request->input('created_by'),
                'jenis_rapat'       => $jenisRapat,
                'mode_rapat'        => $modeRapat,
                'event_id'          => $eventId,
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

            // Auto-populate participants based on meeting type
            $memberIds = collect();

            switch ($jenisRapat) {
                case 'internal_dept':
                    $memberIds = Member::where('departemen', $departemen)->pluck('id');
                    break;

                case 'perdana':
                    $memberIds = Member::pluck('id');
                    break;

                case 'bph':
                    $memberIds = Member::where('is_bph', true)->pluck('id');
                    break;

                case 'lintas_dept':
                    $memberIds = Member::whereIn('departemen', $departemenCollab)->pluck('id');
                    break;

                case 'kepanitiaan':
                    if ($tipeDivisi === 'perdivisi' && !empty($request->input('divisi_id'))) {
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
        $izinCount  = $attendances->where('status', 'izin')->count();
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

        $absentUrl = route('public.absent', $meeting->absent_link);
        $onlineUrl = route('public.online', $meeting->unique_link);

        return view('meetings.scan', compact('meeting', 'attendances', 'hadirCount', 'scannedUserIds', 'absentUrl', 'onlineUrl'));
    }

    public function processScan(Request $request, $id)
    {
        $meeting = Meeting::findOrFail($id);

        if (!in_array($meeting->status, ['scanning', 'draft'])) {
            return response()->json([
                'success' => false,
                'message' => 'Rapat ini tidak sedang aktif untuk scanning.'
            ], 422);
        }

        $userId = $request->input('user_id');
        $qrCode = $request->input('qr_code');

        if (!$userId || !$qrCode) {
            return response()->json([
                'success' => false,
                'message' => 'Data scan tidak lengkap.'
            ], 422);
        }

        $member = Member::where('id', $userId)
            ->where('qr_code', $qrCode)
            ->first();

        if (!$member) {
            return response()->json([
                'success' => false,
                'message' => 'QR Code tidak cocok dengan anggota manapun.'
            ], 404);
        }

        $attendance = Attendance::where('meeting_id', $meeting->id)
            ->where('user_id', $member->id)
            ->first();

        $now = now();

        if ($attendance) {
            if ($attendance->status === 'hadir') {
                return response()->json([
                    'success'   => false,
                    'duplicate' => true,
                    'message'   => $member->nama_lengkap . ' sudah tercatat hadir sebelumnya.'
                ]);
            }

            $attendance->update([
                'status'      => 'hadir',
                'scanned_at'  => $now,
                'scan_method' => 'qr_scan',
            ]);
        } else {
            // If kepanitiaan, allow BPH to attend even if not in original roster
            if ($meeting->jenis_rapat === 'kepanitiaan' && !$member->is_bph) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anggota tidak terdaftar sebagai peserta rapat kepanitiaan ini.'
                ], 403);
            }

            $attendance = Attendance::create([
                'meeting_id'  => $meeting->id,
                'user_id'     => $member->id,
                'status'      => 'hadir',
                'scanned_at'  => $now,
                'scan_method' => 'qr_scan',
                'created_at'  => $now,
            ]);
        }

        return response()->json([
            'success'    => true,
            'message'    => 'Kehadiran berhasil dicatat!',
            'scanned_at' => $now->format('H:i'),
            'user'       => [
                'id'         => $member->id,
                'nama'       => $member->nama_lengkap,
                'departemen' => $member->departemen,
                'jabatan'    => $member->jabatan,
            ],
        ]);
    }

    public function finish(Request $request, $id)
    {
        $meeting = Meeting::findOrFail($id);

        $meeting->update([
            'status'        => 'completed',
            'waktu_selesai' => now()->format('H:i:s'),
        ]);

        // Sync to Google Sheets in background / inline
        $syncResult = $this->syncService->syncMeeting($meeting);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Absensi selesai dan berhasil disimpan.',
                'sync'    => $syncResult,
            ]);
        }

        return redirect()->route('meetings.show', $meeting->id)
            ->with('success', 'Rapat telah diselesaikan. ' . ($syncResult['message'] ?? ''));
    }

    public function savePhoto(Request $request, $id)
    {
        $meeting = Meeting::findOrFail($id);

        $request->validate([
            'foto' => ['required', 'image', 'max:10240'], // 10MB
        ]);

        $file = $request->file('foto');
        $filename = 'doc_' . $meeting->id . '_' . time() . '.' . $file->getClientOriginalExtension();
        $targetDir = public_path('uploads/dokumentasi');

        if (!File::isDirectory($targetDir)) {
            File::makeDirectory($targetDir, 0755, true);
        }

        $file->move($targetDir, $filename);

        $photos = $meeting->foto_dokumentasi ? json_decode($meeting->foto_dokumentasi, true) : [];
        if (!is_array($photos)) {
            $photos = $meeting->foto_dokumentasi ? [$meeting->foto_dokumentasi] : [];
        }
        $photos[] = $filename;

        $meeting->update([
            'foto_dokumentasi' => json_encode($photos),
        ]);

        // Upload to Google Drive if configured
        try {
            $base64 = base64_encode(File::get($targetDir . '/' . $filename));
            $this->syncService->uploadPhotoToDrive($base64, $filename);
        } catch (\Throwable $e) {}

        if ($request->wantsJson()) {
            return response()->json([
                'success'  => true,
                'message'  => 'Foto dokumentasi berhasil disimpan!',
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

        return back()->with('danger', 'Gagal sinkronisasi: ' . ($result['message'] ?? 'Error tidak diketahui'));
    }

    public function updateAttendanceStatus(Request $request, $meetingId, $attendanceId)
    {
        $request->validate([
            'status'     => ['required', 'in:hadir,izin,sakit,alpha'],
            'keterangan' => ['nullable', 'string', 'max:255'],
        ]);

        $attendance = Attendance::where('meeting_id', $meetingId)->findOrFail($attendanceId);

        $data = [
            'status'     => $request->input('status'),
            'keterangan' => $request->input('keterangan'),
        ];

        if ($request->input('status') === 'hadir' && !$attendance->scanned_at) {
            $data['scanned_at'] = now();
            $data['scan_method'] = 'manual';
        }

        $attendance->update($data);

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
                'id'           => $d->id,
                'nama_divisi'  => $d->nama_divisi,
                'total_member' => EventMember::where('division_id', $d->id)->count(),
            ];
        });

        return response()->json([
            'success' => true,
            'data'    => [
                'event'     => [
                    'id'            => $event->id,
                    'nama_event'    => $event->nama_event,
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
        if (!$file) {
            return response()->json(['success' => false, 'message' => 'File foto tidak ditemukan.'], 422);
        }

        $filename = 'doc_' . $meeting->id . '_' . time() . '.' . $file->getClientOriginalExtension();
        $targetDir = public_path('uploads/dokumentasi');

        if (!File::isDirectory($targetDir)) {
            File::makeDirectory($targetDir, 0755, true);
        }

        $file->move($targetDir, $filename);

        $photos = $meeting->foto_dokumentasi ? json_decode($meeting->foto_dokumentasi, true) : [];
        if (!is_array($photos)) {
            $photos = $meeting->foto_dokumentasi ? [$meeting->foto_dokumentasi] : [];
        }
        $photos[] = $filename;

        $meeting->update([
            'foto_dokumentasi' => json_encode($photos),
        ]);

        return response()->json([
            'success'  => true,
            'message'  => 'Foto dokumentasi berhasil disimpan!',
            'filename' => $filename,
        ]);
    }
}
