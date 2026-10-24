<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Meeting extends Model
{
    protected $table = 'meetings';

    protected $fillable = [
        'created_by',
        'jenis_rapat',
        'mode_rapat',
        'event_id',
        'departemen',
        'nama_event',
        'divisi_rapat',
        'tipe_divisi',
        'fokus_bahasan',
        'departemen_collab',
        'tanggal',
        'waktu_mulai',
        'waktu_selesai',
        'status',
        'unique_link',
        'absent_link',
        'foto_dokumentasi',
    ];

    protected function casts(): array
    {
        return [
            'departemen_collab' => 'array',
            'tanggal' => 'date:Y-m-d',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'created_by');
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class, 'event_id');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class, 'meeting_id');
    }

    public function getJenisLabelAttribute(): string
    {
        $types = config('himati.meeting_types', []);
        return $types[$this->jenis_rapat] ?? $this->jenis_rapat;
    }

    public function getHadirCountAttribute(): int
    {
        return $this->attendances()->where('status', 'hadir')->count();
    }

    public function getTotalPesertaAttribute(): int
    {
        return $this->attendances()->count();
    }
}
