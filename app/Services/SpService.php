<?php

namespace App\Services;

use App\Models\Announcement;
use App\Models\AnnouncementRead;
use App\Models\Attendance;
use App\Models\Meeting;
use App\Models\Member;

class SpService
{
    /**
     * Hitung level SP dan jumlah alpha berturut-turut saat ini dari riwayat rapat selesai.
     *
     * @return array{sp_level: int, consecutive_alphas: int}
     */
    public function calculateStreakAndLevel(Member $member): array
    {
        $attendances = Attendance::query()
            ->join('meetings', 'meetings.id', '=', 'attendances.meeting_id')
            ->where('attendances.user_id', $member->id)
            ->where('attendances.sp_ignored', false)
            ->where('meetings.status', 'completed')
            ->orderBy('meetings.tanggal', 'asc')
            ->orderBy('meetings.waktu_mulai', 'asc')
            ->orderBy('meetings.id', 'asc')
            ->orderBy('attendances.id', 'asc')
            ->select('attendances.id', 'attendances.status', 'attendances.meeting_id')
            ->with('meeting')
            ->get();

        $spamProtection = app(MeetingSpamProtectionService::class);
        $calculatedSp = (int) ($member->sp_base_level ?? 0);
        $streak = 0;

        foreach ($attendances as $attendance) {
            if ($attendance->meeting && ! $spamProtection->isMeetingValidForSp($attendance->meeting)) {
                continue;
            }

            if ($attendance->status === 'alpha') {
                $streak++;
                if ($streak >= 3) {
                    if ($calculatedSp < 3) {
                        $calculatedSp++;
                    }
                    $streak = 0;
                }
            } else {
                $streak = 0;
            }
        }

        $finalSp = min(3, max((int) $member->sp_level, $calculatedSp));

        return [
            'sp_level' => $finalSp,
            'consecutive_alphas' => $finalSp >= 3 ? 0 : $streak,
        ];
    }

    /**
     * Ambil jumlah alpha berturut-turut aktif menuju SP berikutnya (0, 1, atau 2).
     */
    public function getCurrentAlphaStreak(Member $member): int
    {
        return $this->calculateStreakAndLevel($member)['consecutive_alphas'];
    }

    /**
     * Evaluasi otomatis status SP pengurus berdasarkan riwayat kehadiran pada rapat yang telah selesai.
     */
    public function evaluateMember(Member $member): Member
    {
        $result = $this->calculateStreakAndLevel($member);
        $newLevel = $result['sp_level'];
        $oldLevel = (int) $member->sp_level;

        if ($newLevel > $oldLevel) {
            $reason = match ($newLevel) {
                1 => 'Otomatis: Alpha dalam kegiatan selama 3 kali berturut-turut',
                2 => 'Otomatis: Pelanggaran berulang (Alpha 3 kali berturut-turut pada SP 1)',
                default => 'Otomatis: Pemberhentian pengurus (Alpha 3 kali berturut-turut berulang setelah SP 2)',
            };

            $detail = match ($newLevel) {
                1 => 'Pengurus tercatat tidak hadir tanpa keterangan (Alpha) pada 3 kegiatan/rapat yang telah selesai secara berturut-turut.',
                2 => 'Pengurus yang telah berstatus SP 1 kembali tercatat tidak hadir tanpa keterangan (Alpha) pada 3 kegiatan/rapat secara berturut-turut.',
                default => 'Pengurus yang telah berstatus SP 2 kembali tercatat tidak hadir tanpa keterangan (Alpha) pada 3 kegiatan/rapat secara berturut-turut sehingga diberhentikan dan akun dinonaktifkan.',
            };

            $updateData = [
                'sp_level' => $newLevel,
                'is_active' => $newLevel < 3,
                'sp_reason' => $reason,
                'sp_detail' => $detail,
                'sp_issued_at' => now(),
            ];

            if ($newLevel >= 3) {
                $updateData['remember_token'] = null;
            }

            $member->forceFill($updateData)->saveQuietly();
            $this->syncSpNotification($member, $oldLevel);
        } elseif ($newLevel >= 3 && $member->is_active) {
            $member->forceFill([
                'is_active' => false,
                'remember_token' => null,
            ])->saveQuietly();
        }

        return $member;
    }

    /**
     * Evaluasi seluruh peserta pada rapat yang telah selesai.
     */
    public function evaluateMeeting(Meeting $meeting): void
    {
        if ($meeting->status !== 'completed') {
            return;
        }

        if (! app(MeetingSpamProtectionService::class)->isMeetingValidForSp($meeting)) {
            return;
        }

        $attendances = $meeting->attendances()->with('member')->get();

        foreach ($attendances as $attendance) {
            if ($attendance->member) {
                $this->evaluateMember($attendance->member);
            }
        }
    }

    /**
     * Tambahkan 1 tingkat SP oleh Admin (0 -> SP 1 -> SP 2 -> SP 3 Pemecatan & Nonaktif).
     */
    public function addSpByAdmin(Member $member, ?string $reason = null, ?string $detail = null): Member
    {
        $currentLevel = (int) $member->sp_level;
        $newLevel = min(3, $currentLevel + 1);

        $defaultReason = match ($newLevel) {
            1 => 'Melakukan pelanggaran disiplin/aturan organisasi (SP 1 diberikan oleh Admin)',
            2 => 'Melakukan pelanggaran berulang (Naik dari SP 1 ke SP 2 oleh Admin)',
            default => 'Pemberhentian pengurus atas pelanggaran berat/berulang (SP 3 oleh Admin — Akun Dinonaktifkan)',
        };

        return $this->setSpByAdmin($member, $newLevel, filled($reason) ? $reason : $defaultReason, $detail);
    }

    /**
     * Tetapkan tingkat SP secara langsung oleh Admin (0 s/d 3).
     */
    public function setSpByAdmin(Member $member, int $targetLevel, ?string $reason = null, ?string $detail = null): Member
    {
        $oldLevel = (int) $member->sp_level;
        $newLevel = max(0, min(3, $targetLevel));

        $this->ignoreCompletedAttendances($member);

        if ($newLevel === 0) {
            $member->forceFill([
                'sp_level' => 0,
                'sp_base_level' => 0,
                'is_active' => true,
                'sp_reason' => null,
                'sp_detail' => null,
                'sp_issued_at' => null,
            ])->saveQuietly();

            $this->syncSpNotification($member, $oldLevel);

            return $member;
        }

        $defaultReason = match ($newLevel) {
            1 => 'Melakukan pelanggaran disiplin/aturan organisasi (SP 1 diberikan oleh Admin)',
            2 => 'Melakukan pelanggaran disiplin/aturan organisasi (SP 2 ditetapkan oleh Admin)',
            default => 'Pemberhentian pengurus atas pelanggaran berat/berulang (SP 3 oleh Admin — Akun Dinonaktifkan)',
        };

        $defaultDetail = match ($newLevel) {
            1 => 'Pengurus diberikan Surat Peringatan Pertama (SP 1) oleh Admin karena melanggar ketentuan, disiplin, atau tanggung jawab kepengurusan HIMA TI.',
            2 => 'Pengurus diberikan Surat Peringatan Kedua (SP 2) oleh Admin karena melakukan pelanggaran berulang terhadap aturan kepengurusan HIMA TI.',
            default => 'Pengurus diberhentikan dari kepengurusan HIMA TI (SP 3) dan akun dinonaktifkan karena pelanggaran berat atau pelanggaran berulang setelah SP 2.',
        };

        $existingAdminReason = (! str_starts_with((string) $member->sp_reason, 'Otomatis:'))
            ? $member->sp_reason
            : null;

        $existingAdminDetail = (! $member->isSpFromAlpha())
            ? $member->sp_detail
            : null;

        $finalReason = filled($reason)
            ? trim($reason)
            : ($existingAdminReason ?: $defaultReason);

        $finalDetail = filled($detail)
            ? trim($detail)
            : ($existingAdminDetail ?: $defaultDetail);

        $updateData = [
            'sp_level' => $newLevel,
            'sp_base_level' => $newLevel,
            'is_active' => $newLevel < 3,
            'sp_reason' => $finalReason,
            'sp_detail' => $finalDetail,
            'sp_issued_at' => now(),
        ];

        if ($newLevel >= 3) {
            $updateData['remember_token'] = null;
        }

        $member->forceFill($updateData)->saveQuietly();
        $this->syncSpNotification($member, $oldLevel);

        return $member;
    }

    /**
     * Hapus / cabut status SP pengurus oleh Admin (kembali ke Aman & Aktif).
     */
    public function removeSpByAdmin(Member $member): Member
    {
        return $this->setSpByAdmin($member, 0);
    }

    /**
     * Pastikan pengurus yang memiliki SP aktif memiliki pesan rincian SP di halaman Notifikasi.
     */
    public function ensureActiveSpNotification(Member $member): void
    {
        if ((int) $member->sp_level <= 0) {
            return;
        }

        $existing = Announcement::query()
            ->where('target_type', 'personal')
            ->where('target_user_id', $member->id)
            ->where('is_active', true)
            ->where('pinned', true)
            ->where('judul', 'like', '[SURAT PERINGATAN]%')
            ->latest('id')
            ->first();

        if (! $existing) {
            $this->syncSpNotification($member, (int) $member->sp_level);
        }
    }

    /**
     * Buat atau perbarui pesan rincian SP pada Notifikasi Personal Pengurus.
     */
    public function syncSpNotification(Member $member, int $previousLevel = 0): void
    {
        $adminId = auth('web')->check() ? auth('web')->id() : null;
        $currentLevel = (int) $member->sp_level;

        if ($currentLevel <= 0) {
            Announcement::query()
                ->where('target_type', 'personal')
                ->where('target_user_id', $member->id)
                ->where('judul', 'like', '[SURAT PERINGATAN]%')
                ->update(['pinned' => false]);

            if ($previousLevel > 0) {
                $nowText = now()->locale('id')->translatedFormat('d M Y, H:i');

                Announcement::create([
                    'admin_id' => $adminId,
                    'judul' => "[SURAT PERINGATAN] Pencabutan Status SP {$previousLevel} — Akun Kembali Aman",
                    'konten' => "PEMBERITAHUAN PENCABUTAN SURAT PERINGATAN (SP)\n\n"
                        ."Halo {$member->nama_lengkap},\n"
                        ."Status Surat Peringatan (SP {$previousLevel}) pada akun Anda telah dicabut oleh Admin HIMA TI.\n\n"
                        ."• Status Akun Saat Ini : Aman (Tanpa SP) & Aktif\n"
                        ."• Waktu Pencabutan : {$nowText} WITA\n\n"
                        .'Tetap jaga kedisiplinan dan patuhi seluruh peraturan kepengurusan HIMA TI.',
                    'tipe' => 'info',
                    'target_type' => 'personal',
                    'target_user_id' => $member->id,
                    'is_banner' => false,
                    'is_active' => true,
                    'pinned' => false,
                ]);
            }

            return;
        }

        $payload = $this->buildSpNotificationPayload($member);
        $tipe = $currentLevel >= 2 ? 'mendesak' : 'penting';

        $existingSameLevel = ($previousLevel === $currentLevel)
            ? Announcement::query()
                ->where('target_type', 'personal')
                ->where('target_user_id', $member->id)
                ->where('is_active', true)
                ->where('pinned', true)
                ->where('judul', 'like', '[SURAT PERINGATAN]%')
                ->latest('id')
                ->first()
            : null;

        if ($existingSameLevel) {
            $existingSameLevel->update([
                'admin_id' => $adminId ?? $existingSameLevel->admin_id,
                'judul' => $payload['judul'],
                'konten' => $payload['konten'],
                'tipe' => $tipe,
            ]);

            AnnouncementRead::query()
                ->where('announcement_id', $existingSameLevel->id)
                ->where('user_id', $member->id)
                ->delete();

            return;
        }

        Announcement::query()
            ->where('target_type', 'personal')
            ->where('target_user_id', $member->id)
            ->where('judul', 'like', '[SURAT PERINGATAN]%')
            ->update(['pinned' => false]);

        Announcement::create([
            'admin_id' => $adminId,
            'judul' => $payload['judul'],
            'konten' => $payload['konten'],
            'tipe' => $tipe,
            'target_type' => 'personal',
            'target_user_id' => $member->id,
            'is_banner' => false,
            'is_active' => true,
            'pinned' => true,
        ]);
    }

    /**
     * Susun judul dan pesan rincian SP untuk halaman Notifikasi sesuai penyebab SP (Admin / 3x Alpha).
     *
     * @return array{judul: string, konten: string}
     */
    protected function buildSpNotificationPayload(Member $member): array
    {
        $issuedAt = ($member->sp_issued_at ?? now())->locale('id')->translatedFormat('d M Y, H:i');

        if ($member->isSpFromAlpha()) {
            $judul = "[SURAT PERINGATAN] {$member->sp_full_label} (Tidak Hadir 3x Berturut-turut)";
            $konten = "RINCIAN SURAT PERINGATAN PENGURUS HIMA TI\n\n"
                ."Halo {$member->nama_lengkap} ({$member->jabatan} — Dept. {$member->departemen}),\n"
                ."Sistem menerbitkan {$member->sp_full_label} pada akun Anda karena ketidakhadiran kegiatan dengan rincian berikut:\n\n"
                ."• Status Akun : {$member->sp_full_label}\n"
                ."• Pelanggaran : {$member->sp_reason_display}\n"
                ."• Rincian : {$member->sp_detail_display}\n"
                ."• Waktu Diterbitkan : {$issuedAt} WITA\n\n"
                ."PERINGATAN:\n"
                .$member->sp_warning_text;

            return [
                'judul' => $judul,
                'konten' => $konten,
            ];
        }

        $judul = "[SURAT PERINGATAN] {$member->sp_full_label} (Pelanggaran)";
        $konten = "RINCIAN SURAT PERINGATAN PENGURUS HIMA TI\n\n"
            ."Halo {$member->nama_lengkap} ({$member->jabatan} — Dept. {$member->departemen}),\n"
            ."Admin HIMA TI telah menetapkan {$member->sp_full_label} pada akun Anda karena melakukan pelanggaran dengan rincian berikut:\n\n"
            ."• Status Akun : {$member->sp_full_label}\n"
            ."• Sumber SP : Diberikan langsung oleh Admin (Pelanggaran)\n"
            ."• Pelanggaran : {$member->sp_reason_display}\n"
            ."• Rincian Pelanggaran : {$member->sp_detail_display}\n"
            ."• Waktu Diterbitkan : {$issuedAt} WITA\n\n"
            ."PERINGATAN:\n"
            .$member->sp_warning_text;

        return [
            'judul' => $judul,
            'konten' => $konten,
        ];
    }

    /**
     * Tandai seluruh riwayat kehadiran pada rapat selesai sebelumnya sebagai sudah dievaluasi
     * agar tidak terhitung ulang setelah perubahan atau pencabutan SP oleh Admin.
     */
    protected function ignoreCompletedAttendances(Member $member): void
    {
        $completedIds = Attendance::query()
            ->where('user_id', $member->id)
            ->where('sp_ignored', false)
            ->whereHas('meeting', fn ($query) => $query->where('status', 'completed'))
            ->pluck('id');

        if ($completedIds->isNotEmpty()) {
            Attendance::whereIn('id', $completedIds)->update(['sp_ignored' => true]);
        }
    }
}
