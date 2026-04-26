<?php

namespace App\Helpers\Analytics;

use GeoIp2\Database\Reader;
use Illuminate\Support\Facades\Log;

class GeoIpHelper
{
    /** @var array<string,Reader> */
    protected static array $readers = [];

    protected static function isPrivateIp(?string $ip): bool
    {
        if (!$ip) return true;
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false;
    }

    protected static function reader(string $key): ?Reader
    {
        if (isset(self::$readers[$key])) {
            return self::$readers[$key];
        }
        $path = config("seo.analytics.geoip.paths.$key");
        if (!$path || !is_file($path)) {
            Log::warning("GeoIP DB not found for key [$key] at path: " . ($path ?: 'null'));
            return null;
        }
        try {
            return self::$readers[$key] = new Reader($path);
        } catch (\Throwable $e) {
            Log::error("GeoIP Reader init failed for [$key]: " . $e->getMessage());
            return null;
        }
    }

    public static function geoipCity(?string $ip): array
    {
        if (self::isPrivateIp($ip)) return self::unknownCity();

        try {
            $reader = self::reader('city');
            if ($reader) {
                $r = $reader->city($ip);
                $iso = $r->country->isoCode ?? null;

                return [
                    'country'    => $r->country->name ?? 'Unknown',
                    'code'       => $iso ?? 'Unknown',
                    'city'       => $r->city->name ?? 'Unknown',
                    'lat'        => $r->location->latitude ?? null,
                    'lon'        => $r->location->longitude ?? null,
                    'flag_emoji' => self::countryFlagEmoji($iso),
                    'flag_url'   => self::countryFlagUrl($iso),
                ];
            }
        } catch (\Throwable $e) {
            Log::error("GeoIP City lookup failed ({$ip}): " . $e->getMessage());
        }
        return self::unknownCity();
    }

    public static function geoipCountry(?string $ip): array
    {
        if (self::isPrivateIp($ip)) return self::unknownCountry();

        try {
            $reader = self::reader('country');
            if ($reader) {
                $r = $reader->country($ip);
                $iso = $r->country->isoCode ?? null;

                return [
                    'country'    => $r->country->name ?? 'Unknown',
                    'code'       => $iso ?? 'Unknown',
                    'flag_emoji' => self::countryFlagEmoji($iso),
                    'flag_url'   => self::countryFlagUrl($iso),
                ];
            }
        } catch (\Throwable $e) {
            Log::error("GeoIP Country lookup failed ({$ip}): " . $e->getMessage());
        }
        return self::unknownCountry();
    }

    public static function geoipAsn(?string $ip): array
    {
        if (self::isPrivateIp($ip)) return self::unknownAsn();

        try {
            $reader = self::reader('asn');
            if ($reader) {
                $r = $reader->asn($ip);

                return [
                    'asn' => $r->autonomousSystemNumber ?? null,
                    'isp' => $r->autonomousSystemOrganization ?? 'Unknown',
                ];
            }
        } catch (\Throwable $e) {
            Log::error("GeoIP ASN lookup failed ({$ip}): " . $e->getMessage());
        }
        return self::unknownAsn();
    }

    /** Helpers */

    protected static function countryFlagEmoji(?string $isoCode): string
    {
        if (!$isoCode || strlen($isoCode) !== 2) {
            return '🏳';
        }
        $iso = strtoupper($isoCode);
        $offset = 127397; // regional indicator base
        // mb_chr is available in PHP 7.2+ with mbstring
        return mb_chr(ord($iso[0]) + $offset, 'UTF-8')
            . mb_chr(ord($iso[1]) + $offset, 'UTF-8');
    }

    protected static function countryFlagUrl(?string $isoCode): ?string
    {
        if (!$isoCode || strlen($isoCode) !== 2) {
            return null;
        }
        $code = strtolower($isoCode);
        // 20px height PNG (FlagCDN). Boyutu ihtiyaca göre h40, h80 vb. değiştirilebilir.
        return "https://flagcdn.com/h20/{$code}.png";
    }

    protected static function unknownCity(): array
    {
        return [
            'country'    => 'Unknown',
            'code'       => 'Unknown',
            'city'       => 'Unknown',
            'lat'        => null,
            'lon'        => null,
            'flag_emoji' => '🏳',
            'flag_url'   => null,
        ];
    }

    protected static function unknownCountry(): array
    {
        return [
            'country'    => 'Unknown',
            'code'       => 'Unknown',
            'flag_emoji' => '🏳',
            'flag_url'   => null,
        ];
    }

    protected static function unknownAsn(): array
    {
        return [
            'asn' => null,
            'isp' => 'Unknown',
        ];
    }
}
