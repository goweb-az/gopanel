<?php

namespace App\Http\Middleware;

use App\Models\Settings\SiteSetting;
use Closure;
use Illuminate\Http\Request;

class HumanGate
{
    public function handle(Request $request, Closure $next)
    {

        $status = (SiteSetting::getCached())?->block_bad_bots ?? false;
        if (!$status)
            return $next($request);



        // API/aktiv sorğularını keçin
        if ($request->is('api/*') || $request->is('storage/*') || $request->is('assets/*')) {
            return $next($request);
        }

        // Yalnız HTML səhifələri
        $accept = $request->header('accept', '');
        $isHtml = str_contains($accept, 'text/html');

        // Məlum yaxşı botlara icazə verin (UA saxtalaşdırıla bilər, lakin SEO üçün lazımdır)
        $goodBots = config('seo.bots.good');
        $ua = $request->userAgent() ?? '';
        foreach ($goodBots as $bot) {
            if (stripos($ua, $bot) !== false) {
                return $next($request);
            }
        }

        if ($request->isMethod('GET') && $isHtml) {
            if (!$request->cookies->has('__hs')) {
                // JS ilə kukiləri quraşdırın və yeniləyin; JS ilə işləməyən surətçıxaranlar burada qalacaq
                return response()->view('security.verify-cookie')
                    ->header('Cache-Control', 'no-store');
            }
        }

        return $next($request);
    }
}
