<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Meeting;
use App\Models\Member;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class PublicAttendanceController extends Controller
{
    public function onlineAttend($uniqueLink)
    {
        $meeting = Meeting::with('creator')
            ->where('unique_link', $uniqueLink)
            ->firstOrFail();

        // Check expiry: link absensi online tetap aktif selama rapat berstatus 'scanning' (belum diselesaikan pembuat rapat).
        // Link hanya dianggap expired jika rapat sudah berstatus 'completed'/'cancelled',
        // atau jika rapat bukan 'scanning' dan waktu batas telah terlampaui.
        $start = $meeting->tanggal->copy()->setTimeFromTimeString($meeting->waktu_mulai);
        $expiryHours = (int) Setting::get('online_link_expiry_hours', '3');
        $isExpired = in_array($meeting->status, ['completed', 'cancelled'], true)
            || ($meeting->status !== 'scanning' && now()->greaterThan($start->addHours($expiryHours)));

        $attendances = $meeting->attendances()
            ->with('member')
            ->get();

        $registeredUserIds = $attendances->pluck('user_id')->toArray();

        $participantsCollection = collect();
        foreach ($attendances as $att) {
            if ($att->member) {
                $participantsCollection->push((object) [
                    'user_id' => $att->user_id,
                    'member' => $att->member,
                    'status' => $att->status,
                ]);
            }
        }

        // Jika rapat internal, lintas departemen, atau kepanitiaan, Pengurus Inti berhak hadir opsional
        if (in_array($meeting->jenis_rapat, ['internal_dept', 'lintas_dept', 'kepanitiaan'])) {
            $intiMembers = Member::where('departemen', 'Inti')
                ->whereNotIn('id', $registeredUserIds)
                ->get();

            foreach ($intiMembers as $inti) {
                $participantsCollection->push((object) [
                    'user_id' => $inti->id,
                    'member' => $inti,
                    'status' => null,
                    'is_optional_inti' => true,
                ]);
            }
        }

        $participants = $participantsCollection->sortBy(function ($a) {
            return $a->member ? $a->member->nama_lengkap : '';
        });

        return view('public.online_attend', compact('meeting', 'participants', 'isExpired'));
    }

    public function submitOnlineAttend(Request $request, $uniqueLink)
    {
        $meeting = Meeting::where('unique_link', $uniqueLink)->firstOrFail();

        if (in_array($meeting->status, ['completed', 'cancelled'], true)) {
            return back()->with('error', 'Presensi ditolak. Rapat ini telah diselesaikan atau dibatalkan dan absensi online sudah ditutup.');
        }

        $start = $meeting->tanggal->copy()->setTimeFromTimeString($meeting->waktu_mulai);
        $expiryHours = (int) Setting::get('online_link_expiry_hours', '3');
        if ($meeting->status !== 'scanning' && now()->greaterThan($start->addHours($expiryHours))) {
            return back()->with('error', 'Presensi ditolak. Batas waktu absensi untuk rapat ini telah berakhir.');
        }

        $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'status' => ['required', 'in:hadir,izin,sakit'],
            'keterangan' => ['nullable', 'string', 'max:255'],
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

        $status = $request->input('status');
        $keterangan = $request->input('keterangan');

        $attendance = Attendance::where('meeting_id', $meeting->id)
            ->where('user_id', $userId)
            ->first();

        $data = [
            'status' => $status,
            'keterangan' => $keterangan,
            'scanned_at' => now(),
            'scan_method' => 'online_link',
        ];

        if ($request->hasFile('bukti_foto')) {
            $file = $request->file('bukti_foto');
            $filename = 'bukti_'.$meeting->id.'_'.$userId.'_'.time().'.'.$file->getClientOriginalExtension();
            $targetDir = public_path('uploads/bukti');

            if (! File::isDirectory($targetDir)) {
                File::makeDirectory($targetDir, 0755, true);
            }

            $file->move($targetDir, $filename);
            $data['bukti_foto'] = $filename;
        }

        if ($attendance) {
            $attendance->update($data);
        } else {
            $data['meeting_id'] = $meeting->id;
            $data['user_id'] = $userId;
            $data['created_at'] = now();
            Attendance::create($data);
        }

        return back()->with('success', 'Absensi online berhasil dicatat! Terima kasih.'.$member->sp_attendance_notice);
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
            $targetDir = public_path('uploads/bukti');

            if (! File::isDirectory($targetDir)) {
                File::makeDirectory($targetDir, 0755, true);
            }

            $file->move($targetDir, $filename);
            $data['bukti_foto'] = $filename;
        }

        if ($attendance) {
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
