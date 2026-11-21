<?php

namespace App\Console\Commands;

use App\Models\Setting;
use App\Services\WebPushService;
use Illuminate\Console\Command;

class GenerateVapidKeysCommand extends Command
{
    protected $signature = 'webpush:vapid {--save : Simpan langsung ke tabel settings}';

    protected $description = 'Generate pasangan kunci VAPID (ECDSA P-256) untuk Web Push Notification';

    public function handle(WebPushService $webPushService): int
    {
        $keys = $webPushService->generateVapidKeys();

        if ($this->option('save')) {
            Setting::set('vapid_public_key', $keys['public_key']);
            Setting::set('vapid_private_key', $keys['private_key']);
            $this->info('Kunci VAPID berhasil dibuat dan disimpan ke tabel settings.');
        }

        $this->line('');
        $this->line('VAPID_PUBLIC_KEY='.$keys['public_key']);
        $this->line('VAPID_PRIVATE_KEY='.$keys['private_key']);
        $this->line('VAPID_SUBJECT=mailto:admin@himati.id');
        $this->line('');

        return self::SUCCESS;
    }
}

