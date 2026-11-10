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

        // Auto-run pending migrations in production/cloud if gamification tables are missing
        if (! cache()->has('db_migrated_gamification_v2')) {
            try {
                if (! Schema::hasTable('member_badges')) {
                    Artisan::call('migrate', ['--force' => true]);
                }
                cache()->forever('db_migrated_gamification_v2', true);
            } catch (\Throwable $e) {
                // Log and continue gracefully
            }
        }

        View::composer('layouts.portal', function ($view) {
            if (auth('member')->check()) {
                $portalMember = auth('member')->user();
                app(SpService::class)->ensureActiveSpNotification($portalMember);

                [$portalAnnouncements, $portalUnreadNotifCount] = cache()->remember(
                    "portal_layout_notifs_{$portalMember->id}",
                    30,
                    function () use ($portalMember) {
                        $announcements = Announcement::active()
                            ->forMember($portalMember)
                            ->with(['admin', 'reads' => function ($q) use ($portalMember) {
                                $q->where('user_id', $portalMember->id);
                            }])
                            ->orderBy('pinned', 'desc')
                            ->orderBy('created_at', 'desc')
                            ->take(10)
                            ->get();

                        $unreadCount = $announcements->filter(function ($a) {
                            $read = $a->reads->first();

                            return ! ($read && $read->read_at);
                        })->count();

                        return [$announcements, $unreadCount];
                    }
                );

                $view->with('portalAnnouncements', $portalAnnouncements)
                    ->with('portalUnreadNotifCount', $portalUnreadNotifCount);
            }
        });
    }
}
