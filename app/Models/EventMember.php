<?php

namespace App\Models;

use App\Services\GamificationService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventMember extends Model
{
    protected $table = 'event_members';

    public $timestamps = false;

    protected $fillable = [
        'event_id',
        'division_id',
        'user_id',
        'jabatan_panitia',
        'created_at',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class, 'event_id');
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(EventDivision::class, 'division_id');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'user_id');
    }

    public function getTrackRecordAttribute(): array
    {
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
}
