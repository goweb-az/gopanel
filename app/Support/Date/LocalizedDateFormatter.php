<?php

declare(strict_types=1);

namespace App\Support\Date;

use Carbon\Carbon;
use DateTimeInterface;
use Throwable;

/**
 * Tarixin istifadəçiyə göstərilən, dilə görə mətni - TƏK formatlama nüvəsi.
 *
 * Dil: açıq verilməyibsə sorğunun app locale-i (`app()->getLocale()`; saytda `language`
 * middleware-i, API-də `Accept-Language` qurur). Carbon həmin dili tanımırsa
 * `app.fallback_locale`. Carbon-un QLOBAL locale-i dəyişdirilmir - yalnız nüsxə
 * səviyyəsində `->locale()`. Niyə: `Carbon::setLocale()` bütün prosesə təsir edir, növbə
 * worker-ində bir işin dili növbəti işə «sızır».
 *
 *   az: 27 sentyabr 2026, 01:03 · en: 27 September 2026, 01:03 · ru: 27 сентября 2026, 01:03
 *
 * İstifadə: model trait-i FormatsDate və API resource-ları (`*_formatted` sahələri).
 */
final class LocalizedDateFormatter
{
    public const DATE       = 'd F Y';
    public const DATE_TIME  = 'd F Y, H:i';
    public const SHORT_DATE_TIME = 'd.m.Y H:i';

    /** @var array<string, string> istənilən dil => işlədiləcək dil (proses daxilində keş) */
    private static array $supported = [];

    public static function date(DateTimeInterface|string|null $value, ?string $locale = null): ?string
    {
        return self::format($value, self::DATE, $locale);
    }

    public static function dateTime(DateTimeInterface|string|null $value, ?string $locale = null): ?string
    {
        return self::format($value, self::DATE_TIME, $locale);
    }

    public static function format(DateTimeInterface|string|null $value, string $format, ?string $locale = null): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            $date = $value instanceof DateTimeInterface ? Carbon::instance($value) : Carbon::parse($value);
        } catch (Throwable) {
            return null;
        }

        return $date->locale(self::resolveLocale($locale))->translatedFormat($format);
    }

    /** Verilmiş / app locale; Carbon tanımırsa layihənin fallback dili. */
    public static function resolveLocale(?string $locale = null): string
    {
        $locale   = strtolower((string) ($locale ?: app()->getLocale()));
        $fallback = (string) (config('app.fallback_locale') ?: 'az');

        if ($locale === '') {
            return $fallback;
        }

        return self::$supported[$locale] ??= in_array($locale, Carbon::getAvailableLocales(), true)
            ? $locale
            : $fallback;
    }
}
