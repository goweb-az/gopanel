<?php

namespace App\Http\Middleware\Seo;

use App\Models\Seo\SiteRedirect;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/* 
 * @var SiteRedirectMiddleware $this
 * İstek geldiğinde tabloya göre yönlendirme yapar.
 */

class SiteRedirectMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $this->maybeRedirect($request);
        return $response ?: $next($request);
    }

    protected function maybeRedirect(Request $request): ?Response
    {
        $site_redirect_status = config('gopanel.site_redirect_status', true);
        if (!$site_redirect_status)
            return null;

        if ($request->is('/') || $request->path() === '' || $request->path() === '/') {
            return null;
        }

        $locale     = app()->getLocale();
        $fullUrl    = rtrim($request->fullUrl(), '/');
        $pathWithLocale = '/' . trim($request->path(), '/'); // /az/... olabiler
        $candidates = Cache::remember("site_redirect_rules_{$locale}", now()->addMinutes(5), function () use ($locale) {
            return SiteRedirect::active()
                ->forLocale($locale)
                ->where("is_active", true)
                ->orderByDesc('priority')
                ->orderBy('id')
                ->get();
        });

        foreach ($candidates as $rule) {
            // Tam URL və yol üçün ayrıca cəhd edin:
            if ($rule->matches($fullUrl, $locale) || $rule->matches($pathWithLocale, $locale)) {
                $rule->registerHit();
                $target = $rule->target ?: url('/');
                return redirect()->to($target, $rule->http_code);
            }
        }

        // dildən asılı olmayan qaydaları ehtiyat nüsxə kimi sınayaq:
        $fallbackKey = "site_redirect_rules_all";
        $fallback = Cache::remember($fallbackKey, now()->addMinutes(5), function () {
            return SiteRedirect::active()
                ->whereNull('locale')
                ->where("is_active", true)
                ->orderByDesc('priority')
                ->orderBy('id')
                ->get();
        });

        foreach ($fallback as $rule) {
            if ($rule->matches($fullUrl, $locale) || $rule->matches($pathWithLocale, $locale)) {
                $rule->registerHit();
                $target = $rule->target ?: url('/');
                return redirect()->to($target, $rule->http_code);
            }
        }

        return null;
    }



    private function fallback() {}
}
