<?php

declare(strict_types=1);

namespace App\Http\Middleware\Security;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpFoundation\Response;

/**
 * IP allowlist (alias `ip.restrict`) - `config/custom/security.php`-dəki
 * `ip_restriction_enabled` / `allowed_ips` / `restricted_domains` açarlarının
 * TƏTBİQİ. Config uzun müddət mövcud idi, amma heç bir sinif onu oxumurdu.
 *
 * GoPanel route qrupuna bağlanıb (`RouteServiceProvider`) - giriş səhifəsi də
 * daxil olmaqla. Default `IP_RESTRICTION_ENABLED=false` → heç nə etmir.
 *
 * Qayda:
 *  - `restricted_domains` boşdursa → bağlandığı bütün route-lar yoxlanır;
 *    doludursa → yalnız həmin host-lardakı sorğular (məs. canlı panel domeni
 *    yoxlanır, lokal domen yox).
 *  - `allowed_ips` IP və CIDR qəbul edir: "1.2.3.4,10.0.0.0/8".
 *  - Açıq + siyahı BOŞ → hamı bloklanır (fail-closed). Niyə: «açdım, amma
 *    siyahını yazmağı unutdum» halında panelin sükutla açıq qalması
 *    təhlükəsizlik baxımından daha pisdir.
 *
 * qrgate `RestrictByIP`-dən fərqlər: orada yoxlama yalnız `local` mühitdə
 * işləyirdi (canlıda heç vaxt) və `X-Forwarded-For` başlığına kor-koranə
 * inanılırdı - başlığı saxtalaşdıran istənilən kəs allowlist-i keçirdi.
 * Burada IP `$request->ip()`-dir (TrustProxies), Cloudflare başlığı yalnız
 * `trust_cf_header=true` olanda oxunur.
 */
final class RestrictByIp
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!config('custom.security.ip_restriction_enabled', false) || !$this->appliesToHost($request)) {
            return $next($request);
        }

        $allowed = self::list(config('custom.security.allowed_ips', ''));

        if ($allowed === [] || !IpUtils::checkIp(self::clientIp($request), $allowed)) {
            abort(403, 'Bu ünvandan giriş qadağandır.');
        }

        return $next($request);
    }

    private function appliesToHost(Request $request): bool
    {
        $domains = array_map('strtolower', self::list(config('custom.security.restricted_domains', '')));

        return $domains === [] || in_array(strtolower($request->getHost()), $domains, true);
    }

    /**
     * Real ziyarətçi IP-si. `CF-Connecting-IP` yalnız açıq icazə ilə oxunur -
     * server Cloudflare arxasında deyilsə bu başlığı hər kəs göndərə bilər.
     */
    public static function clientIp(Request $request): string
    {
        if (config('custom.security.trust_cf_header', false)) {
            $cf = trim((string) $request->header('CF-Connecting-IP'));

            if ($cf !== '' && filter_var($cf, FILTER_VALIDATE_IP)) {
                return $cf;
            }
        }

        return (string) $request->ip();
    }

    /**
     * Vergüllə yazılmış env dəyərini massivə çevirir (`\r`, `\n` təmizlənir).
     *
     * @return array<int, string>
     */
    public static function list(mixed $raw): array
    {
        if (is_array($raw)) {
            $raw = implode(',', $raw);
        }

        return array_values(array_filter(array_map(
            'trim',
            explode(',', str_replace(["\r", "\n"], '', (string) $raw))
        )));
    }
}
