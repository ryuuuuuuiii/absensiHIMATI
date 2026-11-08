<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\AnnouncementRead;
use App\Models\Attendance;
use App\Models\Feedback;
use App\Models\Member;
use App\Services\FileUploadService;
use App\Services\QrCodeService;
use App\Services\SpService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PortalController extends Controller
{
    public function __construct(
        protected QrCodeService $qrService,
        protected FileUploadService $uploadService
    ) {}

    public function dashboard()
    {
        $member = $this->currentMember();
        $stats = $this->attendanceStats($member);

        $recentAttendances = $member->attendances()
            ->with('meeting')
            ->whereHas('meeting', function ($query) {
                $query->where('status', 'completed');
            })
            ->get()
            ->sortByDesc(fn (Attendance $attendance) => $attendance->meeting?->tanggal?->timestamp ?? 0)
            ->take(5);

        $upcomingAttendances = $member->attendances()
            ->with('meeting')
            ->whereHas('meeting', function ($query) {
                $query->whereIn('status', ['draft', 'scanning']);
            })
            ->get()
            ->sortBy(fn (Attendance $attendance) => $attendance->meeting?->tanggal?->timestamp ?? 0);

        $unattendedAttendances = $upcomingAttendances->filter(fn (Attendance $a) => ! in_array($a->status, ['hadir', 'izin', 'sakit'], true));
        $attendedAttendances = $upcomingAttendances->filter(fn (Attendance $a) => in_array($a->status, ['hadir', 'izin', 'sakit'], true));

        $eventMemberships = $member->eventMembers()
            ->with(['event', 'division'])
            ->get()
            ->sortByDesc(fn ($membership) => $membership->event?->nama_event ?? '');

        // Pengumuman & Notifikasi Banner aktif untuk dashboard
        $dashboardBanners = Announcement::active()
            ->forMember($member)
            ->where('is_banner', true)
            ->with(['admin', 'reads' => function ($q) use ($member) {
                $q->where('user_id', $member->id);
            }])
            ->orderBy('pinned', 'desc')
            ->orderBy('created_at', 'desc')
            ->get()
            ->filter(function (Announcement $a) {
                $read = $a->reads->first();

                return ! ($read && $read->is_dismissed);
            });

        return view('portal.dashboard', compact(
            'member',
            'stats',
            'recentAttendances',
            'upcomingAttendances',
            'unattendedAttendances',
            'attendedAttendances',
            'eventMemberships',
            'dashboardBanners'
        ));
    }

    public function attendance(Request $request)
    {
        $member = $this->currentMember();
        $stats = $this->attendanceStats($member);
        $status = $request->query('status');

        $query = $member->attendances()
            ->with('meeting')
            ->whereHas('meeting', function ($builder) {
                $builder->where('status', 'completed');
            });

        if (in_array($status, ['hadir', 'izin', 'sakit', 'alpha'], true)) {
            $query->where('status', $status);
        }

        $attendances = $query
            ->join('meetings', 'meetings.id', '=', 'attendances.meeting_id')
            ->orderByDesc('meetings.tanggal')
            ->orderByDesc('meetings.waktu_mulai')
            ->select('attendances.*')
            ->paginate(15)
            ->withQueryString();

        return view('portal.attendance', compact('member', 'stats', 'attendances', 'status'));
    }

    public function qr()
    {
        $member = $this->currentMember();
        $filename = $this->qrService->ensureMemberQr($member);

        return view('portal.qr', compact('member', 'filename'));
    }

    public function downloadQr()
    {
        $member = $this->currentMember();
        $filename = $this->qrService->ensureMemberQr($member);

        if (! $filename) {
            return back()->with('danger', 'Gagal membuat file QR Code. Silakan coba lagi.');
        }

        $filepath = public_path('assets/img/qrcodes/'.$filename);
        $downloadName = 'QR_'.Str::slug($member->nama_lengkap).'_'.$member->qr_code.'.png';

        return response()->download($filepath, $downloadName);
    }

    public function absenceForm()
    {
        $member = $this->currentMember();

        $attendances = $member->attendances()
            ->with('meeting')
            ->whereHas('meeting', function ($query) {
                $query->whereIn('status', ['draft', 'scanning']);
            })
            ->get()
            ->sortBy(fn (Attendance $attendance) => $attendance->meeting?->tanggal?->timestamp ?? 0);

        return view('portal.absence', compact('member', 'attendances'));
    }

    public function submitAbsence(Request $request)
    {
        $member = $this->currentMember();

        $validated = $request->validate([
            'attendance_id' => ['required', 'integer'],
            'status' => ['required', 'in:izin,sakit'],
            'keterangan' => ['required', 'string', 'max:255'],
            'bukti_foto' => ['nullable', 'image', 'max:5120'],
        ]);

        $attendance = $member->attendances()
            ->with('meeting')
            ->findOrFail($validated['attendance_id']);

        $meeting = $attendance->meeting;

        if (! $meeting || in_array($meeting->status, ['completed', 'cancelled'], true)) {
            return back()->with('error', 'Absen ditolak. Rapat ini sudah selesai atau dibatalkan.');
        }

        $data = [
            'status' => $validated['status'],
            'keterangan' => $validated['keterangan'],
            'scanned_at' => now(),
            'scan_method' => 'absent_form',
        ];

        if ($filename = $this->storeProofPhoto($request, $attendance, 'surat')) {
            $data['bukti_foto'] = $filename;
        }

        $attendance->update($data);

        return redirect()->route('portal.attendance')
            ->with('success', 'Pengajuan absen berhasil dikirim.'.$member->sp_attendance_notice);
    }

    public function submitPresence(Request $request, Attendance $attendance)
    {
        $member = $this->currentMember();

        abort_unless((int) $attendance->user_id === (int) $member->id, 404);

        if ($member->isDismissed()) {
            return back()->with('error', 'Presensi ditolak. Akun Anda telah dinonaktifkan karena status SP 3.');
        }

        $attendance->loadMissing('meeting');
        $meeting = $attendance->meeting;

        if (! $meeting || $meeting->status !== 'scanning') {
            return back()->with('error', 'Presensi hanya dapat dilakukan saat rapat sedang berlangsung.');
        }

        if ($meeting->mode_rapat === 'offline') {
            return back()->with('error', 'Rapat ini diselenggarakan secara offline (tatap muka). Kehadiran wajib dicatat melalui scan QR Code oleh scanner panitia.');
        }

        $validated = $request->validate([
            'keterangan' => ['nullable', 'string', 'max:255'],
            'bukti_foto' => ['nullable', 'image', 'max:5120'],
        ]);

        $data = [
            'status' => 'hadir',
            'keterangan' => $validated['keterangan'] ?? null,
            'scanned_at' => now(),
            'scan_method' => 'online_link',
        ];

        if ($filename = $this->storeProofPhoto($request, $attendance, 'presensi')) {
            $data['bukti_foto'] = $filename;
        }

        $attendance->update($data);

        return back()->with('success', 'Presensi kehadiran berhasil dicatat.'.$member->sp_attendance_notice.' Terima kasih!');
    }

    public function profile()
    {
        $member = $this->currentMember();
        $feedbacks = $member->feedbacks()
            ->latest()
            ->take(10)
            ->get();

        return view('portal.profile', compact('member', 'feedbacks'));
    }

    public function updateProfile(Request $request)
    {
        $member = $this->currentMember();

        $validated = $request->validate([
            'email' => [
                'required',
                'email',
                'max:100',
                Rule::unique('users', 'email')->ignore($member->id),
            ],
            'profile_photo' => ['nullable', 'image', 'max:5120'],
            'remove_photo' => ['nullable', 'boolean'],
        ]);

        $data = ['email' => $validated['email']];

        if ($request->boolean('remove_photo') && $member->profile_photo) {
            $this->deleteProfilePhoto($member->profile_photo);
            $data['profile_photo'] = null;
        }

        if ($request->hasFile('profile_photo')) {
            $this->deleteProfilePhoto($member->profile_photo);

            $file = $request->file('profile_photo');
            $filename = 'profile_'.$member->id.'_'.time().'.'.$file->getClientOriginalExtension();
            $filename = $this->uploadService->store($file, 'profiles', $filename);
            $data['profile_photo'] = $filename;
        }

        $member->update($data);

        return back()->with('success', 'Profil berhasil diperbarui.');
    }

    public function updatePassword(Request $request)
    {
        $member = $this->currentMember();

        $validated = $request->validate([
            'current_password' => ['required', 'current_password:member'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $member->update(['password' => $validated['password']]);

        return back()->with('success', 'Password berhasil diperbarui.');
    }

    public function storeFeedback(Request $request)
    {
        $member = $this->currentMember();

        $validated = $request->validate([
            'kategori' => ['required', 'string', 'in:bug,saran,pertanyaan,lainnya'],
            'subjek' => ['required', 'string', 'max:150'],
            'pesan' => ['required', 'string', 'max:3000'],
            'screenshot' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ], [
            'kategori.required' => 'Pilih kategori feedback.',
            'subjek.required' => 'Subjek / judul feedback wajib diisi.',
            'pesan.required' => 'Pesan / detail masukan wajib diisi.',
            'screenshot.image' => 'File lampiran harus berupa gambar (JPG, JPEG, PNG, atau WEBP).',
            'screenshot.max' => 'Ukuran gambar screenshot maksimal 5MB.',
        ]);

        $screenshotFilename = null;
        if ($request->hasFile('screenshot')) {
            $file = $request->file('screenshot');
            $screenshotFilename = 'feedback_'.time().'_'.Str::random(10).'.'.$file->getClientOriginalExtension();
            $screenshotFilename = $this->uploadService->store($file, 'feedbacks', $screenshotFilename);
        }

        Feedback::create([
            'user_id' => $member->id,
            'kategori' => $validated['kategori'],
            'subjek' => $validated['subjek'],
            'pesan' => $validated['pesan'],
            'screenshot' => $screenshotFilename,
            'status' => 'pending',
        ]);

        return back()->with('feedback_success', 'Terima kasih! Feedback Anda telah berhasil dikirimkan ke Admin HIMA TI.');
    }

    private function currentMember(): Member
    {
        return auth('member')->user();
    }

    /**
     * @return array{total: int, hadir: int, izin: int, sakit: int, alpha: int, percentage: float}
     */
    private function attendanceStats(Member $member): array
    {
        $summary = $member->attendances()
            ->join('meetings', 'meetings.id', '=', 'attendances.meeting_id')
            ->where('meetings.status', 'completed')
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN attendances.status = 'hadir' THEN 1 ELSE 0 END) as hadir,
                SUM(CASE WHEN attendances.status = 'izin' THEN 1 ELSE 0 END) as izin,
                SUM(CASE WHEN attendances.status = 'sakit' THEN 1 ELSE 0 END) as sakit,
                SUM(CASE WHEN attendances.status = 'alpha' THEN 1 ELSE 0 END) as alpha
            ")
            ->first();

        $total = (int) ($summary->total ?? 0);
        $hadir = (int) ($summary->hadir ?? 0);

        return [
            'total' => $total,
            'hadir' => $hadir,
            'izin' => (int) ($summary->izin ?? 0),
            'sakit' => (int) ($summary->sakit ?? 0),
            'alpha' => (int) ($summary->alpha ?? 0),
            'percentage' => $total > 0 ? round(($hadir / $total) * 100, 1) : 0.0,
        ];
    }

    public function announcements()
    {
        $member = $this->currentMember();
        app(SpService::class)->ensureActiveSpNotification($member);

        $announcements = Announcement::active()
            ->forMember($member)
            ->with(['admin', 'reads' => function ($q) use ($member) {
                $q->where('user_id', $member->id);
            }])
            ->orderBy('pinned', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('portal.announcements', compact('member', 'announcements'));
    }

    public function readAnnouncement(Announcement $announcement)
    {
        $member = $this->currentMember();

        $read = AnnouncementRead::firstOrCreate(
            ['announcement_id' => $announcement->id, 'user_id' => $member->id]
        );

        if (! $read->read_at) {
            $read->update(['read_at' => now()]);
        }

        return response()->json(['success' => true]);
    }

    public function dismissAnnouncement(Announcement $announcement)
    {
        $member = $this->currentMember();

        $read = AnnouncementRead::firstOrCreate(
            ['announcement_id' => $announcement->id, 'user_id' => $member->id]
        );

        $read->update([
            'is_dismissed' => true,
            'dismissed_at' => now(),
            'read_at' => $read->read_at ?? now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pengumuman ditutup dari tampilan utama.',
        ]);
    }

    public function readAllAnnouncements()
    {
        $member = $this->currentMember();
        $announcements = Announcement::active()->forMember($member)->get();
        $now = now();

        foreach ($announcements as $a) {
            AnnouncementRead::updateOrCreate(
                ['announcement_id' => $a->id, 'user_id' => $member->id],
                ['read_at' => $now]
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Semua notifikasi ditandai sudah dibaca.',
        ]);
    }

    private function deleteProfilePhoto(?string $filename): void
    {
        if (! $filename) {
            return;
        }

        $this->uploadService->delete('profiles', $filename);
    }

    private function storeProofPhoto(Request $request, Attendance $attendance, string $prefix): ?string
    {
        if (! $request->hasFile('bukti_foto')) {
            return null;
        }

        $this->deleteProofFile($attendance->bukti_foto);

        $file = $request->file('bukti_foto');
        $filename = $prefix.'_'.$attendance->meeting_id.'_'.$attendance->user_id.'_'.time().'.'.$file->getClientOriginalExtension();

        return $this->uploadService->store($file, 'bukti', $filename);
    }

    private function deleteProofFile(?string $filename): void
    {
        if (! $filename) {
            return;
        }

        $this->uploadService->delete('bukti', $filename);
    }
}
