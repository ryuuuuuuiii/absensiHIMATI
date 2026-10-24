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

        // Check expiry
        $start = $meeting->tanggal->copy()->setTimeFromTimeString($meeting->waktu_mulai);
        $expiryHours = (int) Setting::get('online_link_expiry_hours', '3');
        $isExpired = now()->greaterThan($start->addHours($expiryHours)) && $meeting->status === 'completed';

        $participants = $meeting->attendances()
            ->with('member')
            ->get()
            ->sortBy(function ($a) {
                return $a->member ? $a->member->nama_lengkap : '';
            });

        $logo1 = Setting::get('logo_1');
        $logo2 = Setting::get('logo_2');
        $logo3 = Setting::get('logo_3');

        return view('public.online_attend', compact('meeting', 'participants', 'isExpired', 'logo1', 'logo2', 'logo3'));
    }

    public function submitOnlineAttend(Request $request, $uniqueLink)
    {
        $meeting = Meeting::where('unique_link', $uniqueLink)->firstOrFail();

        $request->validate([
            'user_id'    => ['required', 'exists:users,id'],
            'status'     => ['required', 'in:hadir,izin,sakit'],
            'keterangan' => ['nullable', 'string', 'max:255'],
            'bukti_foto' => ['nullable', 'image', 'max:5120'], // 5MB
        ]);

        $userId = $request->input('user_id');
        $status = $request->input('status');
        $keterangan = $request->input('keterangan');

        $attendance = Attendance::where('meeting_id', $meeting->id)
            ->where('user_id', $userId)
            ->first();

        $data = [
            'status'      => $status,
            'keterangan'  => $keterangan,
            'scanned_at'  => now(),
            'scan_method' => 'online_link',
        ];

        if ($request->hasFile('bukti_foto')) {
            $file = $request->file('bukti_foto');
            $filename = 'bukti_' . $meeting->id . '_' . $userId . '_' . time() . '.' . $file->getClientOriginalExtension();
            $targetDir = public_path('uploads/bukti');

            if (!File::isDirectory($targetDir)) {
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

        return back()->with('success', 'Absensi online berhasil dicatat! Terima kasih.');
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

        $logo1 = Setting::get('logo_1');
        $logo2 = Setting::get('logo_2');
        $logo3 = Setting::get('logo_3');

        return view('public.absent_form', compact('meeting', 'participants', 'logo1', 'logo2', 'logo3'));
    }

    public function submitAbsentForm(Request $request, $absentLink)
    {
        $meeting = Meeting::where('absent_link', $absentLink)->firstOrFail();

        $request->validate([
            'user_id'    => ['required', 'exists:users,id'],
            'status'     => ['required', 'in:izin,sakit'],
            'keterangan' => ['required', 'string', 'max:255'],
            'bukti_foto' => ['nullable', 'image', 'max:5120'], // 5MB
        ]);

        $userId = $request->input('user_id');
        $status = $request->input('status');
        $keterangan = $request->input('keterangan');

        $attendance = Attendance::where('meeting_id', $meeting->id)
            ->where('user_id', $userId)
            ->first();

        $data = [
            'status'      => $status,
            'keterangan'  => $keterangan,
            'scanned_at'  => now(),
            'scan_method' => 'absent_form',
        ];

        if ($request->hasFile('bukti_foto')) {
            $file = $request->file('bukti_foto');
            $filename = 'surat_' . $meeting->id . '_' . $userId . '_' . time() . '.' . $file->getClientOriginalExtension();
            $targetDir = public_path('uploads/bukti');

            if (!File::isDirectory($targetDir)) {
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

        return back()->with('success', 'Form keterangan ketidakhadiran berhasil dikirim.');
    }
}
