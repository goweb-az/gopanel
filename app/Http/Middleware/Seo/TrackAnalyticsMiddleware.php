<?php

namespace App\Http\Middleware\Seo;

use Closure;
use App\Events\Analytics\ClickRegistered;
use App\Helpers\Analytics\TrackingHelper;
use App\Models\Settings\SiteSetting;
use Illuminate\Support\Facades\Log;

class TrackAnalyticsMiddleware
{
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        // yalnız status 200 olduqda işləsin
        if ($response->getStatusCode() === 200) {
            $this->analytics($request);
        }

        return $response;
    }

    private function analytics($request): void
    {
        $status = SiteSetting::getCached()?->site_analytics ?? false;
        if (!$status) {
            return;
        }

        try {
            $query = $request->query();
            $lang  = TrackingHelper::normalizeLanguage($request->header('Accept-Language'));

            // Reklam platformasını təhlil edin
            $platformMap = config('seo.analytics.ad_platforms', []);
            $logoMap     = config('seo.analytics.ad_logos', []);
            $resolved    = TrackingHelper::resolveAdPlatform($query, $platformMap, $logoMap);

            // UTM
            $utm = [
                'utm_source'   => $query['utm_source']   ?? null,
                'utm_medium'   => $query['utm_medium']   ?? null,
                'utm_campaign' => $query['utm_campaign'] ?? null,
                'utm_term'     => $query['utm_term']     ?? null,
                'utm_content'  => $query['utm_content']  ?? null,
            ];

            // Xam reklam parametrləri
            $platformKeys = array_keys($platformMap);
            $rawAdIds = array_intersect_key($query, array_flip($platformKeys));

            // Event trigger
            event(new ClickRegistered(array_filter([
                'ip_address'      => $request->ip(),
                'user_agent'      => $request->userAgent(),
                'accept_language' => $lang['full'],
                'referer'         => TrackingHelper::clamp($request->headers->get('referer'), 2048),
                'url'             => TrackingHelper::clamp($request->fullUrl(), 2048),

                // UTM parametrləri
                ...$utm,

                // Xam reklam idləri
                ...$rawAdIds,

                // Həll edilmiş platformalar
                'ad_platform'   => $resolved['ad_platform']   ?? null,
                'platform_logo' => $resolved['platform_logo'] ?? null,
                'platform_data' => $resolved['platform_data'] ?? null,
            ])));
        } catch (\Throwable $th) {
            Log::error('TrackAnalyticsMiddleware error: ' . $th->getMessage(), [
                'trace' => $th->getTraceAsString(),
            ]);
        }
    }
}
