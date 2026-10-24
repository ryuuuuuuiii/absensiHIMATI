<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EventDivision extends Model
{
    protected $table = 'event_divisions';

    public $timestamps = false;

    protected $fillable = [
        'event_id',
        'nama_divisi',
        'created_at',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class, 'event_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(EventMember::class, 'division_id');
    }
}
