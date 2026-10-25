<?php

namespace App\Http\Middleware\Gopanel;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Paneli YALNIZ öz domenindən (`GOPANEL_URL`) açır, qalan host-larda 404.
 *
 * Niyə: canlıda panel ayrıca subdomendə olur (go.example.dev). Eyni Laravel
 * tətbiqi sayt domenində də işlədiyi üçün `example.dev/gopanel` da açılırdı -
 * admin sessiyası sayt cookie-si ilə qarışır, nginx IP məhdudiyyəti isə yan
 * keçilir. Bax: docs/rules/domains-cdn-gopanel.md → «GoPanel host məhdudiyyəti».
 *
 * `GOPANEL_URL` boşdursa heç nə yoxlanmır - lokalda panel hər host-da açılır.
 * Route qrupuna `RouteServiceProvider`-də bağlanıb (alias `gopanel.host`).
 */
class GopanelHostMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $gopanelUrl = config('app.gopanel_url');

        if (empty($gopanelUrl)) {
            return $next($request);
        }

        $allowedHost = parse_url($gopanelUrl, PHP_URL_HOST);

        if (!$allowedHost) {
            return $next($request);
        }

        if ($request->getHost() !== $allowedHost) {
            abort(404);
        }

        return $next($request);
    }
}
