<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Feedback extends Model
{
    protected $table = 'feedback';

    protected $fillable = [
        'user_id',
        'kategori',
        'subjek',
        'pesan',
        'screenshot',
        'status',
        'admin_notes',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'user_id');
    }

    public function getKategoriLabelAttribute(): string
    {
        return match ($this->kategori) {
            'bug' => 'Kendala / Bug',
            'saran' => 'Usulan Fitur / Saran',
            'pertanyaan' => 'Pertanyaan / Bantuan',
            default => 'Lainnya',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'read' => 'Dibaca',
            'resolved' => 'Selesai',
            default => 'Menunggu',
        };
    }

    public function getScreenshotUrlAttribute(): ?string
    {
        if ($this->screenshot) {
            return asset('uploads/feedbacks/'.$this->screenshot);
        }

        return null;
    }
}
