<?php

namespace App\Providers;

use App\Models\Announcement;
use App\Services\SpService;
use Carbon\Carbon;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (
            request()->header('x-forwarded-proto') === 'https'
            || request()->isSecure()
            || str_contains((string) request()->getHttpHost(), 'trycloudflare.com')
            || str_contains((string) request()->getHttpHost(), 'ngrok')
            || str_contains((string) request()->getHttpHost(), 'localtunnel.me')
        ) {
            URL::forceScheme('https');
        }

        Carbon::setLocale('id');
        Paginator::defaultView('vendor.pagination.custom');
        Paginator::defaultSimpleView('vendor.pagination.custom');

        // Auto-run pending migrations in production/cloud (e.g. Vercel + TiDB)
        if (! cache()->has('db_auto_migrated_v6') || ! Schema::hasTable('member_badges') || ! Schema::hasColumn('meetings', 'sifat_kegiatan')) {
            try {
                Artisan::call('migrate', ['--force' => true]);
                cache()->forever('db_auto_migrated_v6', true);
            } catch (\Throwable $e) {
                // Log and continue gracefully
            }
        }

        View::composer(['layouts.portal', 'layouts.public'], function ($view) {
            $expPopupData = session('exp_popup');

            if (auth('member')->check()) {
                $portalMember = auth('member')->user();
                app(SpService::class)->ensureActiveSpNotification($portalMember);

                // Tidak di-cache: objek Eloquent tidak bisa di-unserialize dari cache
                // (cache.serializable_classes = false di Laravel 13), dan query ini ringan (maks 10 baris).
                $portalAnnouncements = Announcement::active()
                    ->forMember($portalMember)
                    ->with(['admin', 'reads' => function ($q) use ($portalMember) {
                        $q->where('user_id', $portalMember->id);
                    }])
                    ->orderBy('pinned', 'desc')
                    ->orderBy('created_at', 'desc')
                    ->take(10)
                    ->get();

                $portalUnreadNotifCount = $portalAnnouncements->filter(function ($a) {
                    $read = $a->reads->first();

                    return ! ($read && $read->read_at);
                })->count();

                // Deteksi pertambahan EXP untuk memicu pop-up gamifikasi
                $lastKnownExpKey = 'member_last_known_exp_'.$portalMember->id;

                if (! $expPopupData && session()->has($lastKnownExpKey)) {
                    $lastExp = (int) session($lastKnownExpKey);
                    $currentExp = (int) $portalMember->exp;

                    if ($currentExp > $lastExp) {
                        $diff = $currentExp - $lastExp;
                        $prevLevel = (int) floor($lastExp / 300) + 1;
                        $curLevel = (int) floor($currentExp / 300) + 1;

                        $expPopupData = [
                            'exp' => $diff,
                            'reason' => $diff >= 500 ? 'Kepanitiaan Event' : 'Presensi Kehadiran',
                            'total_exp' => $currentExp,
                            'is_level_up' => $curLevel > $prevLevel,
                            'prev_level' => $prevLevel,
                            'level_data' => $portalMember->level_data,
                        ];
                    }
                }

                // Perbarui posisi EXP yang diketahui di session
                session([$lastKnownExpKey => (int) $portalMember->exp]);

                $view->with('portalAnnouncements', $portalAnnouncements)
                    ->with('portalUnreadNotifCount', $portalUnreadNotifCount);
            }

            $view->with('expPopupData', $expPopupData);
        });
    }
}
