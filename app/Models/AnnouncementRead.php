<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnnouncementRead extends Model
{
    protected $table = 'announcement_reads';

    protected $fillable = [
        'announcement_id',
        'user_id',
        'is_dismissed',
        'read_at',
        'dismissed_at',
    ];

    protected function casts(): array
    {
        return [
            'is_dismissed' => 'boolean',
            'read_at' => 'datetime',
            'dismissed_at' => 'datetime',
        ];
    }

    public function announcement(): BelongsTo
    {
        return $this->belongsTo(Announcement::class, 'announcement_id');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'user_id');
    }
}
