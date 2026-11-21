<?php

namespace App\Services;

use App\Models\Announcement;
use App\Models\Meeting;
use App\Models\Member;
use App\Models\PushSubscription;
use App\Models\Setting;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;

class WebPushService
{
    private const P256_SPKI_DER_PREFIX_HEX = '3059301306072a8648ce3d020106082a8648ce3d030107034200';

    private const P256_SEC1_DER_PREFIX_HEX = '30770201010420';

    private const P256_SEC1_DER_MIDDLE_HEX = 'a00a06082a8648ce3d030107a144034200';

    /**
     * Pastikan tabel push_subscriptions sudah tersedia (self-heal untuk cloud/Vercel).
     */
    public function ensureTableReady(): bool
    {
        try {
            if (Schema::hasTable('push_subscriptions')) {
                return true;
            }

            Artisan::call('migrate', ['--force' => true]);

            return Schema::hasTable('push_subscriptions');
        } catch (\Throwable $e) {
            Log::warning('WebPush table check failed: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Ambil pasangan kunci VAPID (prioritas: config/.env, lalu tabel settings, atau generate otomatis).
     *
     * @return array{public_key: string, private_key: string, subject: string}
     */
    public function getVapidKeys(): array
    {
        $publicKey = trim((string) config('himati.webpush.public_key', ''));
        $privateKey = trim((string) config('himati.webpush.private_key', ''));
        $subject = trim((string) config('himati.webpush.subject', ''));

        if ($subject === '') {
            $appUrl = trim((string) config('app.url', 'https://absensi-himati.vercel.app'));
            $subject = str_starts_with($appUrl, 'https://') ? $appUrl : 'mailto:admin@himati.id';
        }

        if ($publicKey !== '' && $privateKey !== '') {
            return [
                'public_key' => $publicKey,
                'private_key' => $privateKey,
                'subject' => $subject,
            ];
        }

        $storedPublic = trim((string) Setting::get('vapid_public_key', ''));
        $storedPrivate = trim((string) Setting::get('vapid_private_key', ''));

        if ($storedPublic !== '' && $storedPrivate !== '') {
            return [
                'public_key' => $storedPublic,
                'private_key' => $storedPrivate,
                'subject' => $subject,
            ];
        }

        $generated = $this->generateVapidKeys();

        Setting::set('vapid_public_key', $generated['public_key']);
        Setting::set('vapid_private_key', $generated['private_key']);

        return [
            'public_key' => $generated['public_key'],
            'private_key' => $generated['private_key'],
            'subject' => $subject,
        ];
    }

    /**
     * Ambil VAPID Public Key (Base64URL) untuk dipakai browser saat subscribe.
     */
    public function getPublicKey(): ?string
    {
        try {
            return $this->getVapidKeys()['public_key'];
        } catch (\Throwable $e) {
            Log::warning('Failed to obtain VAPID public key: '.$e->getMessage());

            return null;
        }
    }

    /**
     * Generate pasangan kunci ECDSA P-256 (VAPID) baru dalam format Base64URL.
     *
     * @return array{public_key: string, private_key: string}
     */
    public function generateVapidKeys(): array
    {
        $pair = $this->generateEcKeyPair();

        return [
            'public_key' => self::base64UrlEncode($pair['public_raw']),
            'private_key' => self::base64UrlEncode($pair['private_raw']),
        ];
    }

    /**
     * Kirim notifikasi push ke seluruh peserta rapat/kegiatan yang baru dibuat.
     *
     * @param  array<int, int>  $participantUserIds
     * @return array{sent: int, failed: int, expired: int}
     */
    public function notifyMeetingCreated(Meeting $meeting, array $participantUserIds): array
    {
        $userIds = array_values(array_unique(array_filter(array_map('intval', $participantUserIds))));
        if ($userIds === []) {
            return ['sent' => 0, 'failed' => 0, 'expired' => 0];
        }

        $isKegiatan = $meeting->isKegiatan();
        $modeLabel = $meeting->mode_rapat === 'online' ? 'Online' : 'Offline';
        $tanggalLabel = $meeting->tanggal
            ? $meeting->tanggal->locale('id')->translatedFormat('d M Y')
            : now()->locale('id')->translatedFormat('d M Y');
        $waktuLabel = $meeting->waktu_mulai ? substr((string) $meeting->waktu_mulai, 0, 5).' WITA' : '';

        $topic = $meeting->nama_event ?: ($meeting->fokus_bahasan ?: $meeting->jenis_label);
        $title = $isKegiatan
            ? 'Kegiatan Baru: '.Str::limit((string) $topic, 60)
            : 'Rapat Baru: '.$meeting->jenis_label;

        $bodyParts = [];
        if (! $isKegiatan && $topic) {
            $bodyParts[] = Str::limit((string) $topic, 90);
        }
        $schedule = trim($tanggalLabel.($waktuLabel !== '' ? ' • '.$waktuLabel : '')." ({$modeLabel})");
        if ($schedule !== '') {
            $bodyParts[] = $schedule;
        }
        $bodyParts[] = $isKegiatan || $meeting->mode_rapat === 'online'
            ? 'Ketuk untuk membuka halaman presensi.'
            : 'Ketuk untuk membuka Portal Pengurus.';

        $targetUrl = ($isKegiatan || $meeting->mode_rapat === 'online')
            ? route('public.online', $meeting->unique_link)
            : route('portal.dashboard');

        return $this->sendToUsers($userIds, [
            'title' => $title,
            'body' => implode(' — ', $bodyParts),
            'url' => $targetUrl,
            'icon' => asset('assets/img/logo2.png'),
            'badge' => asset('assets/img/logo2.png'),
            'tag' => 'meeting-'.$meeting->id,
        ]);
    }

    /**
     * Kirim notifikasi push saat Admin membuat pengumuman baru yang aktif.
     *
     * @return array{sent: int, failed: int, expired: int}
     */
    public function notifyAnnouncementCreated(Announcement $announcement): array
    {
        if (! $announcement->is_active) {
            return ['sent' => 0, 'failed' => 0, 'expired' => 0];
        }

        $userIds = match ($announcement->target_type) {
            'personal' => $announcement->target_user_id ? [(int) $announcement->target_user_id] : [],
            'departemen' => $this->resolveDepartmentMemberIds((string) $announcement->target_departemen),
            default => Member::where('is_active', true)->pluck('id')->all(),
        };

        if ($userIds === []) {
            return ['sent' => 0, 'failed' => 0, 'expired' => 0];
        }

        $prefix = match ($announcement->tipe) {
            'mendesak' => '[Mendesak] ',
            'penting' => '[Penting] ',
            default => '',
        };

        return $this->sendToUsers($userIds, [
            'title' => $prefix.Str::limit((string) $announcement->judul, 70),
            'body' => Str::limit(trim(preg_replace('/\s+/', ' ', strip_tags((string) $announcement->konten))), 140),
            'url' => route('portal.announcements.index'),
            'icon' => asset('assets/img/logo2.png'),
            'badge' => asset('assets/img/logo2.png'),
            'tag' => 'announcement-'.$announcement->id,
        ]);
    }

    /**
     * Kirim notifikasi uji coba ke semua perangkat yang terdaftar untuk seorang pengurus.
     *
     * @return array{sent: int, failed: int, expired: int}
     */
    public function sendTestToMember(Member $member): array
    {
        return $this->sendToUsers([$member->id], [
            'title' => 'Notifikasi HP Aktif — Absensi HIMA TI',
            'body' => "Halo {$member->nama_lengkap}! Perangkat ini siap menerima notifikasi rapat & kegiatan terbaru.",
            'url' => route('portal.dashboard'),
            'icon' => asset('assets/img/logo2.png'),
            'badge' => asset('assets/img/logo2.png'),
            'tag' => 'test-push-'.$member->id.'-'.time(),
        ]);
    }

    /**
     * Kirim payload push secara paralel ke seluruh subscription milik daftar user ID.
     *
     * @param  array<int, int>  $userIds
     * @param  array<string, mixed>  $payload
     * @return array{sent: int, failed: int, expired: int}
     */
    public function sendToUsers(array $userIds, array $payload): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $userIds))));
        if ($ids === [] || ! $this->ensureTableReady()) {
            return ['sent' => 0, 'failed' => 0, 'expired' => 0];
        }

        $subscriptions = PushSubscription::whereIn('user_id', $ids)->get();
        if ($subscriptions->isEmpty()) {
            return ['sent' => 0, 'failed' => 0, 'expired' => 0];
        }

        try {
            $vapid = $this->getVapidKeys();
        } catch (\Throwable $e) {
            Log::error('WebPush aborted: unable to load VAPID keys. '.$e->getMessage());

            return ['sent' => 0, 'failed' => $subscriptions->count(), 'expired' => 0];
        }

        $jsonPayload = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($jsonPayload === false) {
            return ['sent' => 0, 'failed' => $subscriptions->count(), 'expired' => 0];
        }

        $sent = 0;
        $failed = 0;
        $expired = 0;
        $expiredIds = [];
        $usedIds = [];

        foreach ($subscriptions->chunk(25) as $chunk) {
            $prepared = [];

            foreach ($chunk as $sub) {
                try {
                    $encryptedBody = $this->encryptPayload($jsonPayload, $sub->public_key, $sub->auth_token);
                    $authHeader = $this->buildVapidAuthorizationHeader(
                        $sub->endpoint,
                        $vapid['public_key'],
                        $vapid['private_key'],
                        $vapid['subject']
                    );

                    $prepared[$sub->id] = [
                        'subscription' => $sub,
                        'body' => $encryptedBody,
                        'headers' => [
                            'Authorization' => $authHeader,
                            'TTL' => '86400',
                            'Urgency' => 'high',
                            'Content-Encoding' => 'aes128gcm',
                            'Content-Type' => 'application/octet-stream',
                        ],
                    ];
                } catch (\Throwable $e) {
                    $failed++;
                    Log::warning("WebPush encryption/signing failed for subscription #{$sub->id}: {$e->getMessage()}");
                }
            }

            if ($prepared === []) {
                continue;
            }

            try {
                $responses = Http::pool(function (Pool $pool) use ($prepared) {
                    $requests = [];
                    foreach ($prepared as $subId => $item) {
                        $requests[$subId] = $pool->as((string) $subId)
                            ->timeout(6)
                            ->connectTimeout(3)
                            ->withHeaders($item['headers'])
                            ->withBody($item['body'], 'application/octet-stream')
                            ->post($item['subscription']->endpoint);
                    }

                    return $requests;
                });

                foreach ($prepared as $subId => $item) {
                    $response = $responses[(string) $subId] ?? null;

                    if ($response instanceof Response) {
                        $status = $response->status();
                        if ($status >= 200 && $status < 300) {
                            $sent++;
                            $usedIds[] = $subId;
                        } elseif (in_array($status, [404, 410], true)) {
                            $expired++;
                            $expiredIds[] = $subId;
                        } else {
                            $failed++;
                            Log::warning("WebPush delivery failed for subscription #{$subId}: HTTP {$status}");
                        }
                    } else {
                        $failed++;
                    }
                }
            } catch (\Throwable $e) {
                $failed += count($prepared);
                Log::warning('WebPush pool request error: '.$e->getMessage());
            }
        }

        if ($expiredIds !== []) {
            PushSubscription::whereIn('id', $expiredIds)->delete();
        }

        if ($usedIds !== []) {
            PushSubscription::whereIn('id', $usedIds)->update(['last_used_at' => now()]);
        }

        return [
            'sent' => $sent,
            'failed' => $failed,
            'expired' => $expired,
        ];
    }

    /**
     * Enkripsi payload sesuai RFC 8291 (Web Push Message Encryption — aes128gcm) & RFC 8188.
     *
     * @param  array{public_raw: string, private_raw: string}|null  $ephemeralKeyPair  Opsional (untuk pengujian deterministik)
     */
    public function encryptPayload(
        string $plaintext,
        string $userPublicKeyBase64Url,
        string $userAuthTokenBase64Url,
        ?array $ephemeralKeyPair = null,
        ?string $salt = null
    ): string {
        $uaPublic = self::base64UrlDecode($userPublicKeyBase64Url);
        $authSecret = self::base64UrlDecode($userAuthTokenBase64Url);

        if (strlen($uaPublic) !== 65 || $uaPublic[0] !== "\x04") {
            throw new RuntimeException('Kunci publik subscription (p256dh) tidak valid.');
        }

        if (strlen($authSecret) !== 16) {
            throw new RuntimeException('Token autentikasi subscription (auth) tidak valid.');
        }

        $localKeys = $ephemeralKeyPair ?? $this->generateEcKeyPair();
        $asPublic = $localKeys['public_raw'];
        $asPrivatePem = $this->rawEcPrivateKeyToPem($localKeys['private_raw'], $asPublic);
        $uaPublicPem = $this->rawEcPublicKeyToPem($uaPublic);

        $asPrivateResource = openssl_pkey_get_private($asPrivatePem);
        $uaPublicResource = openssl_pkey_get_public($uaPublicPem);

        if ($asPrivateResource === false || $uaPublicResource === false) {
            throw new RuntimeException('Gagal memuat kunci EC untuk enkripsi Web Push.');
        }

        $sharedSecret = openssl_pkey_derive($uaPublicResource, $asPrivateResource, 32);
        if ($sharedSecret === false) {
            throw new RuntimeException('Gagal menghitung shared secret ECDH.');
        }
        $sharedSecret = str_pad($sharedSecret, 32, "\x00", STR_PAD_LEFT);

        // RFC 8291 Section 3.3: IKM = HKDF(salt=auth_secret, ikm=ecdh_secret, info="WebPush: info\0" || ua_pub || as_pub, L=32)
        $keyInfo = "WebPush: info\x00".$uaPublic.$asPublic;
        $ikm = hash_hkdf('sha256', $sharedSecret, 32, $keyInfo, $authSecret);

        $saltBytes = $salt ?? random_bytes(16);
        if (strlen($saltBytes) !== 16) {
            throw new RuntimeException('Salt enkripsi harus 16 byte.');
        }

        // RFC 8291 Section 3.4: CEK (16 bytes) & NONCE (12 bytes)
        $cek = hash_hkdf('sha256', $ikm, 16, "Content-Encoding: aes128gcm\x00", $saltBytes);
        $nonce = hash_hkdf('sha256', $ikm, 12, "Content-Encoding: nonce\x00", $saltBytes);

        // RFC 8188 Section 2: Final record delimiter 0x02
        $paddedPlaintext = $plaintext."\x02";
        $tag = '';

        $ciphertext = openssl_encrypt(
            $paddedPlaintext,
            'aes-128-gcm',
            $cek,
            OPENSSL_RAW_DATA,
            $nonce,
            $tag,
            '',
            16
        );

        if ($ciphertext === false || strlen($tag) !== 16) {
            throw new RuntimeException('Enkripsi AES-128-GCM gagal.');
        }

        // RFC 8188 Section 2.1 Header: salt (16) || rs (4) || idlen (1) || keyid (65)
        $recordSize = pack('N', 4096);
        $header = $saltBytes.$recordSize.chr(strlen($asPublic)).$asPublic;

        return $header.$ciphertext.$tag;
    }

    /**
     * Buat header Authorization VAPID sesuai RFC 8292 (`vapid t=<jwt>, k=<publicKey>`).
     */
    public function buildVapidAuthorizationHeader(
        string $endpoint,
        string $vapidPublicKeyBase64Url,
        string $vapidPrivateKeyBase64Url,
        string $subject,
        ?int $expiration = null
    ): string {
        $parsed = parse_url($endpoint);
        if (! is_array($parsed) || empty($parsed['scheme']) || empty($parsed['host'])) {
            throw new RuntimeException('Endpoint Web Push tidak valid.');
        }

        $audience = $parsed['scheme'].'://'.$parsed['host'];
        if (! empty($parsed['port'])) {
            $audience .= ':'.$parsed['port'];
        }

        $headerB64 = self::base64UrlEncode((string) json_encode([
            'typ' => 'JWT',
            'alg' => 'ES256',
        ], JSON_UNESCAPED_SLASHES));

        $claimsB64 = self::base64UrlEncode((string) json_encode([
            'aud' => $audience,
            'exp' => $expiration ?? (time() + 43200), // 12 jam
            'sub' => $subject,
        ], JSON_UNESCAPED_SLASHES));

        $signingInput = $headerB64.'.'.$claimsB64;

        $privateRaw = self::base64UrlDecode($vapidPrivateKeyBase64Url);
        $publicRaw = self::base64UrlDecode($vapidPublicKeyBase64Url);
        $privatePem = $this->rawEcPrivateKeyToPem($privateRaw, $publicRaw);

        $privateResource = openssl_pkey_get_private($privatePem);
        if ($privateResource === false) {
            throw new RuntimeException('Gagal memuat VAPID private key.');
        }

        $derSignature = '';
        if (! openssl_sign($signingInput, $derSignature, $privateResource, OPENSSL_ALGO_SHA256)) {
            throw new RuntimeException('Gagal menandatangani token VAPID ES256.');
        }

        $rawSignature = $this->derSignatureToRaw($derSignature);
        $jwt = $signingInput.'.'.self::base64UrlEncode($rawSignature);

        return "vapid t={$jwt}, k={$vapidPublicKeyBase64Url}";
    }

    /**
     * Konversi signature ECDSA ASN.1 DER dari OpenSSL ke format raw 64-byte (R || S) untuk JWS ES256.
     */
    public function derSignatureToRaw(string $der): string
    {
        $offset = 0;
        if (strlen($der) < 8 || ord($der[$offset]) !== 0x30) {
            throw new RuntimeException('Format DER signature tidak valid.');
        }

        $offset++;
        $seqLenByte = ord($der[$offset++]);
        if (($seqLenByte & 0x80) !== 0) {
            $offset += ($seqLenByte & 0x7F);
        }

        if (! isset($der[$offset]) || ord($der[$offset++]) !== 0x02) {
            throw new RuntimeException('Komponen R pada DER signature tidak valid.');
        }
        $rLen = ord($der[$offset++]);
        $r = substr($der, $offset, $rLen);
        $offset += $rLen;

        if (! isset($der[$offset]) || ord($der[$offset++]) !== 0x02) {
            throw new RuntimeException('Komponen S pada DER signature tidak valid.');
        }
        $sLen = ord($der[$offset++]);
        $s = substr($der, $offset, $sLen);

        $r = str_pad(ltrim($r, "\x00"), 32, "\x00", STR_PAD_LEFT);
        $s = str_pad(ltrim($s, "\x00"), 32, "\x00", STR_PAD_LEFT);

        return substr($r, -32).substr($s, -32);
    }

    /**
     * Generate pasangan kunci EC P-256 (prime256v1) mentah.
     *
     * @return array{public_raw: string, private_raw: string}
     */
    public function generateEcKeyPair(): array
    {
        $config = [
            'curve_name' => 'prime256v1',
            'private_key_type' => OPENSSL_KEYTYPE_EC,
        ];

        $resource = @openssl_pkey_new($config);

        if ($resource === false) {
            $cnfPath = $this->findOpenSslConfigPath();
            if ($cnfPath !== null) {
                $config['config'] = $cnfPath;
                $resource = @openssl_pkey_new($config);
            }
        }

        if ($resource === false) {
            throw new RuntimeException('OpenSSL gagal membuat kunci EC P-256: '.(openssl_error_string() ?: 'unknown error'));
        }

        $details = openssl_pkey_get_details($resource);
        if (! is_array($details) || empty($details['ec']['x']) || empty($details['ec']['y']) || empty($details['ec']['d'])) {
            throw new RuntimeException('Detail kunci EC P-256 tidak lengkap.');
        }

        $x = str_pad($details['ec']['x'], 32, "\x00", STR_PAD_LEFT);
        $y = str_pad($details['ec']['y'], 32, "\x00", STR_PAD_LEFT);
        $d = str_pad($details['ec']['d'], 32, "\x00", STR_PAD_LEFT);

        return [
            'public_raw' => "\x04".$x.$y,
            'private_raw' => $d,
        ];
    }

    public function rawEcPublicKeyToPem(string $publicRaw): string
    {
        if (strlen($publicRaw) !== 65 || $publicRaw[0] !== "\x04") {
            throw new RuntimeException('Kunci publik P-256 harus 65 byte uncompressed.');
        }

        $der = hex2bin(self::P256_SPKI_DER_PREFIX_HEX).$publicRaw;

        return "-----BEGIN PUBLIC KEY-----\n"
            .chunk_split(base64_encode($der), 64, "\n")
            ."-----END PUBLIC KEY-----\n";
    }

    public function rawEcPrivateKeyToPem(string $privateRaw, string $publicRaw): string
    {
        $privatePadded = str_pad(ltrim($privateRaw, "\x00"), 32, "\x00", STR_PAD_LEFT);
        if (strlen($privatePadded) !== 32) {
            throw new RuntimeException('Kunci privat P-256 harus 32 byte.');
        }

        if (strlen($publicRaw) !== 65 || $publicRaw[0] !== "\x04") {
            throw new RuntimeException('Kunci publik P-256 harus 65 byte uncompressed.');
        }

        $der = hex2bin(self::P256_SEC1_DER_PREFIX_HEX)
            .$privatePadded
            .hex2bin(self::P256_SEC1_DER_MIDDLE_HEX)
            .$publicRaw;

        return "-----BEGIN EC PRIVATE KEY-----\n"
            .chunk_split(base64_encode($der), 64, "\n")
            ."-----END EC PRIVATE KEY-----\n";
    }

    public static function base64UrlEncode(string $binary): string
    {
        return rtrim(strtr(base64_encode($binary), '+/', '-_'), '=');
    }

    public static function base64UrlDecode(string $encoded): string
    {
        $normalized = strtr(trim($encoded), '-_', '+/');
        $padding = strlen($normalized) % 4;
        if ($padding > 0) {
            $normalized .= str_repeat('=', 4 - $padding);
        }

        $decoded = base64_decode($normalized, true);
        if ($decoded === false) {
            throw new RuntimeException('Base64URL tidak valid.');
        }

        return $decoded;
    }

    /**
     * @return array<int, int>
     */
    protected function resolveDepartmentMemberIds(string $department): array
    {
        $deptLower = strtolower(trim($department));
        if ($deptLower === '') {
            return [];
        }

        if ($deptLower === 'bph') {
            return Member::where('is_active', true)
                ->where(function ($q) {
                    $q->where('departemen', 'Inti')
                        ->orWhere('is_bph', true);
                })
                ->pluck('id')
                ->all();
        }

        return Member::where('is_active', true)
            ->where('departemen', $department)
            ->pluck('id')
            ->all();
    }

    protected function findOpenSslConfigPath(): ?string
    {
        $phpDir = dirname(PHP_BINARY);
        $candidates = array_filter([
            getenv('OPENSSL_CONF') ?: null,
            $phpDir.DIRECTORY_SEPARATOR.'extras'.DIRECTORY_SEPARATOR.'ssl'.DIRECTORY_SEPARATOR.'openssl.cnf',
            dirname($phpDir).DIRECTORY_SEPARATOR.'extras'.DIRECTORY_SEPARATOR.'ssl'.DIRECTORY_SEPARATOR.'openssl.cnf',
            'C:\\laragon\\bin\\php\\openssl.cnf',
            '/etc/ssl/openssl.cnf',
        ]);

        foreach ($candidates as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        return null;
    }
}

