<?php

namespace App\Models;

use App\Services\SpService;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

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
        'is_bph',
        'sp_level',
        'sp_base_level',
        'is_active',
        'sp_reason',
        'sp_detail',
        'sp_issued_at',
    ];

    protected $attributes = [
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
            'is_bph' => 'boolean',
            'is_active' => 'boolean',
            'sp_level' => 'integer',
            'sp_base_level' => 'integer',
            'sp_issued_at' => 'datetime',
            'password' => 'hashed',
            'last_login_at' => 'datetime',
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

    public function feedbacks(): HasMany
    {
        return $this->hasMany(Feedback::class, 'user_id');
    }

    public function announcementReads(): HasMany
    {
        return $this->hasMany(AnnouncementRead::class, 'user_id');
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
