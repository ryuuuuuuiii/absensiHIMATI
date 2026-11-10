<?php

namespace App\Services;

use App\Models\Meeting;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class MeetingSpamProtectionService
{
    /**
     * Waktu jeda minimum (dalam detik) antar pembuatan rapat oleh pembuat yang sama.
     */
    public const COOLDOWN_SECONDS = 60;

    /**
     * Batas maksimal pembuatan rapat dalam periode rate limiter.
     */
    public const MAX_ATTEMPTS = 3;

    /**
     * Durasi jendela waktu rate limiter (dalam detik, 10 menit).
     */
    public const DECAY_SECONDS = 600;

    /**
     * Batas maksimal rapat aktif (status scanning) yang belum ditutup pada hari yang sama.
     */
    public const MAX_ACTIVE_MEETINGS = 2;

    /**
     * Durasi minimal rapat (detik) agar dianggap rapat sah yang dapat memicu akumulasi SP jika tidak ada peserta lain.
     */
    public const MIN_VALID_MEETING_DURATION_SECONDS = 120;

    /**
     * Periksa apakah pembuatan rapat terindikasi spam atau melebihi batas.
     * Mengembalikan pesan error (string) jika terindikasi spam / dibatasi, atau null jika diizinkan.
     */
    public function checkCreationSpam(Request $request, int $createdBy): ?string
    {
        $actorId = auth('member')->id() ?? auth('web')->id() ?? $createdBy;
        $actorType = auth('member')->check() ? 'member' : 'admin';
        $ip = $request->ip() ?? '127.0.0.1';

        // 1. Jeda Minimum (Cooldown 60 detik) antar pembuatan rapat oleh user yang sama
        $latestMeeting = Meeting::where('created_by', $createdBy)
            ->latest('created_at')
            ->first();

        if ($latestMeeting && $latestMeeting->created_at) {
            $createdCarbon = Carbon::parse($latestMeeting->created_at);
            if ($createdCarbon->isAfter(now()->subSeconds(self::COOLDOWN_SECONDS))) {
                $secondsPassed = abs((int) $createdCarbon->diffInSeconds(now()));
                $remaining = max(1, self::COOLDOWN_SECONDS - $secondsPassed);

                return "Sistem mendeteksi pembuatan sesi absensi terlalu cepat. Mohon tunggu {$remaining} detik sebelum membuat sesi absensi baru (Cooldown Anti-Spam).";
            }
        }

        // 2. Rate Limiting: Maksimal 3 rapat dalam 10 menit per user & IP
        $limiterKey = "create-meeting:{$actorType}:{$actorId}";
        if (RateLimiter::tooManyAttempts($limiterKey, self::MAX_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($limiterKey);
            $minutes = max(1, (int) ceil($seconds / 60));

            return 'Terdeteksi pembuatan absensi terlalu sering (Spam). Pembuatan sesi dibatasi maksimal '.self::MAX_ATTEMPTS." kali dalam 10 menit demi melindungi sistem Surat Peringatan (SP) anggota. Silakan tunggu {$minutes} menit lagi.";
        }

        // 3. Batasi Sesi Absensi Aktif: Maksimal 2 sesi scanning yang belum ditutup pada tanggal yang sama
        $requestDate = $request->input('tanggal') ?? now()->toDateString();
        $activeScanningCount = Meeting::where('created_by', $createdBy)
            ->where('tanggal', $requestDate)
            ->where('status', 'scanning')
            ->count();

        if ($activeScanningCount >= self::MAX_ACTIVE_MEETINGS) {
            return "Anda masih memiliki {$activeScanningCount} sesi absensi yang sedang berlangsung (aktif) hari ini. Harap selesaikan atau batalkan sesi absensi sebelumnya terlebih dahulu sebelum membuat sesi baru.";
        }

        // 4. Deteksi Sesi Duplikat (agenda/topik yang sama dalam 30 menit terakhir)
        $isDuplicate = $this->isDuplicateMeeting($request, $createdBy);
        if ($isDuplicate) {
            return 'Terdeteksi pembuatan absensi duplikat. Sesi absensi untuk agenda atau departemen ini baru saja dibuat dalam 30 menit terakhir. Silakan gunakan sesi yang sudah ada agar tidak memicu Alpha ganda pada pengurus.';
        }

        return null;
    }

    /**
     * Catat hit pembuatan rapat pada Rate Limiter setelah rapat berhasil dibuat.
     */
    public function recordCreation(Request $request, int $createdBy): void
    {
        $actorId = auth('member')->id() ?? auth('web')->id() ?? $createdBy;
        $actorType = auth('member')->check() ? 'member' : 'admin';
        $limiterKey = "create-meeting:{$actorType}:{$actorId}";

        RateLimiter::hit($limiterKey, self::DECAY_SECONDS);
    }

    /**
     * Cek apakah ada rapat duplikat yang baru dibuat dalam 30 menit terakhir.
     */
    protected function isDuplicateMeeting(Request $request, int $createdBy): bool
    {
        $requestDate = $request->input('tanggal');
        $jenisRapat = $request->input('jenis_rapat');
        $departemen = $request->input('departemen');
        $eventId = $request->input('event_id');

        $query = Meeting::where('created_by', $createdBy)
            ->where('tanggal', $requestDate)
            ->where('jenis_rapat', $jenisRapat)
            ->where('status', '!=', 'cancelled')
            ->where('created_at', '>=', now()->subMinutes(30));

        if ($jenisRapat === 'internal_dept' && $departemen) {
            $query->where('departemen', $departemen);
        }

        if ($jenisRapat === 'kepanitiaan' && $eventId) {
            $query->where('event_id', $eventId);
        }

        $existingMeetings = $query->get();

        if ($existingMeetings->isEmpty()) {
            return false;
        }

        // Cocokkan fokus bahasan / nama event
        $fokusIncoming = $this->extractFokusBahasan($request);
        if (! $fokusIncoming) {
            return true;
        }

        foreach ($existingMeetings as $existing) {
            $existingFokus = trim((string) ($existing->fokus_bahasan ?: $existing->nama_event));
            if (strcasecmp($fokusIncoming, $existingFokus) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Ambil fokus bahasan/agenda dari input request sesuai jenis rapat.
     */
    public function extractFokusBahasan(Request $request): ?string
    {
        $jenis = $request->input('jenis_rapat');

        $fokus = match ($jenis) {
            'internal_dept' => $request->input('fokus_bahasan_internal'),
            'perdana' => $request->input('nama_event_perdana'),
            'kepanitiaan' => $request->input('fokus_bahasan_kepanitiaan'),
            'bph' => $request->input('fokus_bahasan_bph'),
            'lintas_dept' => $request->input('fokus_bahasan_lintas'),
            default => null,
        };

        return filled($fokus) ? trim((string) $fokus) : null;
    }

    /**
     * Cache in-memory validitas rapat agar tidak melakukan query count berulang-ulang
     * ketika mengevaluasi puluhan anggota sekaligus pada rapat yang sama.
     */
    protected array $meetingValidityCache = [];

    public function clearMeetingValidityCache(): void
    {
        $this->meetingValidityCache = [];
    }

    /**
     * Periksa apakah sebuah rapat yang telah selesai memenuhi syarat validitas untuk evaluasi SP otomatis.
     * Rapat yang diselesaikan secara instan (< 2 menit) tanpa ada peserta lain yang hadir dianggap sebagai
     * sesi uji coba / spam sehingga TIDAK memicu Alpha beruntun pada sistem SP anggota.
     */
    public function isMeetingValidForSp(Meeting $meeting): bool
    {
        if (isset($this->meetingValidityCache[$meeting->id])) {
            return $this->meetingValidityCache[$meeting->id];
        }

        if ($meeting->status !== 'completed') {
            return $this->meetingValidityCache[$meeting->id] = false;
        }

        // Rapat terjadwal historis (misal waktu_mulai dan waktu_selesai berselisih wajar)
        if ($meeting->waktu_mulai && $meeting->waktu_selesai) {
            $scheduledDiff = strtotime((string) $meeting->waktu_selesai) - strtotime((string) $meeting->waktu_mulai);
            if ($scheduledDiff >= 300) {
                return $this->meetingValidityCache[$meeting->id] = true;
            }
        }

        // Cek durasi aktual sejak rapat dibuat sampai diselesaikan
        $createdAt = $meeting->created_at ?? now();
        $diffSeconds = abs(now()->diffInSeconds($createdAt));

        // Cek berapa banyak peserta lain (selain pembuat rapat) yang hadir
        $otherAttendeesCount = $meeting->attendances()
            ->where('status', 'hadir')
            ->where('user_id', '!=', $meeting->created_by)
            ->count();

        // Jika rapat berjalan setidaknya 2 menit ATAU ada peserta lain yang scan hadir, rapat ini valid
        $isValid = ($diffSeconds >= self::MIN_VALID_MEETING_DURATION_SECONDS || $otherAttendeesCount > 0);

        return $this->meetingValidityCache[$meeting->id] = $isValid;
    }
}
