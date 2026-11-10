<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Event;
use App\Models\Member;
use App\Models\MemberBadge;
use Illuminate\Support\Facades\DB;

class GamificationService
{
    public const ATTENDANCE_EXP = 50;

    public const EVENT_LEADER_EXP = 500;

    public const EVENT_COORDINATOR_EXP = 350;

    public const EVENT_STAFF_EXP = 250;

    /**
     * Memberikan EXP kehadiran saat pengurus hadir rapat.
     * Mengembalikan jumlah EXP yang diberikan, atau null jika sudah pernah diberikan.
     */
    public function awardAttendanceExp(Attendance $attendance, ?Member $member = null): ?int
    {
        if ($attendance->status !== 'hadir') {
            return null;
        }

        if ($attendance->exp_awarded) {
            return null;
        }

        $user = $member ?? $attendance->member ?? Member::find($attendance->user_id);
        if (! $user) {
            return null;
        }

        $expAmount = self::ATTENDANCE_EXP;

        DB::transaction(function () use ($attendance, $user, $expAmount) {
            $user->increment('exp', $expAmount);
            $attendance->update(['exp_awarded' => true]);
            cache()->forget("portal_stats_{$user->id}");
        });

        return $expAmount;
    }

    /**
     * Mengurangi EXP jika status kehadiran diubah dari 'hadir' menjadi status lain.
     */
    public function revokeAttendanceExp(Attendance $attendance, ?Member $member = null): ?int
    {
        if (! $attendance->exp_awarded) {
            return null;
        }

        $user = $member ?? $attendance->member ?? Member::find($attendance->user_id);
        if (! $user) {
            return null;
        }

        $expAmount = self::ATTENDANCE_EXP;

        DB::transaction(function () use ($attendance, $user, $expAmount) {
            $user->update([
                'exp' => max(0, ((int) $user->exp) - $expAmount),
            ]);
            $attendance->update(['exp_awarded' => false]);
            cache()->forget("portal_stats_{$user->id}");
        });

        return $expAmount;
    }

    /**
     * Menghitung besaran EXP berdasarkan jabatan panitia dalam event.
     */
    public function calculateEventMemberExp(?string $jabatan): int
    {
        $role = strtolower(trim($jabatan ?? ''));

        // Tingkat 1: Penanggung Jawab, Ketua Pelaksana, Wakil Pelaksana (+500 EXP)
        if (
            str_contains($role, 'ketua') ||
            str_contains($role, 'project officer') ||
            str_contains($role, 'leader') ||
            str_contains($role, 'penanggung jawab') ||
            str_contains($role, 'penanggungjawab') ||
            $role === 'pj' ||
            str_contains($role, 'pj ') ||
            str_contains($role, 'wakil') ||
            str_contains($role, 'vice')
        ) {
            return self::EVENT_LEADER_EXP;
        }

        // Tingkat 2: Koordinator Divisi (Koor), Sekretaris, Bendahara, BPH (+350 EXP)
        if (
            str_contains($role, 'koor') ||
            str_contains($role, 'sekretaris') ||
            str_contains($role, 'sekre') ||
            str_contains($role, 'bendahara') ||
            str_contains($role, 'bendum') ||
            str_contains($role, 'bph')
        ) {
            return self::EVENT_COORDINATOR_EXP;
        }

        // Tingkat 3: Anggota Panitia (+250 EXP)
        return self::EVENT_STAFF_EXP;
    }

    /**
     * Menyelesaikan event, memberikan EXP bernilai besar dan lencana kepanitiaan ke semua panitia.
     */
    public function completeEvent(Event $event): int
    {
        return DB::transaction(function () use ($event) {
            $event->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            $eventMembers = $event->members()->with(['division', 'member'])->get();
            $awardedCount = 0;

            foreach ($eventMembers as $em) {
                if (! $em->member) {
                    continue;
                }

                $user = $em->member;
                $jabatan = $em->jabatan_panitia ?: 'Anggota Panitia';
                $divisi = $em->division?->nama_divisi;
                $exp = $this->calculateEventMemberExp($jabatan);

                // Cek apakah lencana untuk event ini sudah pernah dibuat sebelumnya
                $existingBadge = MemberBadge::where('user_id', $user->id)
                    ->where('event_id', $event->id)
                    ->first();

                if (! $existingBadge) {
                    MemberBadge::create([
                        'user_id' => $user->id,
                        'event_id' => $event->id,
                        'event_name' => $event->nama_event,
                        'jabatan' => $jabatan,
                        'divisi' => $divisi,
                        'badge_type' => 'event_completion',
                        'exp_earned' => $exp,
                    ]);

                    $user->increment('exp', $exp);
                    cache()->forget("portal_stats_{$user->id}");
                    $awardedCount++;
                }
            }

            return $awardedCount;
        });
    }

    /**
     * Membuka kembali event yang telah diselesaikan (menarik kembali lencana dan bonus EXP).
     */
    public function reopenEvent(Event $event): int
    {
        return DB::transaction(function () use ($event) {
            $event->update([
                'status' => 'active',
                'completed_at' => null,
            ]);

            $badges = MemberBadge::where('event_id', $event->id)->get();
            $revertedCount = 0;

            foreach ($badges as $badge) {
                $user = Member::find($badge->user_id);
                if ($user) {
                    $user->update([
                        'exp' => max(0, ((int) $user->exp) - (int) $badge->exp_earned),
                    ]);
                    cache()->forget("portal_stats_{$user->id}");
                }
                $badge->delete();
                $revertedCount++;
            }

            return $revertedCount;
        });
    }
}
