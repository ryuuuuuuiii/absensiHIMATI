<?php

namespace App\Models;

use App\Services\SpService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    protected $table = 'attendances';

    public $timestamps = false;

    protected $fillable = [
        'meeting_id',
        'user_id',
        'status',
        'keterangan',
        'bukti_foto',
        'scanned_at',
        'scan_method',
        'sp_ignored',
        'exp_awarded',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'scanned_at' => 'datetime',
            'sp_ignored' => 'boolean',
            'exp_awarded' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saved(function (Attendance $attendance) {
            $meeting = $attendance->relationLoaded('meeting')
                ? $attendance->meeting
                : Meeting::find($attendance->meeting_id);

            if ($meeting && $meeting->status === 'completed') {
                $member = $attendance->relationLoaded('member')
                    ? $attendance->member
                    : Member::find($attendance->user_id);

                if ($member) {
                    app(SpService::class)->evaluateMember($member);
                }
            }
        });
    }

    public function meeting(): BelongsTo
    {
        return $this->belongsTo(Meeting::class, 'meeting_id');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'user_id');
    }
}
