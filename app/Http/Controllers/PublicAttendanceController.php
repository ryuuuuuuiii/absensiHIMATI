<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Meeting;
use App\Models\Member;
use App\Services\FileUploadService;
use App\Services\GamificationService;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

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

        // Konversi fallback jika bukti foto dikirim dari kamera WebRTC sebagai data base64
        if (! $request->files->has('bukti_foto') && $request->filled('bukti_foto_base64')) {
            $base64Data = (string) $request->input('bukti_foto_base64');
            if (preg_match('/^data:image\/(\w+);base64,/', $base64Data, $type)) {
                $rawBase64 = substr($base64Data, strpos($base64Data, ',') + 1);
                $ext = strtolower($type[1]) === 'jpeg' ? 'jpg' : strtolower($type[1]);
                if (in_array($ext, ['jpg', 'png', 'webp'])) {
                    $binary = base64_decode($rawBase64);
                    if ($binary !== false) {
                        $tempPath = tempnam(sys_get_temp_dir(), 'camera_');
                        file_put_contents($tempPath, $binary);
                        $file = new UploadedFile($tempPath, 'camera_capture_'.time().'.'.$ext, 'image/'.$ext, null, true);
                        $request->files->set('bukti_foto', $file);
                        (function () use ($file) {
                            $this->convertedFiles['bukti_foto'] = $file;
                        })->call($request);
                    }
                }
            }
        }

        $userId = $member->id;
        $attendance = Attendance::where('meeting_id', $meeting->id)
            ->where('user_id', $userId)
            ->first();

        $hasExistingProof = $attendance && filled($attendance->bukti_foto);
        $status = $request->input('status');
        $isProofRequired = ($status === 'hadir' || ! $status) && ! $hasExistingProof;

        $isFaceRequired = $meeting->isKegiatan() && $meeting->mode_rapat === 'offline' && $status === 'hadir';

        $proofMessage = match (true) {
            $meeting->isKegiatan() && $meeting->mode_rapat === 'offline' => 'Foto kehadiran di lokasi kegiatan (wajah Anda harus terlihat jelas) wajib diunggah.',
            $meeting->isKegiatan() => 'Screenshot Zoom/Google Meet dengan kamera menyala (wajah terlihat) wajib diunggah.',
            default => 'Foto bukti dokumentasi rapat online (screenshot Google Meet/Zoom atau foto kehadiran) wajib diunggah.',
        };

        $request->validate([
            'status' => ['required', 'in:hadir,izin,sakit'],
            'keterangan' => ['nullable', 'string', 'max:255'],
            'bukti_foto' => [$isProofRequired ? 'required' : 'nullable', 'image', 'max:5120'], // 5MB
            'konfirmasi_wajah' => [$isFaceRequired ? 'accepted' : 'nullable'],
        ], [
            'status.required' => 'Status kehadiran wajib dipilih.',
            'bukti_foto.required' => $proofMessage,
            'bukti_foto.image' => 'File bukti dokumentasi harus berupa file gambar (JPG, PNG, JPEG, dll.).',
            'bukti_foto.max' => 'Ukuran file foto bukti dokumentasi maksimal 5MB.',
            'konfirmasi_wajah.accepted' => 'Centang pernyataan bahwa wajah Anda terlihat jelas pada foto.',
        ]);

        $status = $request->input('status');
        $keterangan = $request->input('keterangan');

        $data = [
            'status' => $status,
            'keterangan' => $keterangan,
            'scanned_at' => now(),
            'scan_method' => $meeting->isKegiatan() ? 'foto_kegiatan' : 'online_link',
        ];

        if ($meeting->isKegiatan()) {
            $data['sp_ignored'] = true;
        }

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
                $freshMember = $member->fresh();
                session()->flash('exp_popup', [
                    'exp' => $exp,
                    'reason' => $meeting->isKegiatan() ? 'Presensi Kegiatan' : 'Presensi Kehadiran',
                    'total_exp' => (int) $freshMember->exp,
                    'level_data' => $freshMember->level_data,
                ]);
            }
        }

        $successLabel = $meeting->isKegiatan() ? 'Presensi kegiatan' : 'Presensi online';

        return back()->with('success', $successLabel.' berhasil dicatat atas nama '.$member->nama_lengkap.'!'.$expNotice.' Terima kasih.'.$member->sp_attendance_notice);
    }

    public function absentForm($absentLink)
    {
        $member = auth('member')->user();
        if (! $member && auth('web')->check()) {
            $member = Member::where('email', auth('web')->user()->email)->first();
        }

        if (! $member) {
            return redirect()->guest(route('login'))
                ->with('info', 'Silakan login terlebih dahulu dengan akun kepengurusan Anda untuk mengajukan izin/sakit.');
        }

        $meeting = Meeting::with('creator')
            ->where('absent_link', $absentLink)
            ->firstOrFail();

        $eligibilityError = $meeting->checkMemberEligibility($member);

        $existingAttendance = Attendance::where('meeting_id', $meeting->id)
            ->where('user_id', $member->id)
            ->first();

        return view('public.absent_form', compact('meeting', 'member', 'eligibilityError', 'existingAttendance'));
    }

    public function submitAbsentForm(Request $request, $absentLink)
    {
        $meeting = Meeting::where('absent_link', $absentLink)->firstOrFail();

        if (in_array($meeting->status, ['completed', 'cancelled'], true)) {
            return back()->with('error', 'Form izin ditolak. Rapat ini telah diselesaikan atau dibatalkan dan absensi sudah ditutup.');
        }

        $member = auth('member')->user();
        if (! $member && auth('web')->check()) {
            $member = Member::where('email', auth('web')->user()->email)->first();
        }

        if (! $member) {
            return redirect()->guest(route('login'))
                ->with('info', 'Silakan login terlebih dahulu dengan akun kepengurusan Anda untuk mengajukan izin/sakit.');
        }

        $eligibilityError = $meeting->checkMemberEligibility($member);
        if ($eligibilityError) {
            return back()->with('error', $eligibilityError);
        }

        // Konversi fallback jika bukti foto dikirim dari kamera WebRTC sebagai data base64
        if (! $request->files->has('bukti_foto') && $request->filled('bukti_foto_base64')) {
            $base64Data = (string) $request->input('bukti_foto_base64');
            if (preg_match('/^data:image\/(\w+);base64,/', $base64Data, $type)) {
                $rawBase64 = substr($base64Data, strpos($base64Data, ',') + 1);
                $ext = strtolower($type[1]) === 'jpeg' ? 'jpg' : strtolower($type[1]);
                if (in_array($ext, ['jpg', 'png', 'webp'])) {
                    $binary = base64_decode($rawBase64);
                    if ($binary !== false) {
                        $tempPath = tempnam(sys_get_temp_dir(), 'camera_');
                        file_put_contents($tempPath, $binary);
                        $file = new UploadedFile($tempPath, 'camera_capture_'.time().'.'.$ext, 'image/'.$ext, null, true);
                        $request->files->set('bukti_foto', $file);
                        (function () use ($file) {
                            $this->convertedFiles['bukti_foto'] = $file;
                        })->call($request);
                    }
                }
            }
        }

        $request->validate([
            'status' => ['required', 'in:izin,sakit'],
            'keterangan' => ['required', 'string', 'max:255'],
            'bukti_foto' => ['nullable', 'image', 'max:5120'], // 5MB
        ], [
            'status.required' => 'Status ketidakhadiran (Izin / Sakit) wajib dipilih.',
            'keterangan.required' => 'Keterangan atau alasan tidak hadir wajib diisi.',
            'bukti_foto.image' => 'File bukti/surat harus berupa gambar (JPG, JPEG, PNG).',
            'bukti_foto.max' => 'Ukuran file bukti maksimal 5MB.',
        ]);

        $userId = $member->id;
        $attendance = Attendance::where('meeting_id', $meeting->id)
            ->where('user_id', $userId)
            ->first();

        $status = $request->input('status');
        $keterangan = $request->input('keterangan');

        $data = [
            'status' => $status,
            'keterangan' => $keterangan,
            'scanned_at' => now(),
            'scan_method' => 'absent_form',
        ];

        if ($meeting->isKegiatan()) {
            $data['sp_ignored'] = true;
        }

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

        return back()->with('success', 'Form keterangan ketidakhadiran berhasil dikirim atas nama '.$member->nama_lengkap.'.'.$member->sp_attendance_notice);
    }
}
