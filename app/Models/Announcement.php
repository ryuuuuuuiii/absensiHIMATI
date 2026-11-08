<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Announcement extends Model
{
    protected $table = 'announcements';

    protected $fillable = [
        'admin_id',
        'judul',
        'konten',
        'tipe',
        'target_type',
        'target_departemen',
        'target_user_id',
        'is_banner',
        'is_active',
        'pinned',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'is_banner' => 'boolean',
            'is_active' => 'boolean',
            'pinned' => 'boolean',
            'expires_at' => 'datetime',
        ];
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'admin_id');
    }

    public function targetUser(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'target_user_id');
    }

    public function reads(): HasMany
    {
        return $this->hasMany(AnnouncementRead::class, 'announcement_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where(function (Builder $q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    public function scopeForMember(Builder $query, Member $member): Builder
    {
        return $query->where(function (Builder $q) use ($member) {
            $q->where('target_type', 'all')
                ->orWhere(function (Builder $sq) use ($member) {
                    $sq->where('target_type', 'departemen');
                    if ($member->is_bph) {
                        $sq->where(function (Builder $bphQ) use ($member) {
                            $bphQ->where('target_departemen', $member->departemen)
                                ->orWhere('target_departemen', 'BPH');
                        });
                    } else {
                        $sq->where('target_departemen', $member->departemen);
                    }
                })
                ->orWhere(function (Builder $sq) use ($member) {
                    $sq->where('target_type', 'personal')
                        ->where('target_user_id', $member->id);
                });
        });
    }

    public function isReadBy(Member $member): bool
    {
        return $this->reads()
            ->where('user_id', $member->id)
            ->whereNotNull('read_at')
            ->exists();
    }

    public function isDismissedBy(Member $member): bool
    {
        return $this->reads()
            ->where('user_id', $member->id)
            ->where('is_dismissed', true)
            ->exists();
    }

    public function getTargetLabelAttribute(): string
    {
        if ($this->target_type === 'all') {
            return 'Semua Pengurus';
        }

        if ($this->target_type === 'departemen') {
            return 'Departemen '.$this->target_departemen;
        }

        if ($this->target_type === 'personal') {
            return 'Personal: '.($this->targetUser?->nama_lengkap ?? 'Anggota #'.$this->target_user_id);
        }

        return 'Umum';
    }

    public function getBadgeClassAttribute(): string
    {
        return match ($this->tipe) {
            'mendesak' => 'badge-danger',
            'penting' => 'badge-warning',
            default => 'badge-primary',
        };
    }

    public function getTipeLabelAttribute(): string
    {
        return match ($this->tipe) {
            'mendesak' => 'Mendesak',
            'penting' => 'Penting',
            default => 'Informasi',
        };
    }

    public function getIconAttribute(): string
    {
        return match ($this->tipe) {
            'mendesak' => 'fa-exclamation-triangle',
            'penting' => 'fa-bell',
            default => 'fa-bullhorn',
        };
    }
}
