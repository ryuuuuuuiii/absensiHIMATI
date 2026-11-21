<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\PushSubscription;
use App\Services\WebPushService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PushSubscriptionController extends Controller
{
    public function __construct(protected WebPushService $webPushService) {}

    public function config(): JsonResponse
    {
        /** @var Member $member */
        $member = auth('member')->user();

        $tableReady = $this->webPushService->ensureTableReady();
        $publicKey = $this->webPushService->getPublicKey();
        $subscribedCount = $tableReady
            ? PushSubscription::where('user_id', $member->id)->count()
            : 0;

        return response()->json([
            'enabled' => filled($publicKey) && $tableReady,
            'public_key' => $publicKey,
            'subscribed_count' => $subscribedCount,
        ]);
    }

    public function subscribe(Request $request): JsonResponse
    {
        if (! $this->webPushService->ensureTableReady()) {
            return response()->json([
                'success' => false,
                'message' => 'Tabel notifikasi belum siap. Silakan coba beberapa saat lagi.',
            ], 503);
        }

        $validated = $request->validate([
            'endpoint' => ['required', 'url', 'max:2000'],
            'keys' => ['required', 'array'],
            'keys.p256dh' => ['required', 'string', 'max:255'],
            'keys.auth' => ['required', 'string', 'max:255'],
            'content_encoding' => ['nullable', 'string', 'max:30'],
        ]);

        // Validasi format dasar kunci p256dh (65 byte uncompressed) dan auth (16 byte)
        try {
            $pubBytes = WebPushService::base64UrlDecode($validated['keys']['p256dh']);
            $authBytes = WebPushService::base64UrlDecode($validated['keys']['auth']);
            if (strlen($pubBytes) !== 65 || $pubBytes[0] !== "\x04" || strlen($authBytes) !== 16) {
                return response()->json([
                    'success' => false,
                    'message' => 'Format kunci langganan browser tidak valid.',
                ], 422);
            }
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Format kunci langganan browser tidak valid.',
            ], 422);
        }

        /** @var Member $member */
        $member = auth('member')->user();
        $endpoint = $validated['endpoint'];
        $endpointHash = hash('sha256', $endpoint);

        PushSubscription::updateOrCreate(
            ['endpoint_hash' => $endpointHash],
            [
                'user_id' => $member->id,
                'endpoint' => $endpoint,
                'public_key' => $validated['keys']['p256dh'],
                'auth_token' => $validated['keys']['auth'],
                'content_encoding' => $validated['content_encoding'] ?? 'aes128gcm',
                'user_agent' => substr((string) $request->userAgent(), 0, 255),
                'last_used_at' => now(),
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Notifikasi HP berhasil diaktifkan di perangkat ini!',
            'subscribed_count' => PushSubscription::where('user_id', $member->id)->count(),
        ]);
    }

    public function unsubscribe(Request $request): JsonResponse
    {
        if (! $this->webPushService->ensureTableReady()) {
            return response()->json(['success' => true, 'subscribed_count' => 0]);
        }

        $validated = $request->validate([
            'endpoint' => ['required', 'string', 'max:2000'],
        ]);

        /** @var Member $member */
        $member = auth('member')->user();
        $endpointHash = hash('sha256', $validated['endpoint']);

        PushSubscription::where('user_id', $member->id)
            ->where('endpoint_hash', $endpointHash)
            ->delete();

        return response()->json([
            'success' => true,
            'message' => 'Notifikasi HP pada perangkat ini telah dimatikan.',
            'subscribed_count' => PushSubscription::where('user_id', $member->id)->count(),
        ]);
    }

    public function sendTest(): JsonResponse
    {
        /** @var Member $member */
        $member = auth('member')->user();

        $result = $this->webPushService->sendTestToMember($member);

        if ($result['sent'] > 0) {
            return response()->json([
                'success' => true,
                'message' => "Notifikasi tes berhasil dikirim ke {$result['sent']} perangkat Anda!",
                'result' => $result,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Belum ada perangkat aktif atau pengiriman gagal. Coba aktifkan ulang notifikasi di perangkat ini.',
            'result' => $result,
        ], 422);
    }
}

