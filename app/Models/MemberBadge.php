<?php

namespace App\Models;

use App\Services\GamificationService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberBadge extends Model
{
    protected $table = 'member_badges';

    protected $fillable = [
        'user_id',
        'event_id',
        'event_name',
        'jabatan',
        'divisi',
        'badge_type',
        'exp_earned',
        'attendance_summary',
    ];

    protected function casts(): array
    {
        return [
            'exp_earned' => 'integer',
            'attendance_summary' => 'array',
        ];
    }

    public function getTrackRecordAttribute(): array
    {
        if (! empty($this->attendance_summary)) {
            return is_array($this->attendance_summary)
                ? $this->attendance_summary
                : (json_decode((string) $this->attendance_summary, true) ?: []);
        }

        if ($this->event) {
            return app(GamificationService::class)->calculateEventMemberTrackRecord($this->event, (int) $this->user_id);
        }

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

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'user_id');
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class, 'event_id');
    }

    public function getIconAttribute(): string
    {
        $role = strtolower(trim($this->jabatan ?? ''));

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
            return 'fa-trophy';
        }

        if (
            str_contains($role, 'koor') ||
            str_contains($role, 'sekretaris') ||
            str_contains($role, 'sekre') ||
            str_contains($role, 'bendahara') ||
            str_contains($role, 'bendum') ||
            str_contains($role, 'bph')
        ) {
            return 'fa-medal';
        }

        return 'fa-award';
    }

    public function getColorThemeAttribute(): array
    {
        $role = strtolower(trim($this->jabatan ?? ''));

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
            return [
                'name' => 'gold',
                'color' => '#F59E0B',
                'border' => '#FCD34D',
                'bg' => 'rgba(245, 158, 11, 0.12)',
                'badge_class' => 'badge-gold',
            ];
        }

        if (
            str_contains($role, 'koor') ||
            str_contains($role, 'sekretaris') ||
            str_contains($role, 'sekre') ||
            str_contains($role, 'bendahara') ||
            str_contains($role, 'bendum') ||
            str_contains($role, 'bph')
        ) {
            return [
                'name' => 'purple',
                'color' => '#8B5CF6',
                'border' => '#C4B5FD',
                'bg' => 'rgba(139, 92, 246, 0.12)',
                'badge_class' => 'badge-purple',
            ];
        }

        return [
            'name' => 'cyan',
            'color' => '#06B6D4',
            'border' => '#67E8F9',
            'bg' => 'rgba(6, 182, 212, 0.12)',
            'badge_class' => 'badge-info',
        ];
    }

    public function getBadgeColorAttribute(): string
    {
        return $this->color_theme['color'];
    }

    public function getBadgeBgAttribute(): string
    {
        return $this->color_theme['bg'];
    }

    public function getRoleTitleAttribute(): string
    {
        $role = strtolower(trim($this->jabatan ?? ''));

        if (
            str_contains($role, 'penanggung jawab') ||
            str_contains($role, 'penanggungjawab') ||
            $role === 'pj' ||
            str_contains($role, 'pj ')
        ) {
            return 'Penanggung Jawab';
        }

        if (
            str_contains($role, 'ketua') ||
            str_contains($role, 'project officer') ||
            str_contains($role, 'leader') ||
            str_contains($role, 'wakil') ||
            str_contains($role, 'vice')
        ) {
            return 'Pimpinan / Ketua';
        }

        if (
            str_contains($role, 'koor') ||
            str_contains($role, 'sekretaris') ||
            str_contains($role, 'sekre') ||
            str_contains($role, 'bendahara') ||
            str_contains($role, 'bendum') ||
            str_contains($role, 'bph')
        ) {
            return 'Koordinator & BPH';
        }

        return 'Panitia Pelaksana';
    }
}
