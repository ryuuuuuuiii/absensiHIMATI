<?php

namespace App\Providers;

use App\Models\Announcement;
use App\Services\SpService;
use Carbon\Carbon;
use Illuminate\Pagination\Paginator;
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

        View::composer('layouts.portal', function ($view) {
            if (auth('member')->check()) {
                $portalMember = auth('member')->user();
                app(SpService::class)->ensureActiveSpNotification($portalMember);
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

                $view->with('portalAnnouncements', $portalAnnouncements)
                    ->with('portalUnreadNotifCount', $portalUnreadNotifCount);
            }
        });
    }
}
