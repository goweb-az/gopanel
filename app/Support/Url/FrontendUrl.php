<?php

declare(strict_types=1);

namespace App\Support\Url;

/**
 * İctimai saytın ünvanı - sayt ayrıca (headless) frontend olduqda.
 *
 * Niyə lazımdır: törəmə layihənin bir qismində Laravel yalnız API + GoPanel
 * verir, sayt isə ayrı domendə Next.js/Nuxt-dur. Bu halda `route()`/`url()`
 * sayt linkini API domeninə qurur («Sayta keç», email-dəki «Sifarişə bax»
 * linki 404 verir). Ünvan `FRONTEND_URL` ilə açıq yazılır; yazılmayıbsa
 * `APP_URL`-dən çıxarılır: host `api.` ilə başlayırsa prefiks atılır -
 * `https://api.site.az` → `https://site.az`.
 *
 * Prefiks yoxdursa (adi monolit starter) ayrıca sayt **yoxdur** - `target()`
 * `null` qaytarır, `base()`/`to()` isə `APP_URL`-ə düşür.
 *
 * Panel linki üçün `GopanelUrl`, fayl üçün `CdnUrl` işlədilir.
 */
class FrontendUrl
{
    /**
     * Ayrıca saytın ünvanı - «Sayta keç» düyməsi üçün.
     *
     * @return string|null  null = ayrıca sayt domeni yoxdur
     */
    public static function target(): ?string
    {
        $configured = trim((string) config('app.frontend_url'));

        if ($configured !== '') {
            return rtrim($configured, '/');
        }

        return static::stripApiSubdomain((string) config('app.url'));
    }

    /** Ayrıca sayt domeni varmı. */
    public static function isConfigured(): bool
    {
        return static::target() !== null;
    }

    /**
     * Public ünvan kökü. Sayt domeni yoxdursa `APP_URL` - sitemap/RSS boş qalmasın.
     */
    public static function base(): string
    {
        return static::target() ?? rtrim((string) config('app.url'), '/');
    }

    /** Saytın daxili ünvanı: `FrontendUrl::to('az/bloq')`. */
    public static function to(?string $path = null): string
    {
        $path = trim((string) $path, '/');

        return $path === '' ? static::base() : static::base() . '/' . $path;
    }

    /**
     * `https://api.site.az` → `https://site.az`.
     * `api.` prefiksi yoxdursa null - çıxarılacaq sayt domeni yoxdur.
     */
    private static function stripApiSubdomain(string $appUrl): ?string
    {
        $appUrl = rtrim($appUrl, '/');

        if ($appUrl === '') {
            return null;
        }

        $host = parse_url($appUrl, PHP_URL_HOST);

        if (! is_string($host) || ! str_starts_with($host, 'api.')) {
            return null;
        }

        $scheme = parse_url($appUrl, PHP_URL_SCHEME) ?: 'https';
        $port = parse_url($appUrl, PHP_URL_PORT);

        return $scheme . '://' . substr($host, 4) . ($port ? ':' . $port : '');
    }
}
