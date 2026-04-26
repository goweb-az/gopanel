<?php

namespace App\Helpers\Analytics;

use App\Models\Geography\Language;

class TrackingHelper
{

    public static function normalizeLanguageFull(?string $acceptLang): string
    {
        if (!$acceptLang) return 'en';
        $first = explode(',', $acceptLang)[0];
        return trim(explode(';', $first)[0]) ?: 'en';
    }

    public static function parseLanguageCode(string $full): string
    {
        return strtolower(substr($full, 0, 2)) ?: 'en';
    }

    public static function languageNameFromCode(string $code): string
    {
        $language = Language::where('code', $code)->first();
        return $language?->name ?? ucfirst($code);
    }

    public static function clamp(?string $value, int $max): ?string
    {
        if ($value === null) return null;
        return mb_substr($value, 0, $max);
    }

    public static function normalizeLanguage(?string $acceptLang): array
    {
        // full: IETF etiketi, code: 2 harf
        $full = self::normalizeLanguageFull($acceptLang);
        $code = self::parseLanguageCode($full);
        return ['full' => $full, 'code' => $code];
    }

    /**
     * $platformMap: ['gclid' => 'Google Ads', 'fbclid' => 'Facebook Ads', ...]
     * $logoMap:     ['Google Ads' => '...', 'Facebook Ads' => '...', ...]
     */
    public static function resolveAdPlatform(array $query, array $platformMap, array $logoMap): ?array
    {
        foreach ($platformMap as $param => $platformName) {
            if (!empty($query[$param])) {
                return [
                    'ad_platform'   => $platformName,
                    'platform_logo' => $logoMap[$platformName] ?? null,
                    'platform_data' => [$param => $query[$param]],
                ];
            }
        }
        return null;
    }
}
