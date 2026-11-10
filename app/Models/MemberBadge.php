<?php

namespace App\Models;

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
    ];

    protected function casts(): array
    {
        return [
            'exp_earned' => 'integer',
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
        $jabatan = strtolower($this->jabatan ?? '');
        if (str_contains($jabatan, 'ketua') || str_contains($jabatan, 'leader') || str_contains($jabatan, 'project officer')) {
            return 'fa-trophy';
        }
        if (str_contains($jabatan, 'koordinator') || str_contains($jabatan, 'sekretaris') || str_contains($jabatan, 'bendahara') || str_contains($jabatan, 'bph')) {
            return 'fa-medal';
        }

        return 'fa-award';
    }

    public function getColorThemeAttribute(): array
    {
        $jabatan = strtolower($this->jabatan ?? '');
        if (str_contains($jabatan, 'ketua') || str_contains($jabatan, 'leader') || str_contains($jabatan, 'project officer')) {
            return [
                'name' => 'gold',
                'color' => '#F59E0B',
                'border' => '#FCD34D',
                'bg' => 'rgba(245, 158, 11, 0.12)',
                'badge_class' => 'badge-gold',
            ];
        }
        if (str_contains($jabatan, 'koordinator') || str_contains($jabatan, 'sekretaris') || str_contains($jabatan, 'bendahara') || str_contains($jabatan, 'bph')) {
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
        $jabatan = strtolower($this->jabatan ?? '');
        if (str_contains($jabatan, 'ketua') || str_contains($jabatan, 'leader') || str_contains($jabatan, 'project officer')) {
            return 'Pimpinan / Ketua';
        }
        if (str_contains($jabatan, 'koordinator') || str_contains($jabatan, 'sekretaris') || str_contains($jabatan, 'bendahara') || str_contains($jabatan, 'bph')) {
            return 'Koordinator / BPH';
        }

        return 'Panitia Pelaksana';
    }
}
