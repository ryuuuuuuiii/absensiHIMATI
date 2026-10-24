<?php

namespace App\Models;

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
}
