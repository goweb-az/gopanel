<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\Site\SiteStatusService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Paneldə «Sayt statusu» bağlıdırsa ziyarətçiyə 503 + Retry-After.
 *
 * Niyə: əvvəl yoxlama yalnız layout-un içində idi (`@if site_status == 0`):
 *  - cavab 200 idi → axtarış sistemi təmir səhifəsini indeksləyə bilərdi;
 *    503 + `Retry-After` isə «müvəqqəti» deməkdir, reytinq qorunur;
 *  - layout-u işlətməyən cavablar (Markdown, sitemap, RSS) açıq qalırdı;
 *  - admin bağlı saytı yoxlaya bilmirdi.
 *
 * `routes/web.php` qrupuna bağlıdır (`RouteServiceProvider`), panelə təsir etmir.
 * Default `SITE_STATUS_ENFORCE=false` → heç nə etmir, köhnə layout davranışı qalır.
 *
 * Panelə daxil olmuş admin saytı adi görür: middleware `siteAdminPreview`
 * paylaşır, layout həmin halda «təmir» blokunu ötürür.
 *
 * Mənbə: qrgate `SiteStatusMiddleware` - layihəyə xas istisnalar (`qr/*`,
 * `endir`) config-ə (`site_status.except`) çıxarılıb, dil seçimi çıxarılıb
 * (təmir səhifəsi tərcümə cədvəlindən asılı olmasın - baza problemi zamanı da açılsın).
 */
final class SiteStatusMiddleware
{
    public function __construct(private readonly SiteStatusService $status)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if (!config('custom.security.site_status.enforce', false) || $this->status->isSiteEnabled()) {
            return $next($request);
        }

        if (auth('gopanel')->check()) {
            View::share('siteAdminPreview', true);

            return $next($request);
        }

        $except = (array) config('custom.security.site_status.except', []);

        if ($except !== [] && $request->is(...$except)) {
            return $next($request);
        }

        $retryAfter = (string) max(60, (int) config('custom.security.site_status.retry_after', 3600));

        if (!$request->isMethod('GET') && !$request->isMethod('HEAD')) {
            abort(503, 'Sayt müvəqqəti olaraq bağlıdır.', ['Retry-After' => $retryAfter]);
        }

        return response()
            ->view('site.pages.errors.maintenance', [], 503)
            ->header('Retry-After', $retryAfter)
            ->header('Cache-Control', 'no-store');
    }
}
