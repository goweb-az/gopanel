<?php

declare(strict_types=1);

namespace App\Support\Geo;

/**
 * Email/bildiriş şablonlarındakı statik xəritə şəkli və «xəritədə aç» linki - vahid mənbə.
 *
 * Niyə: URL bir neçə yerdə (bildiriş, email, panel kartı) ayrıca yığılanda
 * hamısı fərqli provayderə gedir, provayder dəyişəndə hər yeri axtarmaq
 * lazım gəlir. Burada provayder `config/custom/map.php`-dən bir dəfə seçilir.
 *
 * Koordinat yoxlaması da buradadır: `0,0` (doldurulmamış sahə) və sərhəddən
 * kənar dəyər «yer yoxdur» sayılır - Atlantik okeanının ortasında pin görünmür.
 *
 *   StaticMap::payload($lat, $lng);   // şablona hazır dəst
 */
final class StaticMap
{
    public const DEFAULT_ZOOM   = 16;
    public const DEFAULT_WIDTH  = 600;
    public const DEFAULT_HEIGHT = 300;

    /** Koordinat üçün statik xəritə şəkli. Koordinat və ya provayder yoxdursa null. */
    public static function image(
        mixed $lat,
        mixed $lng,
        int $zoom = self::DEFAULT_ZOOM,
        int $width = self::DEFAULT_WIDTH,
        int $height = self::DEFAULT_HEIGHT,
    ): ?string {
        $point = self::point($lat, $lng);

        if ($point === null) {
            return null;
        }

        [$latitude, $longitude] = $point;

        return match (self::provider()) {
            'google'   => self::google($latitude, $longitude, $zoom, $width, $height),
            'geoapify' => self::geoapify($latitude, $longitude, $zoom, $width, $height),
            default    => null,
        };
    }

    /** Xəritədə açmaq üçün keçid - həmişə Google Maps (istifadəçiyə tanış, açarsız). */
    public static function link(mixed $lat, mixed $lng): ?string
    {
        $point = self::point($lat, $lng);

        return $point === null ? null : sprintf('https://www.google.com/maps?q=%s,%s', $point[0], $point[1]);
    }

    /** Koordinatın mətn görünüşü: "40.377600, 49.891900". */
    public static function text(mixed $lat, mixed $lng): ?string
    {
        $point = self::point($lat, $lng);

        return $point === null ? null : sprintf('%.6f, %.6f', $point[0], $point[1]);
    }

    /**
     * Şablonlara verilən hazır dəst: şəkil + keçid + mətn.
     *
     * @return array{has_location: bool, latitude: ?float, longitude: ?float, map_image: ?string, map_link: ?string, location: ?string}
     */
    public static function payload(mixed $lat, mixed $lng): array
    {
        $point = self::point($lat, $lng);

        if ($point === null) {
            return [
                'has_location' => false,
                'latitude'     => null,
                'longitude'    => null,
                'map_image'    => null,
                'map_link'     => null,
                'location'     => null,
            ];
        }

        return [
            'has_location' => true,
            'latitude'     => $point[0],
            'longitude'    => $point[1],
            'map_image'    => self::image($point[0], $point[1]),
            'map_link'     => self::link($point[0], $point[1]),
            'location'     => self::text($point[0], $point[1]),
        ];
    }

    /**
     * Aktiv provayder. Açar tələb edən provayder açarsız qalıbsa `none` -
     * şəkil «invalid key» xətası əvəzinə sadəcə göstərilmir.
     */
    public static function provider(): string
    {
        $provider = strtolower((string) config('custom.map.static_map.provider', 'none'));
        $key = (string) config('custom.map.static_map.key', '');

        if (! in_array($provider, ['google', 'geoapify'], true) || $key === '') {
            return 'none';
        }

        return $provider;
    }

    private static function geoapify(float $lat, float $lng, int $zoom, int $width, int $height): string
    {
        return 'https://maps.geoapify.com/v1/staticmap?' . http_build_query([
            'style'  => 'osm-bright',
            'width'  => $width,
            'height' => $height,
            'center' => "lonlat:{$lng},{$lat}",
            'zoom'   => $zoom,
            'marker' => "lonlat:{$lng},{$lat};type:material;color:#e11d48;size:large",
            'apiKey' => (string) config('custom.map.static_map.key'),
        ]);
    }

    private static function google(float $lat, float $lng, int $zoom, int $width, int $height): string
    {
        return 'https://maps.googleapis.com/maps/api/staticmap?' . http_build_query([
            'center'  => "{$lat},{$lng}",
            'zoom'    => $zoom,
            'size'    => "{$width}x{$height}",
            'scale'   => 2,
            'maptype' => 'roadmap',
            'markers' => "color:red|{$lat},{$lng}",
            'key'     => (string) config('custom.map.static_map.key'),
        ]);
    }

    /**
     * Koordinatı yoxlayıb float cütünə çevirir.
     *
     * @return array{0: float, 1: float}|null
     */
    private static function point(mixed $lat, mixed $lng): ?array
    {
        if (! is_numeric($lat) || ! is_numeric($lng)) {
            return null;
        }

        $latitude = (float) $lat;
        $longitude = (float) $lng;

        if ($latitude === 0.0 && $longitude === 0.0) {
            return null;
        }

        if ($latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
            return null;
        }

        return [$latitude, $longitude];
    }
}
