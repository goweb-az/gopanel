<?php

declare(strict_types=1);

namespace App\Http\Middleware\Security;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpFoundation\Response;

/**
 * Dev/test domenini (dev.example.az) kənar gözdən və axtarış sistemindən gizlədir.
 *
 * Niyə: dev nüsxə canlı ilə eyni məzmunu daşıyır. İndekslənsə axtarışda canlı
 * ilə rəqabət aparır (dublikat məzmun), açıq qalsa yarımçıq funksiya və test
 * datası görünür. nginx-də basic auth hər layihədə ayrıca qurulur və tez-tez
 * unudulur - burada bir env dəyişəni kifayətdir.
 *
 * QLOBAL middleware-dir (Kernel::$middleware): yönləndirmə qaydası və ya 404
 * olsa da kənar adam dev domenin heç bir cavabını görməməlidir.
 * Default `DEV_GUARD_ENABLED=false` → heç nə etmir; açıq olanda da yalnız
 * `DEV_GUARD_DOMAINS`-dəki host-lara təsir edir.
 *
 * Qorunan host-da:
 *  - `robots.txt` HƏMİŞƏ «Disallow: /» (icazəli IP üçün də);
 *  - hər cavaba `X-Robots-Tag: noindex, nofollow, noarchive`;
 *  - icazəli IP / gizli açar cookie-si → sayt adi işləyir;
 *  - qalanları → `redirect_to` (canlı sayt) və ya 403.
 *
 * Gizli açar: `?dev_key=<açar>` → 30 günlük cookie (dinamik IP-li tərtibatçı
 * hər dəfə allowlist-ə düşməsin), `?dev_key=off` → çıxış. Cookie-də açarın
 * özü yox, HMAC-ı saxlanılır.
 *
 * Mənbə: qrgate `RestrictDevDomain` - config `custom.security.dev_guard.*`
 * altına toplanıb, hardcode domen çıxarılıb, CF başlığı ümumi `trust_cf_header`-dən.
 */
final class RestrictDevDomain
{
    /** Gizli açarla açılan girişin cookie adı (EncryptCookies::$except-dədir). */
    public const UNLOCK_COOKIE = 'dev_guard_access';

    public function handle(Request $request, Closure $next): Response
    {
        if (!$this->isGuardedHost($request)) {
            return $next($request);
        }

        $unlock = $this->resolveUnlockAction($request);

        if ($unlock === 'off') {
            return $this->forgetUnlockCookie($this->redirectAway($request));
        }

        if ($request->is('robots.txt')) {
            return $this->noIndex(
                response("User-agent: *\nDisallow: /\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8'])
            );
        }

        if ($unlock === 'on') {
            return $this->rememberUnlockCookie($request, $this->noIndex($next($request)));
        }

        if ($this->isAllowed($request)) {
            return $this->noIndex($next($request));
        }

        return $this->redirectAway($request);
    }

    private function isGuardedHost(Request $request): bool
    {
        if (!config('custom.security.dev_guard.enabled', false)) {
            return false;
        }

        $domains = array_map('strtolower', RestrictByIp::list(config('custom.security.dev_guard.domains', '')));

        return in_array(strtolower($request->getHost()), $domains, true);
    }

    private function isAllowed(Request $request): bool
    {
        if ($this->hasValidUnlockCookie($request)) {
            return true;
        }

        $allowed = RestrictByIp::list(config('custom.security.dev_guard.allowed_ips', ''));

        return $allowed !== [] && IpUtils::checkIp(RestrictByIp::clientIp($request), $allowed);
    }

    private function redirectAway(Request $request): Response
    {
        $base = rtrim((string) config('custom.security.dev_guard.redirect_to', ''), '/');

        if ($base === '') {
            return $this->noIndex(response('Giriş qadağandır.', 403));
        }

        $target = $base;

        if (config('custom.security.dev_guard.keep_path', true)) {
            $path = ltrim($request->getRequestUri(), '/');
            $target = $path === '' ? $base : $base . '/' . $path;
        }

        // Özünə yönləndirmə sonsuz dövrə yaradır - konfiqurasiya səhvində sayt dayanmasın
        if (strtolower((string) parse_url($target, PHP_URL_HOST)) === strtolower($request->getHost())) {
            return $this->noIndex(response('Giriş qadağandır.', 403));
        }

        $status = (int) config('custom.security.dev_guard.redirect_status', 302);

        return $this->noIndex(redirect()->away($target, in_array($status, [301, 302, 307, 308], true) ? $status : 302));
    }

    private function noIndex(Response $response): Response
    {
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow, noarchive');

        return $response;
    }

    /** `?dev_key=` parametrinin nəticəsi: 'on' | 'off' | null. */
    private function resolveUnlockAction(Request $request): ?string
    {
        $key = (string) config('custom.security.dev_guard.unlock_key', '');
        $given = $request->query('dev_key');

        if ($key === '' || !is_string($given)) {
            return null;
        }

        if ($given === 'off') {
            return 'off';
        }

        return hash_equals($key, $given) ? 'on' : null;
    }

    private function hasValidUnlockCookie(Request $request): bool
    {
        $key = (string) config('custom.security.dev_guard.unlock_key', '');

        if ($key === '') {
            return false;
        }

        // Qlobal middleware `EncryptCookies`-dən ƏVVƏL işləyir - dəyər xam oxunur
        $cookie = (string) $request->cookies->get(self::UNLOCK_COOKIE, '');

        return $cookie !== '' && hash_equals($this->unlockCookieValue($key), $cookie);
    }

    private function rememberUnlockCookie(Request $request, Response $response): Response
    {
        $key = (string) config('custom.security.dev_guard.unlock_key', '');

        $response->headers->setCookie(
            Cookie::create(self::UNLOCK_COOKIE, $this->unlockCookieValue($key))
                ->withExpires(now()->addDays(30)->getTimestamp())
                ->withPath('/')
                ->withSecure($request->isSecure())
                ->withHttpOnly(true)
                ->withSameSite('lax')
        );

        return $response;
    }

    private function forgetUnlockCookie(Response $response): Response
    {
        $response->headers->clearCookie(self::UNLOCK_COOKIE, '/');

        return $response;
    }

    private function unlockCookieValue(string $key): string
    {
        return hash_hmac('sha256', self::UNLOCK_COOKIE, $key);
    }
}
