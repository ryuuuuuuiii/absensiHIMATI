<?php

namespace App\Models;

use App\Services\SpService;
use Carbon\Carbon;
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

    protected static function booted(): void
    {
        static::saved(function (Meeting $meeting) {
            if ($meeting->status === 'completed') {
                app(SpService::class)->evaluateMeeting($meeting);
            }
        });
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

    /**
     * Memeriksa apakah anggota berhak hadir pada rapat ini.
     * Mengembalikan pesan error (string) jika ditolak, atau null jika diizinkan.
     */
    public function checkMemberEligibility(Member $member): ?string
    {
        if ($member->isDismissed()) {
            return "Presensi ditolak. Akun {$member->nama_lengkap} telah dinonaktifkan karena status SP 3 (Pemberhentian Pengurus).";
        }

        $isInti = $member->isBphInti();

        switch ($this->jenis_rapat) {
            case 'internal_dept':
                $deptLower = strtolower((string) $this->departemen);
                if ($deptLower === 'inti') {
                    if (! $isInti) {
                        return 'Presensi ditolak. Rapat internal ini khusus untuk Pengurus Inti.';
                    }
                } elseif ($deptLower === 'bph') {
                    if (! $isInti && ! $member->is_bph) {
                        return 'Presensi ditolak. Rapat internal ini khusus untuk BPH (Pengurus Inti dan Kepala Departemen).';
                    }
                } else {
                    $isSameDept = strcasecmp((string) $member->departemen, (string) $this->departemen) === 0;
                    if (! $isSameDept && ! $isInti) {
                        return "Presensi ditolak. Rapat internal ini khusus untuk Departemen {$this->departemen} dan Pengurus Inti.";
                    }
                }
                break;

            case 'bph':
                if (! $isInti && ! $member->is_bph) {
                    return 'Presensi ditolak. Rapat ini khusus untuk pengurus BPH.';
                }
                break;

            case 'lintas_dept':
                $collabDepts = (array) ($this->departemen_collab ?? []);
                $isInCollab = in_array($member->departemen, $collabDepts);
                if (! $isInCollab && ! $isInti) {
                    return 'Presensi ditolak. Anda bukan anggota dari departemen yang berkolaborasi dalam rapat ini.';
                }
                break;

            case 'kepanitiaan':
                $isEventMember = EventMember::where('event_id', $this->event_id)
                    ->where('user_id', $member->id)
                    ->exists();

                if (! $isEventMember && ! $isInti) {
                    return 'Presensi ditolak. Anda tidak terdaftar sebagai panitia pada event ini.';
                }

                if ($this->tipe_divisi === 'perdivisi' && ! empty($this->divisi_rapat) && ! $isInti) {
                    $isInDivisi = EventMember::where('event_id', $this->event_id)
                        ->where('user_id', $member->id)
                        ->whereHas('division', function ($q) {
                            $q->where('nama_divisi', $this->divisi_rapat);
                        })
                        ->exists();

                    if (! $isInDivisi) {
                        return "Presensi ditolak. Rapat ini khusus untuk Divisi {$this->divisi_rapat} dan Pengurus Inti.";
                    }
                }
                break;

            case 'perdana':
                // Seluruh anggota HIMA TI berhak hadir
                break;
        }

        return null;
    }

    /**
     * Mendapatkan waktu batas akhir absensi online (Carbon instance).
     * Jika pembuat rapat menentukan waktu_selesai, gunakan waktu_selesai tersebut.
     * Jika tidak ditentukan, gunakan standar admin setting (online_link_expiry_hours).
     */
    public function getOnlineExpiryTime(): Carbon
    {
        $start = $this->tanggal->copy()->setTimeFromTimeString($this->waktu_mulai);

        if ($this->waktu_selesai) {
            $end = $this->tanggal->copy()->setTimeFromTimeString($this->waktu_selesai);
            if ($end->lessThanOrEqualTo($start)) {
                $end->addDay();
            }

            return $end;
        }

        $expiryHours = (int) Setting::get('online_link_expiry_hours', '3');

        return $start->copy()->addHours($expiryHours);
    }

    /**
     * Memeriksa apakah link absensi online sudah kedaluwarsa.
     */
    public function isOnlineExpired(): bool
    {
        if (in_array($this->status, ['completed', 'cancelled'], true)) {
            return true;
        }

        if ($this->waktu_selesai) {
            return now()->greaterThan($this->getOnlineExpiryTime());
        }

        $expiryHours = (int) Setting::get('online_link_expiry_hours', '3');
        $start = $this->tanggal->copy()->setTimeFromTimeString($this->waktu_mulai);

        return $this->status !== 'scanning' && now()->greaterThan($start->addHours($expiryHours));
    }
}
