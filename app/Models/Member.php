<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Member extends Model
{
    protected $table = 'users';

    protected $fillable = [
        'nama_lengkap',
        'departemen',
        'jabatan',
        'email',
        'qr_code',
        'qr_image',
        'is_bph',
    ];

    protected function casts(): array
    {
        return [
            'is_bph' => 'boolean',
        ];
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class, 'user_id');
    }

    public function eventMembers(): HasMany
    {
        return $this->hasMany(EventMember::class, 'user_id');
    }

    public function meetingsCreated(): HasMany
    {
        return $this->hasMany(Meeting::class, 'created_by');
    }

    public function getInitialsAttribute(): string
    {
        $words = explode(' ', trim($this->nama_lengkap));
        $initials = '';
        foreach (array_slice($words, 0, 2) as $word) {
            $initials .= mb_strtoupper(mb_substr($word, 0, 1));
        }
        return $initials ?: 'U';
    }

    public function getQrDataAttribute(): string
    {
        return json_encode([
            'id'         => (int) $this->id,
            'qr_code'    => $this->qr_code,
            'nama'       => $this->nama_lengkap,
            'departemen' => $this->departemen,
            'jabatan'    => $this->jabatan,
        ], JSON_UNESCAPED_UNICODE);
    }
}
