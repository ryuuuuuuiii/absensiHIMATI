<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends Model
{
    protected $table = 'events';

    protected $fillable = [
        'nama_event',
        'deskripsi',
        'status',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'completed_at' => 'datetime',
        ];
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function badges(): HasMany
    {
        return $this->hasMany(MemberBadge::class, 'event_id');
    }

    public function divisions(): HasMany
    {
        return $this->hasMany(EventDivision::class, 'event_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(EventMember::class, 'event_id');
    }

    public function meetings(): HasMany
    {
        return $this->hasMany(Meeting::class, 'event_id');
    }
}
