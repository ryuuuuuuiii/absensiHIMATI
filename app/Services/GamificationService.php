<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Event;
use App\Models\Member;
use App\Models\MemberBadge;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class GamificationService
{
    public const ATTENDANCE_EXP = 100;

    public const EVENT_LEADER_EXP = 900;

    public const EVENT_COORDINATOR_EXP = 600;

    public const EVENT_STAFF_EXP = 300;

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
     * Inti / Pimpinan: +900 EXP (+3 Level)
     * Koordinator / BPH: +600 EXP (+2 Level)
     * Anggota Panitia: +300 EXP (+1 Level)
     */
    public function calculateEventMemberExp(?string $jabatan): int
    {
        $role = strtolower(trim($jabatan ?? ''));

        // Tingkat 1: Penanggung Jawab, Ketua Pelaksana, Wakil Pelaksana (+900 EXP / +3 Level)
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

        // Tingkat 2: Koordinator Divisi (Koor), Sekretaris, Bendahara, BPH (+600 EXP / +2 Level)
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

        // Tingkat 3: Anggota Panitia (+300 EXP / +1 Level)
        return self::EVENT_STAFF_EXP;
    }

    /**
     * Menghitung track record keaktifan seorang pengurus dalam suatu event berdasarkan kehadiran rapat event.
     */
    public function calculateEventMemberTrackRecord(Event $event, int $userId): array
    {
        $meetings = $event->relationLoaded('meetings')
            ? $event->meetings
            : $event->meetings()->select('id', 'fokus_bahasan', 'tanggal', 'status')->get();

        $totalMeetings = $meetings->count();

        if ($totalMeetings === 0) {
            return [
                'total_meetings' => 0,
                'attended_count' => 0,
                'izin_count' => 0,
                'sakit_count' => 0,
                'alpha_count' => 0,
                'attendance_rate' => 0.0,
                'status_label' => 'Belum Ada Rapat',
                'status_class' => 'neutral',
                'badge_color' => '#64748B',
                'badge_bg' => 'rgba(100, 116, 139, 0.15)',
                'icon' => 'fa-info-circle',
                'summary_text' => 'Belum ada rapat kepanitiaan',
            ];
        }

        $meetingIds = $meetings->pluck('id')->all();

        $attendances = Attendance::whereIn('meeting_id', $meetingIds)
            ->where('user_id', $userId)
            ->get();

        $attendedCount = $attendances->where('status', 'hadir')->count();
        $izinCount = $attendances->where('status', 'izin')->count();
        $sakitCount = $attendances->where('status', 'sakit')->count();
        $alphaCount = $attendances->where('status', 'alpha')->count();

        $rate = round(($attendedCount / $totalMeetings) * 100, 1);

        if ($rate >= 100.0) {
            $label = 'Sangat Aktif';
            $class = 'success';
            $color = '#10B981';
            $bg = 'rgba(16, 185, 129, 0.15)';
            $icon = 'fa-check-double';
        } elseif ($rate >= 75.0) {
            $label = 'Aktif';
            $class = 'info';
            $color = '#3B82F6';
            $bg = 'rgba(59, 130, 246, 0.15)';
            $icon = 'fa-check';
        } elseif ($rate >= 50.0) {
            $label = 'Cukup Aktif';
            $class = 'warning';
            $color = '#F59E0B';
            $bg = 'rgba(245, 158, 11, 0.15)';
            $icon = 'fa-exclamation-circle';
        } elseif ($rate > 0.0) {
            $label = 'Kurang Aktif';
            $class = 'orange';
            $color = '#F97316';
            $bg = 'rgba(249, 115, 22, 0.15)';
            $icon = 'fa-exclamation-triangle';
        } else {
            $label = 'Tidak Aktif';
            $class = 'danger';
            $color = '#EF4444';
            $bg = 'rgba(239, 68, 68, 0.15)';
            $icon = 'fa-times-circle';
        }

        $summaryText = "{$attendedCount}/{$totalMeetings} Rapat ({$rate}%) — {$label}";

        return [
            'total_meetings' => $totalMeetings,
            'attended_count' => $attendedCount,
            'izin_count' => $izinCount,
            'sakit_count' => $sakitCount,
            'alpha_count' => $alphaCount,
            'attendance_rate' => $rate,
            'status_label' => $label,
            'status_class' => $class,
            'badge_color' => $color,
            'badge_bg' => $bg,
            'icon' => $icon,
            'summary_text' => $summaryText,
        ];
    }

    /**
     * Menghitung track record secara efisien untuk banyak pengurus sekaligus dalam suatu event (mencegah N+1 query).
     */
    public function getEventMembersTrackRecords(Event $event, ?array $userIds = null): array
    {
        $meetings = $event->relationLoaded('meetings')
            ? $event->meetings
            : $event->meetings()->select('id', 'fokus_bahasan', 'tanggal', 'status')->get();

        $totalMeetings = $meetings->count();
        $meetingIds = $meetings->pluck('id')->all();

        $query = Attendance::whereIn('meeting_id', $meetingIds);
        if ($userIds !== null) {
            $query->whereIn('user_id', $userIds);
        }
        $attendancesByUser = $query->get()->groupBy('user_id');

        $results = [];
        $targetUserIds = $userIds !== null ? $userIds : $attendancesByUser->keys()->all();

        foreach ($targetUserIds as $uId) {
            if ($totalMeetings === 0) {
                $results[$uId] = [
                    'total_meetings' => 0,
                    'attended_count' => 0,
                    'izin_count' => 0,
                    'sakit_count' => 0,
                    'alpha_count' => 0,
                    'attendance_rate' => 0.0,
                    'status_label' => 'Belum Ada Rapat',
                    'status_class' => 'neutral',
                    'badge_color' => '#64748B',
                    'badge_bg' => 'rgba(100, 116, 139, 0.15)',
                    'icon' => 'fa-info-circle',
                    'summary_text' => 'Belum ada rapat kepanitiaan',
                ];

                continue;
            }

            $userAtt = $attendancesByUser->get($uId, collect());
            $attendedCount = $userAtt->where('status', 'hadir')->count();
            $izinCount = $userAtt->where('status', 'izin')->count();
            $sakitCount = $userAtt->where('status', 'sakit')->count();
            $alphaCount = $userAtt->where('status', 'alpha')->count();

            $rate = round(($attendedCount / $totalMeetings) * 100, 1);

            if ($rate >= 100.0) {
                $label = 'Sangat Aktif';
                $class = 'success';
                $color = '#10B981';
                $bg = 'rgba(16, 185, 129, 0.15)';
                $icon = 'fa-check-double';
            } elseif ($rate >= 75.0) {
                $label = 'Aktif';
                $class = 'info';
                $color = '#3B82F6';
                $bg = 'rgba(59, 130, 246, 0.15)';
                $icon = 'fa-check';
            } elseif ($rate >= 50.0) {
                $label = 'Cukup Aktif';
                $class = 'warning';
                $color = '#F59E0B';
                $bg = 'rgba(245, 158, 11, 0.15)';
                $icon = 'fa-exclamation-circle';
            } elseif ($rate > 0.0) {
                $label = 'Kurang Aktif';
                $class = 'orange';
                $color = '#F97316';
                $bg = 'rgba(249, 115, 22, 0.15)';
                $icon = 'fa-exclamation-triangle';
            } else {
                $label = 'Tidak Aktif';
                $class = 'danger';
                $color = '#EF4444';
                $bg = 'rgba(239, 68, 68, 0.15)';
                $icon = 'fa-times-circle';
            }

            $summaryText = "{$attendedCount}/{$totalMeetings} Rapat ({$rate}%) — {$label}";

            $results[$uId] = [
                'total_meetings' => $totalMeetings,
                'attended_count' => $attendedCount,
                'izin_count' => $izinCount,
                'sakit_count' => $sakitCount,
                'alpha_count' => $alphaCount,
                'attendance_rate' => $rate,
                'status_label' => $label,
                'status_class' => $class,
                'badge_color' => $color,
                'badge_bg' => $bg,
                'icon' => $icon,
                'summary_text' => $summaryText,
            ];
        }

        return $results;
    }

    /**
     * Menyelesaikan event, memberikan EXP bernilai besar dan lencana kepanitiaan ke semua panitia.
     */
    public function completeEvent(Event $event): int
    {
        if (! Schema::hasTable('member_badges')) {
            try {
                Artisan::call('migrate', ['--force' => true]);
            } catch (\Throwable $e) {
                // Continue gracefully
            }
        }

        return DB::transaction(function () use ($event) {
            $event->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            $eventMembers = $event->members()->with(['division', 'member'])->get();
            $awardedCount = 0;

            // Preload track record keaktifan untuk seluruh panitia event ini
            $trackRecords = $this->getEventMembersTrackRecords(
                $event,
                $eventMembers->pluck('user_id')->filter()->all()
            );

            foreach ($eventMembers as $em) {
                if (! $em->member) {
                    continue;
                }

                $user = $em->member;
                $jabatan = $em->jabatan_panitia ?: 'Anggota Panitia';
                $divisi = $em->division?->nama_divisi;
                $exp = $this->calculateEventMemberExp($jabatan);
                $trackRecord = $trackRecords[$user->id] ?? $this->calculateEventMemberTrackRecord($event, $user->id);

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
                        'attendance_summary' => $trackRecord,
                    ]);

                    $user->increment('exp', $exp);
                    cache()->forget("portal_stats_{$user->id}");
                    $awardedCount++;
                } else {
                    $existingBadge->update([
                        'attendance_summary' => $trackRecord,
                    ]);
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
        if (! Schema::hasTable('member_badges')) {
            return 0;
        }

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
