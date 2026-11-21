<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Member;
use App\Models\PushSubscription;
use App\Services\WebPushService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Client\Request as HttpClientRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class WebPushNotificationTest extends TestCase
{
    use DatabaseTransactions;

    private function createAdmin(): Admin
    {
        return Admin::create([
            'username' => 'admin_push_'.Str::lower(Str::random(6)),
            'nama' => 'Admin Push Test',
            'email' => 'admin_push_'.Str::lower(Str::random(6)).'@himati.test',
            'password' => Hash::make('password123'),
        ]);
    }

    private function createMember(array $overrides = []): Member
    {
        return Member::create(array_merge([
            'nama_lengkap' => 'Pengurus Push '.Str::random(5),
            'departemen' => 'KOMINFO',
            'jabatan' => 'Anggota',
            'email' => 'push_'.Str::lower(Str::random(8)).'@himati.test',
            'password' => Hash::make('password123'),
            'qr_code' => 'HIMATI-PUSH-'.Str::upper(Str::random(8)),
            'is_active' => true,
        ], $overrides));
    }

    /**
     * Helper: buat subscription palsu dengan pasangan kunci EC P-256 valid.
     *
     * @return array{endpoint: string, keys: array{p256dh: string, auth: string}}
     */
    private function makeValidSubscriptionPayload(string $suffix = 'device'): array
    {
        $service = app(WebPushService::class);
        $uaKeys = $service->generateEcKeyPair();

        return [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/test-'.$suffix.'-'.Str::random(12),
            'keys' => [
                'p256dh' => WebPushService::base64UrlEncode($uaKeys['public_raw']),
                'auth' => WebPushService::base64UrlEncode(random_bytes(16)),
            ],
            'content_encoding' => 'aes128gcm',
        ];
    }

    /**
     * Konversi raw 64-byte ES256 signature (R || S) kembali ke ASN.1 DER untuk verifikasi OpenSSL.
     */
    private function rawSignatureToDer(string $raw): string
    {
        $r = ltrim(substr($raw, 0, 32), "\x00");
        $s = ltrim(substr($raw, 32, 32), "\x00");

        if ($r === '' || (ord($r[0]) & 0x80) !== 0) {
            $r = "\x00".$r;
        }
        if ($s === '' || (ord($s[0]) & 0x80) !== 0) {
            $s = "\x00".$s;
        }

        $seq = "\x02".chr(strlen($r)).$r."\x02".chr(strlen($s)).$s;

        return "\x30".chr(strlen($seq)).$seq;
    }

    public function test_rfc8291_aes128gcm_payload_encrypts_and_decrypts_cleanly(): void
    {
        $service = app(WebPushService::class);

        // Simulasikan kunci browser (UA)
        $uaKeys = $service->generateEcKeyPair();
        $uaPublicB64 = WebPushService::base64UrlEncode($uaKeys['public_raw']);
        $authSecret = random_bytes(16);
        $authB64 = WebPushService::base64UrlEncode($authSecret);

        $originalPayload = json_encode([
            'title' => 'Rapat Baru: Internal KOMINFO',
            'body' => 'Evaluasi proker hari ini pukul 19:00 WITA.',
            'url' => 'https://absensi-himati.vercel.app/portal',
        ], JSON_UNESCAPED_UNICODE);

        $encrypted = $service->encryptPayload((string) $originalPayload, $uaPublicB64, $authB64);

        // Parse header RFC 8188 (16 byte salt + 4 byte rs + 1 byte idlen + 65 byte asPublic = 86 bytes)
        $salt = substr($encrypted, 0, 16);
        $rs = unpack('N', substr($encrypted, 16, 4))[1];
        $idlen = ord($encrypted[20]);
        $asPublic = substr($encrypted, 21, $idlen);
        $body = substr($encrypted, 21 + $idlen);

        $this->assertSame(4096, $rs);
        $this->assertSame(65, $idlen);
        $this->assertSame("\x04", $asPublic[0]);

        $ciphertext = substr($body, 0, -16);
        $tag = substr($body, -16);

        // Dekripsi dari sisi browser menggunakan UA private key + AS public key
        $uaPrivPem = $service->rawEcPrivateKeyToPem($uaKeys['private_raw'], $uaKeys['public_raw']);
        $asPubPem = $service->rawEcPublicKeyToPem($asPublic);

        $sharedSecret = openssl_pkey_derive(
            openssl_pkey_get_public($asPubPem),
            openssl_pkey_get_private($uaPrivPem),
            32
        );
        $sharedSecret = str_pad((string) $sharedSecret, 32, "\x00", STR_PAD_LEFT);

        $ikm = hash_hkdf('sha256', $sharedSecret, 32, "WebPush: info\x00".$uaKeys['public_raw'].$asPublic, $authSecret);
        $cek = hash_hkdf('sha256', $ikm, 16, "Content-Encoding: aes128gcm\x00", $salt);
        $nonce = hash_hkdf('sha256', $ikm, 12, "Content-Encoding: nonce\x00", $salt);

        $decryptedPadded = openssl_decrypt($ciphertext, 'aes-128-gcm', $cek, OPENSSL_RAW_DATA, $nonce, $tag);

        $this->assertNotFalse($decryptedPadded);
        $this->assertSame("\x02", substr((string) $decryptedPadded, -1));
        $this->assertSame($originalPayload, substr((string) $decryptedPadded, 0, -1));
    }

    public function test_rfc8292_vapid_authorization_header_has_valid_es256_signature(): void
    {
        $service = app(WebPushService::class);
        $vapid = $service->generateVapidKeys();

        $header = $service->buildVapidAuthorizationHeader(
            'https://fcm.googleapis.com/fcm/send/sample-endpoint-123',
            $vapid['public_key'],
            $vapid['private_key'],
            'mailto:admin@himati.id'
        );

        $this->assertMatchesRegularExpression('/^vapid t=([^,]+), k=(.+)$/', $header);
        preg_match('/^vapid t=([^,]+), k=(.+)$/', $header, $matches);

        $jwt = $matches[1];
        $k = $matches[2];
        $this->assertSame($vapid['public_key'], $k);

        $parts = explode('.', $jwt);
        $this->assertCount(3, $parts);

        $jwtHeader = json_decode(WebPushService::base64UrlDecode($parts[0]), true);
        $jwtClaims = json_decode(WebPushService::base64UrlDecode($parts[1]), true);

        $this->assertSame('JWT', $jwtHeader['typ']);
        $this->assertSame('ES256', $jwtHeader['alg']);
        $this->assertSame('https://fcm.googleapis.com', $jwtClaims['aud']);
        $this->assertSame('mailto:admin@himati.id', $jwtClaims['sub']);
        $this->assertGreaterThan(time(), $jwtClaims['exp']);

        $rawSig = WebPushService::base64UrlDecode($parts[2]);
        $this->assertSame(64, strlen($rawSig));

        $derSig = $this->rawSignatureToDer($rawSig);
        $pubPem = $service->rawEcPublicKeyToPem(WebPushService::base64UrlDecode($vapid['public_key']));

        $verified = openssl_verify($parts[0].'.'.$parts[1], $derSig, $pubPem, OPENSSL_ALGO_SHA256);
        $this->assertSame(1, $verified);
    }

    public function test_member_can_fetch_config_subscribe_send_test_and_unsubscribe(): void
    {
        Http::fake([
            'fcm.googleapis.com/*' => Http::response('', 201),
        ]);

        $member = $this->createMember();
        $subPayload = $this->makeValidSubscriptionPayload('member1');

        // 1. Config
        $configRes = $this->actingAs($member, 'member')
            ->getJson(route('portal.push.config'))
            ->assertOk()
            ->assertJsonPath('enabled', true)
            ->assertJsonPath('subscribed_count', 0);
        $this->assertNotEmpty($configRes->json('public_key'));

        // 2. Subscribe invalid keys -> 422
        $this->actingAs($member, 'member')
            ->postJson(route('portal.push.subscribe'), [
                'endpoint' => $subPayload['endpoint'],
                'keys' => ['p256dh' => 'invalid', 'auth' => 'invalid'],
            ])
            ->assertStatus(422);

        // 3. Subscribe valid keys
        $this->actingAs($member, 'member')
            ->postJson(route('portal.push.subscribe'), $subPayload)
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('subscribed_count', 1);

        $this->assertDatabaseHas('push_subscriptions', [
            'user_id' => $member->id,
            'endpoint_hash' => hash('sha256', $subPayload['endpoint']),
        ]);

        // 4. Send test notification
        $this->actingAs($member, 'member')
            ->postJson(route('portal.push.test'))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('result.sent', 1);

        Http::assertSent(function (HttpClientRequest $request) use ($subPayload) {
            return $request->url() === $subPayload['endpoint']
                && str_starts_with((string) $request->header('Authorization')[0], 'vapid t=')
                && ($request->header('Content-Encoding')[0] ?? '') === 'aes128gcm';
        });

        // 5. Unsubscribe
        $this->actingAs($member, 'member')
            ->postJson(route('portal.push.unsubscribe'), ['endpoint' => $subPayload['endpoint']])
            ->assertOk()
            ->assertJsonPath('subscribed_count', 0);

        $this->assertDatabaseMissing('push_subscriptions', [
            'user_id' => $member->id,
            'endpoint_hash' => hash('sha256', $subPayload['endpoint']),
        ]);
    }

    public function test_creating_meeting_sends_web_push_only_to_participants(): void
    {
        Http::fake([
            'fcm.googleapis.com/*' => Http::response('', 201),
        ]);

        $admin = $this->createAdmin();
        $kominfoMember = $this->createMember([
            'departemen' => 'KOMINFO',
            'jabatan' => 'Koordinator Departemen',
            'is_bph' => true,
        ]);
        $psdmMember = $this->createMember([
            'departemen' => 'PSDM',
            'jabatan' => 'Anggota',
        ]);

        $kominfoSub = $this->makeValidSubscriptionPayload('kominfo');
        $psdmSub = $this->makeValidSubscriptionPayload('psdm');

        PushSubscription::create([
            'user_id' => $kominfoMember->id,
            'endpoint' => $kominfoSub['endpoint'],
            'endpoint_hash' => hash('sha256', $kominfoSub['endpoint']),
            'public_key' => $kominfoSub['keys']['p256dh'],
            'auth_token' => $kominfoSub['keys']['auth'],
        ]);

        PushSubscription::create([
            'user_id' => $psdmMember->id,
            'endpoint' => $psdmSub['endpoint'],
            'endpoint_hash' => hash('sha256', $psdmSub['endpoint']),
            'public_key' => $psdmSub['keys']['p256dh'],
            'auth_token' => $psdmSub['keys']['auth'],
        ]);

        $this->actingAs($admin, 'web')
            ->post(route('meetings.store'), [
                'created_by' => $kominfoMember->id,
                'jenis_rapat' => 'internal_dept',
                'departemen' => 'KOMINFO',
                'fokus_bahasan_internal' => 'Rapat Publikasi & Desain',
                'mode_rapat' => 'online',
                'tanggal' => now()->toDateString(),
                'waktu_mulai' => '19:00',
            ])
            ->assertRedirect();

        Http::assertSent(fn (HttpClientRequest $req) => $req->url() === $kominfoSub['endpoint']);
        Http::assertNotSent(fn (HttpClientRequest $req) => $req->url() === $psdmSub['endpoint']);
    }

    public function test_creating_announcement_sends_web_push_to_target_department_and_cleans_up_expired_subscriptions(): void
    {
        $admin = $this->createAdmin();
        $psdmMember = $this->createMember(['departemen' => 'PSDM']);
        $kominfoMember = $this->createMember(['departemen' => 'KOMINFO']);

        $expiredSub = $this->makeValidSubscriptionPayload('expired');
        $otherSub = $this->makeValidSubscriptionPayload('other');

        Http::fake([
            $expiredSub['endpoint'] => Http::response('', 410),
            '*' => Http::response('', 201),
        ]);

        PushSubscription::create([
            'user_id' => $psdmMember->id,
            'endpoint' => $expiredSub['endpoint'],
            'endpoint_hash' => hash('sha256', $expiredSub['endpoint']),
            'public_key' => $expiredSub['keys']['p256dh'],
            'auth_token' => $expiredSub['keys']['auth'],
        ]);

        PushSubscription::create([
            'user_id' => $kominfoMember->id,
            'endpoint' => $otherSub['endpoint'],
            'endpoint_hash' => hash('sha256', $otherSub['endpoint']),
            'public_key' => $otherSub['keys']['p256dh'],
            'auth_token' => $otherSub['keys']['auth'],
        ]);

        $this->actingAs($admin, 'web')
            ->post(route('announcements.store'), [
                'judul' => 'Pengumuman Penting PSDM',
                'konten' => 'Mohon segera mengisi form evaluasi mingguan.',
                'tipe' => 'penting',
                'target_type' => 'departemen',
                'target_departemen' => 'PSDM',
                'is_banner' => '1',
            ])
            ->assertRedirect(route('announcements.index'));

        Http::assertSent(fn (HttpClientRequest $req) => $req->url() === $expiredSub['endpoint']);
        Http::assertNotSent(fn (HttpClientRequest $req) => $req->url() === $otherSub['endpoint']);

        // Subscription yang mengembalikan HTTP 410 Gone otomatis dihapus dari database
        $this->assertDatabaseMissing('push_subscriptions', [
            'endpoint_hash' => hash('sha256', $expiredSub['endpoint']),
        ]);

        // Subscription departemen lain tidak ikut terhapus
        $this->assertDatabaseHas('push_subscriptions', [
            'endpoint_hash' => hash('sha256', $otherSub['endpoint']),
        ]);
    }
}

