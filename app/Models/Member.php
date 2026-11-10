<?php

namespace App\Models;

use App\Services\FileUploadService;
use App\Services\SpService;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\File;

class Member extends Authenticatable
{
    use Notifiable;

    protected $table = 'users';

    protected $fillable = [
        'nama_lengkap',
        'departemen',
        'jabatan',
        'nim',
        'nrp',
        'angkatan',
        'ttl',
        'email',
        'password',
        'qr_code',
        'qr_image',
        'profile_photo',
        'last_login_at',
        'exp',
        'is_bph',
        'sp_level',
        'sp_base_level',
        'is_active',
        'sp_reason',
        'sp_detail',
        'sp_issued_at',
    ];

    protected $attributes = [
        'exp' => 0,
        'sp_level' => 0,
        'sp_base_level' => 0,
        'is_active' => true,
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'exp' => 'integer',
            'is_bph' => 'boolean',
            'is_active' => 'boolean',
            'sp_level' => 'integer',
            'sp_base_level' => 'integer',
            'sp_issued_at' => 'datetime',
            'password' => 'hashed',
            'last_login_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (Member $member) {
            if ($member->profile_photo) {
                app(FileUploadService::class)->delete('profiles', $member->profile_photo);
            }
            if ($member->qr_image) {
                $filepath = public_path('assets/img/qrcodes/'.$member->qr_image);
                if (File::exists($filepath)) {
                    @File::delete($filepath);
                }
            }
        });
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

    public function feedbacks(): HasMany
    {
        return $this->hasMany(Feedback::class, 'user_id');
    }

    public function announcementReads(): HasMany
    {
        return $this->hasMany(AnnouncementRead::class, 'user_id');
    }

    public function badges(): HasMany
    {
        return $this->hasMany(MemberBadge::class, 'user_id')->latest();
    }

    /**
     * Data level, exp, progress bar, dan title pengurus
     */
    public function getLevelDataAttribute(): array
    {
        $exp = (int) ($this->exp ?? 0);
        $level = 1;
        $currentMin = 0;
        $step = 100;

        while (true) {
            $nextMin = $currentMin + $step;
            if ($exp < $nextMin) {
                break;
            }
            $currentMin = $nextMin;
            $level++;
            $step += 50; // Tiap level berikutnya membutuhkan tambahan 50 EXP lebih banyak
        }

        $needed = $nextMin - $currentMin;
        $progress = $exp - $currentMin;
        $percentage = $needed > 0 ? min(100, max(0, round(($progress / $needed) * 100, 1))) : 100;

        // Rank tiering Minecraft per 5 level:
        // Kayu (1-5) -> Batu (6-10) -> Besi (11-15) -> Gold (16-20) -> Amethyst (21-25) -> Diamond (26-30) -> Emerald (31-35) -> Netherite (36+)
        $minecraftTiers = [
            0 => ['name' => 'Kayu', 'color' => '#B45309', 'badge_bg' => 'rgba(180, 83, 9, 0.15)', 'icon' => 'cube', 'eng' => 'Wood'],
            1 => ['name' => 'Batu', 'color' => '#78716C', 'badge_bg' => 'rgba(120, 113, 108, 0.15)', 'icon' => 'cubes', 'eng' => 'Stone'],
            2 => ['name' => 'Besi', 'color' => '#94A3B8', 'badge_bg' => 'rgba(148, 163, 184, 0.15)', 'icon' => 'shield-alt', 'eng' => 'Iron'],
            3 => ['name' => 'Gold', 'color' => '#F59E0B', 'badge_bg' => 'rgba(245, 158, 11, 0.15)', 'icon' => 'coins', 'eng' => 'Gold'],
            4 => ['name' => 'Amethyst', 'color' => '#A855F7', 'badge_bg' => 'rgba(168, 85, 247, 0.15)', 'icon' => 'gem', 'eng' => 'Amethyst'],
            5 => ['name' => 'Diamond', 'color' => '#00F0FF', 'badge_bg' => 'rgba(0, 240, 255, 0.15)', 'icon' => 'gem', 'eng' => 'Diamond'],
            6 => ['name' => 'Emerald', 'color' => '#10B981', 'badge_bg' => 'rgba(16, 185, 129, 0.15)', 'icon' => 'gem', 'eng' => 'Emerald'],
            7 => ['name' => 'Netherite', 'color' => '#C084FC', 'badge_bg' => 'rgba(192, 132, 252, 0.2)', 'icon' => 'dragon', 'eng' => 'Netherite'],
        ];

        $tierIndex = min((int) floor(($level - 1) / 5), 7);
        $subRankNum = (($level - 1) % 5) + 1;
        $romans = [1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V'];
        $subRankRoman = ($level > 40 && $tierIndex === 7) ? 'V+' : ($romans[$subRankNum] ?? 'V');

        $tier = $minecraftTiers[$tierIndex];
        $title = "Tier {$tier['name']} {$subRankRoman}";

        return [
            'level' => $level,
            'title' => $title,
            'tier_name' => $tier['name'],
            'sub_rank' => $subRankRoman,
            'tier_item' => $tier['eng'],
            'icon' => $tier['icon'],
            'badge_color' => $tier['color'],
            'badge_bg' => $tier['badge_bg'],
            'exp' => $exp,
            'current_min' => $currentMin,
            'next_min' => $nextMin,
            'progress' => $progress,
            'needed' => $needed,
            'percentage' => $percentage,
        ];
    }

    public function getLevelAttribute(): int
    {
        return $this->level_data['level'];
    }

    public function getLevelTitleAttribute(): string
    {
        return $this->level_data['title'];
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
            'id' => (int) $this->id,
            'qr_code' => $this->qr_code,
            'nama' => $this->nama_lengkap,
            'departemen' => $this->departemen,
            'jabatan' => $this->jabatan,
        ], JSON_UNESCAPED_UNICODE);
    }

    public function getProfilePhotoUrlAttribute(): ?string
    {
        if (! $this->profile_photo) {
            return null;
        }

        return asset('uploads/profiles/'.$this->profile_photo);
    }

    public function hasAccount(): bool
    {
        return filled($this->password) && ! $this->isDismissed();
    }

    public function isDismissed(): bool
    {
        return (int) $this->sp_level >= 3 || ! $this->is_active;
    }

    public function getSpLabelAttribute(): ?string
    {
        return match ((int) $this->sp_level) {
            1 => 'SP 1',
            2 => 'SP 2',
            3 => 'SP 3 (Diberhentikan)',
            default => null,
        };
    }

    public function getSpShortLabelAttribute(): ?string
    {
        return match ((int) $this->sp_level) {
            1 => 'SP 1',
            2 => 'SP 2',
            3 => 'SP 3',
            default => null,
        };
    }

    public function getSpFullLabelAttribute(): string
    {
        return match ((int) $this->sp_level) {
            1 => 'SP 1 — Surat Peringatan Pertama',
            2 => 'SP 2 — Surat Peringatan Kedua',
            3 => 'SP 3 — Pemberhentian & Akun Nonaktif',
            default => 'Aman (Tanpa SP)',
        };
    }

    public function getSpBadgeClassAttribute(): string
    {
        return match ((int) $this->sp_level) {
            1 => 'badge-sp-1',
            2 => 'badge-sp-2',
            3 => 'badge-sp-3',
            default => 'badge-success',
        };
    }

    public function getConsecutiveAlphaCountAttribute(): int
    {
        return app(SpService::class)->getCurrentAlphaStreak($this);
    }

    public function isSpFromAlpha(): bool
    {
        if ((int) $this->sp_level <= 0) {
            return false;
        }

        if ((int) $this->sp_level > (int) $this->sp_base_level) {
            return true;
        }

        $reason = (string) $this->sp_reason;

        return str_starts_with($reason, 'Otomatis:');
    }

    public function isSpFromAdmin(): bool
    {
        return (int) $this->sp_level > 0 && ! $this->isSpFromAlpha();
    }

    public function getSpSourceLabelAttribute(): ?string
    {
        if ((int) $this->sp_level <= 0) {
            return null;
        }

        return $this->isSpFromAlpha()
            ? 'Tidak Hadir (Alpha) 3x Berturut-turut'
            : 'Diberikan oleh Admin (Pelanggaran)';
    }

    public function getSpReasonDisplayAttribute(): string
    {
        if (filled($this->sp_reason)) {
            return (string) $this->sp_reason;
        }

        if ($this->isSpFromAlpha()) {
            return 'Tidak hadir (Alpha) dalam kegiatan selama 3 kali berturut-turut.';
        }

        return 'Melakukan pelanggaran disiplin atau aturan kepengurusan organisasi.';
    }

    public function getSpDetailDisplayAttribute(): string
    {
        if (filled($this->sp_detail)) {
            return (string) $this->sp_detail;
        }

        if ($this->isSpFromAlpha()) {
            return 'Pengurus tercatat tidak hadir tanpa keterangan (Alpha) pada 3 kegiatan/rapat yang telah selesai secara berturut-turut.';
        }

        return 'Pelanggaran terhadap peraturan, kedisiplinan, atau tanggung jawab kepengurusan HIMA TI yang ditetapkan oleh Admin.';
    }

    public function getSpWarningTextAttribute(): ?string
    {
        $level = (int) $this->sp_level;
        if ($level <= 0) {
            return null;
        }

        if ($this->isSpFromAlpha()) {
            $streak = $this->consecutive_alpha_count;

            return match ($level) {
                1 => "Perhatian: Surat peringatan ini diterbitkan karena Anda tidak hadir (Alpha) 3 kali berturut-turut. Apabila kembali Alpha 3 kali berturut-turut, status akan otomatis naik menjadi SP 2 (Alpha berturut saat ini: {$streak}/3).",
                2 => "Peringatan Keras: Anda telah melakukan pelanggaran ketidakhadiran berulang (Alpha 3 kali berturut-turut pada SP 1). Apabila kembali Alpha 3 kali berturut-turut, status akan naik menjadi SP 3 (Pemberhentian & Akun Dinonaktifkan) (Alpha berturut saat ini: {$streak}/3).",
                default => 'Akun Anda telah diberhentikan dan dinonaktifkan dari kepengurusan karena tidak hadir (Alpha) 3 kali berturut-turut secara berulang hingga mencapai SP 3.',
            };
        }

        return match ($level) {
            1 => 'Perhatian: Surat peringatan ini diberikan oleh Admin karena Anda melakukan pelanggaran. Harap mematuhi peraturan organisasi; apabila kembali melakukan pelanggaran maka status akan dinaikkan menjadi SP 2.',
            2 => 'Peringatan Keras: Surat peringatan kedua ini diberikan oleh Admin atas pelanggaran berulang. Harap segera melakukan evaluasi diri; apabila kembali melakukan pelanggaran maka akan dikenakan SP 3 (Pemberhentian & Akun Dinonaktifkan).',
            default => 'Akun Anda telah diberhentikan dan dinonaktifkan dari kepengurusan oleh Admin karena pelanggaran berat atau pelanggaran berulang hingga mencapai SP 3.',
        };
    }

    public function getSpWarningHtmlAttribute(): ?string
    {
        $level = (int) $this->sp_level;
        if ($level <= 0) {
            return null;
        }

        if ($this->isSpFromAlpha()) {
            $streak = $this->consecutive_alpha_count;

            return match ($level) {
                1 => "Perhatian: Surat peringatan ini diterbitkan karena Anda tidak hadir (Alpha) 3 kali berturut-turut. Apabila kembali Alpha 3 kali berturut-turut, status akan otomatis naik menjadi <strong>SP 2</strong> (Alpha berturut saat ini: <strong>{$streak}/3</strong>).",
                2 => "Peringatan Keras: Anda telah melakukan pelanggaran ketidakhadiran berulang (Alpha 3 kali berturut-turut pada SP 1). Apabila kembali Alpha 3 kali berturut-turut, status akan naik menjadi <strong>SP 3 (Pemberhentian &amp; Akun Dinonaktifkan)</strong> (Alpha berturut saat ini: <strong>{$streak}/3</strong>).",
                default => 'Akun Anda telah diberhentikan dan dinonaktifkan dari kepengurusan karena tidak hadir (Alpha) 3 kali berturut-turut secara berulang hingga mencapai <strong>SP 3</strong>.',
            };
        }

        return match ($level) {
            1 => 'Perhatian: Surat peringatan ini diberikan oleh Admin karena Anda melakukan pelanggaran. Harap mematuhi peraturan organisasi; apabila kembali melakukan pelanggaran maka status akan dinaikkan menjadi <strong>SP 2</strong>.',
            2 => 'Peringatan Keras: Surat peringatan kedua ini diberikan oleh Admin atas pelanggaran berulang. Harap segera melakukan evaluasi diri; apabila kembali melakukan pelanggaran maka akan dikenakan <strong>SP 3 (Pemberhentian &amp; Akun Dinonaktifkan)</strong>.',
            default => 'Akun Anda telah diberhentikan dan dinonaktifkan dari kepengurusan oleh Admin karena pelanggaran berat atau pelanggaran berulang hingga mencapai <strong>SP 3</strong>.',
        };
    }

    public function getSpAttendanceNoticeAttribute(): string
    {
        if ((int) $this->sp_level <= 0) {
            return '';
        }

        if ($this->isSpFromAlpha()) {
            return " (Status Akun: {$this->sp_label} — Alpha 3x berturut-turut. {$this->sp_warning_text})";
        }

        return " (Status Akun: {$this->sp_label} — Pelanggaran: {$this->sp_reason_display}. {$this->sp_warning_text})";
    }

    public function isBphInti(): bool
    {
        return strcasecmp((string) $this->departemen, 'Inti') === 0
            || in_array($this->jabatan, [
                'Ketua Umum',
                'Wakil Ketua Umum',
                'Sekretaris 1',
                'Sekretaris 2',
                'Bendahara 1',
                'Bendahara 2',
                'Sekretaris Umum I',
                'Sekretaris Umum II',
                'Bendahara Umum I',
                'Bendahara Umum II',
            ]);
    }

    public function getDepartemenLabelAttribute(): string
    {
        if ($this->isBphInti()) {
            return 'Pengurus Inti';
        }

        return 'Dept. '.$this->departemen;
    }
}
