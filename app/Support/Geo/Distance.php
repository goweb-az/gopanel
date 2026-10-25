<?php

declare(strict_types=1);

namespace App\Support\Geo;

/**
 * İki koordinat arasındakı məsafə (Haversine).
 *
 * Niyə ortaq yerdə: törəmə layihədə bu hesablama bir servisin `private`
 * metodunda idi, bildiriş mətni isə («ərazidən 270 m kənarda») eyni rəqəmi
 * başqa yerdə yenidən hesablamalı oldu. Düstur iki nüsxədə saxlananda nəticə
 * fərqlənir. «Ən yaxın filial», «çatdırılma radiusu» kimi hallar üçün də budur.
 *
 * Böyük cədvəldə «radius daxilində» axtarışı üçün PHP-də dövr etmək olmaz -
 * SQL-də bounding-box ilə süzülür, bu sinif yalnız son dəqiqləşdirmə üçündür.
 */
final class Distance
{
    /** Yer kürəsinin radiusu - metrlərlə. */
    private const EARTH_RADIUS_M = 6371000;

    /**
     * Metrlə məsafə.
     */
    public static function meters(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) * sin($dLat / 2)
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2))
            * sin($dLng / 2) * sin($dLng / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return self::EARTH_RADIUS_M * $c;
    }

    /**
     * Koordinatlardan biri yoxdursa `null` - çağıran yerdə `if` yığılmasın.
     */
    public static function between(?float $lat1, ?float $lng1, ?float $lat2, ?float $lng2): ?float
    {
        if ($lat1 === null || $lng1 === null || $lat2 === null || $lng2 === null) {
            return null;
        }

        return self::meters($lat1, $lng1, $lat2, $lng2);
    }

    /**
     * Bildiriş mətni üçün görünüş: 320.4 → "320 m".
     *
     * Həmişə metrlə yazılır (km-ə keçilmir) - radius kimi müqayisə olunan
     * dəyərlər metrlə saxlanılır.
     */
    public static function label(mixed $meters, string $empty = ''): string
    {
        if ($meters === null || $meters === '' || !is_numeric($meters)) {
            return $empty;
        }

        $value = (int) round((float) $meters);

        return $value <= 0 ? $empty : $value . ' m';
    }
}
