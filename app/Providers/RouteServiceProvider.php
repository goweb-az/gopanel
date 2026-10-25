<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/home';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            // site.status: SITE_STATUS_ENFORCE=false (default) olanda heç nə etmir
            Route::middleware(['web', 'site.status', 'site.redirects', 'track.analytics'])
                ->group(base_path('routes/web.php'));

            // gopanel.host: GOPANEL_URL boşdursa heç nə etmir (lokal), doludursa yad host-da 404
            // ip.restrict: IP_RESTRICTION_ENABLED=false (default) olanda heç nə etmir
            Route::middleware(['web', 'gopanel.host', 'ip.restrict'])
                ->namespace($this->namespace)
                ->prefix('gopanel')
                ->name('gopanel.')
                ->group(base_path('routes/gopanel.php'));
        });
    }
}
