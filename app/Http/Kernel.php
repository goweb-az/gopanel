<?php

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    /**
     * The application's global HTTP middleware stack.
     *
     * These middleware are run during every request to your application.
     *
     * @var array<int, class-string|string>
     */
    protected $middleware = [
        // Server-Timing başlığı - SERVER_TIMING_ENABLED=true + APP_DEBUG=true olmadan heç nə etmir
        \App\Http\Middleware\ServerTiming::class,
        // \App\Http\Middleware\TrustHosts::class,
        \App\Http\Middleware\TrustProxies::class,
        // Dev domen qoruyucusu - DEV_GUARD_ENABLED=false (default) olanda heç nə etmir.
        // TrustProxies-dən SONRA: real IP-ni görməlidir.
        \App\Http\Middleware\Security\RestrictDevDomain::class,
        \Illuminate\Http\Middleware\HandleCors::class,
        \App\Http\Middleware\PreventRequestsDuringMaintenance::class,
        \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
        \App\Http\Middleware\TrimStrings::class,
        \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
    ];

    /**
     * The application's route middleware groups.
     *
     * @var array<string, array<int, class-string|string>>
     */
    protected $middlewareGroups = [
        'web' => [
            \App\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            //Kopyalama alətlərini dayanadırmaq üçün
            \App\Http\Middleware\HumanGate::class,
            // HTML səhifəyə `Link: <...md>; rel="alternate"` başlığı (SEO / AI agentlər)
            \App\Http\Middleware\Seo\MarkdownAlternateMiddleware::class,
        ],

        'api' => [
            // \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
            \Illuminate\Routing\Middleware\ThrottleRequests::class . ':api',
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],
    ];

    /**
     * The application's middleware aliases.
     *
     * Aliases may be used instead of class names to conveniently assign middleware to routes and groups.
     *
     * @var array<string, class-string|string>
     */
    protected $middlewareAliases = [
        'auth' => \App\Http\Middleware\Authenticate::class,
        'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
        'auth.session' => \Illuminate\Session\Middleware\AuthenticateSession::class,
        'cache.headers' => \Illuminate\Http\Middleware\SetCacheHeaders::class,
        'can' => \Illuminate\Auth\Middleware\Authorize::class,
        'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
        'password.confirm' => \Illuminate\Auth\Middleware\RequirePassword::class,
        'precognitive' => \Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests::class,
        'signed' => \App\Http\Middleware\ValidateSignature::class,
        'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
        'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
        'gopanel' => \App\Http\Middleware\Gopanel::class,
        'gopanel.can' => \App\Http\Middleware\Gopanel\GopanelPermissionMiddleware::class,
        'gopanel.host' => \App\Http\Middleware\Gopanel\GopanelHostMiddleware::class,
        'language' => \App\Http\Middleware\LanguageMiddleware::class,
        'site.redirects' => \App\Http\Middleware\Seo\SiteRedirectMiddleware::class,
        'track.analytics' => \App\Http\Middleware\Seo\TrackAnalyticsMiddleware::class,
        // Təhlükəsizlik - hamısı config ilə idarə olunur, default sönük (docs/security-and-auth.md)
        'gopanel.pin' => \App\Http\Middleware\Gopanel\RequireAdminPin::class,
        'ip.restrict' => \App\Http\Middleware\Security\RestrictByIp::class,
        'dev.guard' => \App\Http\Middleware\Security\RestrictDevDomain::class,
        'site.status' => \App\Http\Middleware\SiteStatusMiddleware::class,
        'server.timing' => \App\Http\Middleware\ServerTiming::class,
    ];
}
