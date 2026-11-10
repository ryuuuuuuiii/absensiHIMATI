<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Meeting;
use App\Models\Member;
use App\Services\FileUploadService;
use App\Services\GamificationService;
use Illuminate\Http\Request;

class PublicAttendanceController extends Controller
{
    public function __construct(protected FileUploadService $uploadService) {}

    public function onlineAttend($uniqueLink)
    {
        $member = auth('member')->user();
        if (! $member && auth('web')->check()) {
            $member = Member::where('email', auth('web')->user()->email)->first();
        }

        if (! $member) {
            return redirect()->guest(route('login'))
                ->with('info', 'Silakan login terlebih dahulu dengan akun kepengurusan Anda untuk melakukan presensi online.');
        }

        $meeting = Meeting::with('creator')
            ->where('unique_link', $uniqueLink)
            ->firstOrFail();

        $isExpired = $meeting->isOnlineExpired();

        $eligibilityError = $meeting->checkMemberEligibility($member);

        $existingAttendance = Attendance::where('meeting_id', $meeting->id)
            ->where('user_id', $member->id)
            ->first();

        return view('public.online_attend', compact(
            'meeting',
            'member',
            'isExpired',
            'eligibilityError',
            'existingAttendance'
        ));
    }

    public function submitOnlineAttend(Request $request, $uniqueLink)
    {
        $meeting = Meeting::where('unique_link', $uniqueLink)->firstOrFail();

        if (in_array($meeting->status, ['completed', 'cancelled'], true)) {
            return back()->with('error', 'Presensi ditolak. Rapat ini telah diselesaikan atau dibatalkan dan absensi online sudah ditutup.');
        }

        if ($meeting->isOnlineExpired()) {
            return back()->with('error', 'Presensi ditolak. Batas waktu absensi untuk rapat ini telah berakhir.');
        }

        $member = auth('member')->user();
        if (! $member && auth('web')->check()) {
            $member = Member::where('email', auth('web')->user()->email)->first();
            if (! $member && $request->filled('user_id')) {
                $member = Member::find($request->input('user_id'));
            }
        }

        if (! $member) {
            return redirect()->guest(route('login'))
                ->with('info', 'Silakan login terlebih dahulu dengan akun kepengurusan Anda untuk melakukan presensi online.');
        }

        $eligibilityError = $meeting->checkMemberEligibility($member);
        if ($eligibilityError) {
            return back()->with('error', $eligibilityError);
        }

        $userId = $member->id;
        $attendance = Attendance::where('meeting_id', $meeting->id)
            ->where('user_id', $userId)
            ->first();

        $hasExistingProof = $attendance && filled($attendance->bukti_foto);
        $status = $request->input('status');
        $isProofRequired = ($status === 'hadir' || ! $status) && ! $hasExistingProof;

        $request->validate([
            'status' => ['required', 'in:hadir,izin,sakit'],
            'keterangan' => ['nullable', 'string', 'max:255'],
            'bukti_foto' => [$isProofRequired ? 'required' : 'nullable', 'image', 'max:5120'], // 5MB
        ], [
            'status.required' => 'Status kehadiran wajib dipilih.',
            'bukti_foto.required' => 'Foto bukti dokumentasi rapat online (screenshot Google Meet/Zoom atau foto kehadiran) wajib diunggah.',
            'bukti_foto.image' => 'File bukti dokumentasi harus berupa file gambar (JPG, PNG, JPEG, dll.).',
            'bukti_foto.max' => 'Ukuran file foto bukti dokumentasi maksimal 5MB.',
        ]);

        $status = $request->input('status');
        $keterangan = $request->input('keterangan');

        $data = [
            'status' => $status,
            'keterangan' => $keterangan,
            'scanned_at' => now(),
            'scan_method' => 'online_link',
        ];

        if ($request->hasFile('bukti_foto')) {
            $file = $request->file('bukti_foto');
            $filename = 'bukti_'.$meeting->id.'_'.$userId.'_'.time().'.'.$file->getClientOriginalExtension();
            $data['bukti_foto'] = $this->uploadService->store($file, 'bukti', $filename);
        }

        if ($attendance) {
            if ($status !== 'hadir' && $attendance->exp_awarded) {
                app(GamificationService::class)->revokeAttendanceExp($attendance, $member);
            }
            $attendance->update($data);
        } else {
            $data['meeting_id'] = $meeting->id;
            $data['user_id'] = $userId;
            $data['created_at'] = now();
            $attendance = Attendance::create($data);
        }

        $expNotice = '';
        if ($status === 'hadir') {
            $exp = app(GamificationService::class)->awardAttendanceExp($attendance, $member);
            if ($exp) {
                $expNotice = " (+{$exp} EXP)";
            }
        }

        return back()->with('success', 'Presensi online berhasil dicatat atas nama '.$member->nama_lengkap.'!'.$expNotice.' Terima kasih.'.$member->sp_attendance_notice);
    }

    public function absentForm($absentLink)
    {
        $meeting = Meeting::with('creator')
            ->where('absent_link', $absentLink)
            ->firstOrFail();

        $participants = $meeting->attendances()
            ->with('member')
            ->get()
            ->sortBy(function ($a) {
                return $a->member ? $a->member->nama_lengkap : '';
            });

        return view('public.absent_form', compact('meeting', 'participants'));
    }

    public function submitAbsentForm(Request $request, $absentLink)
    {
        $meeting = Meeting::where('absent_link', $absentLink)->firstOrFail();

        if (in_array($meeting->status, ['completed', 'cancelled'], true)) {
            return back()->with('error', 'Form izin ditolak. Rapat ini telah diselesaikan atau dibatalkan dan absensi sudah ditutup.');
        }

        $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'status' => ['required', 'in:izin,sakit'],
            'keterangan' => ['required', 'string', 'max:255'],
            'bukti_foto' => ['nullable', 'image', 'max:5120'], // 5MB
        ]);

        $userId = $request->input('user_id');
        $member = Member::find($userId);

        if (! $member) {
            return back()->with('error', 'Data anggota tidak ditemukan.');
        }

        $eligibilityError = $meeting->checkMemberEligibility($member);
        if ($eligibilityError) {
            return back()->with('error', $eligibilityError);
        }

        $attendance = Attendance::where('meeting_id', $meeting->id)
            ->where('user_id', $userId)
            ->first();

        if (! $attendance) {
            return back()->with('error', 'Anda tidak terdaftar sebagai peserta yang wajib hadir pada rapat ini.');
        }

        $status = $request->input('status');
        $keterangan = $request->input('keterangan');

        $data = [
            'status' => $status,
            'keterangan' => $keterangan,
            'scanned_at' => now(),
            'scan_method' => 'absent_form',
        ];

        if ($request->hasFile('bukti_foto')) {
            $file = $request->file('bukti_foto');
            $filename = 'surat_'.$meeting->id.'_'.$userId.'_'.time().'.'.$file->getClientOriginalExtension();
            $data['bukti_foto'] = $this->uploadService->store($file, 'bukti', $filename);
        }

        if ($attendance) {
            if ($status !== 'hadir' && $attendance->exp_awarded) {
                app(GamificationService::class)->revokeAttendanceExp($attendance, $member);
            }
            $attendance->update($data);
        } else {
            $data['meeting_id'] = $meeting->id;
            $data['user_id'] = $userId;
            $data['created_at'] = now();
            Attendance::create($data);
        }

        return back()->with('success', 'Form keterangan ketidakhadiran berhasil dikirim.'.$member->sp_attendance_notice);
    }
}
