<?php

namespace App\Services\Site\Seo;

use App\Helpers\Analytics\GeoIpHelper;
use App\Helpers\Analytics\TrackingHelper;
use App\Repositories\AnalyticsRepository;
use App\Models\Analytics\AnalyticsClick;
use App\Models\Geography\Language;

class AnalyticsService
{
    protected AnalyticsRepository $repository;

    public ?array $config;

    public function __construct(AnalyticsRepository $repository)
    {
        $this->repository = $repository;
        $this->config = config("seo.analytics");
    }

    public function register(array $data): AnalyticsClick
    {
        $ip         = $data['ip_address']      ?? request()->ip();
        $userAgent  = $data['user_agent']      ?? request()->userAgent();
        $acceptLang = $data['accept_language'] ?? request()->header('Accept-Language');
        $referer    = TrackingHelper::clamp($data['referer'] ?? request()->headers->get('referer'), 2048);
        $url        = TrackingHelper::clamp($data['url']     ?? request()->fullUrl(), 2048);
        $rawUrl     = TrackingHelper::clamp($data['url'] ?? request()->fullUrl(), 2048);

        [$cleanUrl, $cleanPath] = $this->sanitizeUrlAndPath($rawUrl);
        $languageFull           = TrackingHelper::normalizeLanguageFull($acceptLang);
        $languageCode           = TrackingHelper::parseLanguageCode($languageFull);

        $countryName = $this->geoipCountry($ip);
        $countryFlag = $this->geoipCountryFlag($ip);
        $countryIso  = $this->geoipCountryCode($ip);
        $cityName    = $this->geoipCity($ip);

        $deviceData = $this->uaDeviceType($userAgent);
        $osData      = $this->uaOs($userAgent);
        $browserData = $this->uaBrowser($userAgent);

        $link    = $this->repository->registerLink(['locale' => $languageCode, 'slug' => $cleanPath, 'url' => $cleanUrl]);
        $device  = $this->repository->registerDevice($deviceData['type'], $deviceData['icon']);
        $os      = $this->repository->registerOperatingSystem($osData['name'] ?? null, $osData['logo'] ?? null);
        $browser = $this->repository->registerBrowser($browserData['name'], $browserData['logo'] ?? null);
        $country = $this->repository->registerCountry($countryName, $countryIso, $countryFlag);
        $city    = $this->repository->registerCity($cityName, $country?->id);
        $lang    = $this->repository->registerLanguage($languageCode, TrackingHelper::languageNameFromCode($languageCode));

        $click = $this->repository->registerClick([
            'link_id'     => $link?->id,
            'device_id'   => $device?->id,
            'os_id'       => $os?->id,
            'browser_id'  => $browser?->id,
            'country_id'  => $country?->id,
            'city_id'     => $city?->id,
            'language_id' => $lang?->id,
            'ip_address'  => $ip,
            'latitude'    => $data['latitude'] ?? null,
            'longitude'   => $data['longitude'] ?? null,
            'isp'         => $data['isp'] ?? null,
            'url'         => $url,
            'referer'     => $referer,
        ]);

        $this->persistUtm($click, $data);
        $this->persistAdPlatforms($click, $data);
        $this->persistEvents($click, $data);

        return $click;
    }

    private function sanitizeUrlAndPath(string $url): array
    {
        $cleanUrl = strtok($url, '#');
        $cleanUrl = strtok($cleanUrl, '?') ?: $cleanUrl;

        $parsed   = parse_url($url);
        $path     = $parsed['path'] ?? '/';

        $path = preg_replace('#/{2,}#', '/', $path) ?: '/';

        if ($path === '') {
            $path = '/';
        }

        return [$cleanUrl, $path];
    }


    protected function persistUtm(AnalyticsClick $click, array $data): void
    {
        if (
            ($data['utm_source'] ?? null) ||
            ($data['utm_medium'] ?? null) ||
            ($data['utm_campaign'] ?? null) ||
            ($data['utm_term'] ?? null) ||
            ($data['utm_content'] ?? null)
        ) {
            $this->repository->registerUtmParameters([
                'click_id'     => $click->id,
                'utm_source'   => $data['utm_source']   ?? null,
                'utm_medium'   => $data['utm_medium']   ?? null,
                'utm_campaign' => $data['utm_campaign'] ?? null,
                'utm_term'     => $data['utm_term']     ?? null,
                'utm_content'  => $data['utm_content']  ?? null,
            ]);
        }
    }

    protected function persistAdPlatforms(AnalyticsClick $click, array $data): void
    {

        $map = (array) ($this->config['ad_platforms'] ?? []);
        $grouped = [];

        foreach ($map as $param => $platformName) {
            if (!empty($data[$param])) {
                $grouped[$platformName][$param] = $data[$param];
            }
        }

        if (!empty($data['ad_platform']) && !empty($data['platform_data']) && is_array($data['platform_data'])) {
            $pName = $data['ad_platform'];
            $grouped[$pName] = array_merge($grouped[$pName] ?? [], $data['platform_data']);
        }
        if (empty($grouped))
            return;

        foreach ($grouped as $platformName => $pairs) {
            if (empty($pairs) || !is_array($pairs)) {
                continue;
            }
            $platform = $this->repository->registerAdPlatform(
                $platformName,
                $this->config['ad_logos'][$platformName] ?? null
            );
            foreach ($pairs as $paramKey => $paramValue) {
                if ($paramKey === null && $paramValue === null) {
                    continue;
                }

                $this->repository->registerAdPlatformData([
                    'click_id'    => $click->id,
                    'platform_id' => $platform->id,
                    'param_key'   => (string) $paramKey,
                    'param_value' => ($paramValue !== null) ? (string) $paramValue : null,
                ]);
            }
        }
    }

    protected function persistEvents(AnalyticsClick $click, array $data): void
    {
        if (!empty($data['events']) && is_array($data['events'])) {
            foreach ($data['events'] as $event) {
                $this->repository->registerEventLog([
                    'click_id'    => $click->id,
                    'event_type'  => $event['type']  ?? null,
                    'event_value' => $event['value'] ?? null,
                ]);
            }
        }
    }

    protected function geoipCountry(?string $ip): string
    {
        $geo = GeoIpHelper::geoipCountry($ip);
        return $geo['country'] ?? 'Unknown';
    }

    protected function geoipCountryCode(?string $ip): string
    {
        $geo = GeoIpHelper::geoipCountry($ip);
        return $geo['code'] ?? 'Unknown';
    }

    protected function geoipCountryFlag(?string $ip): string
    {
        $geo = GeoIpHelper::geoipCountry($ip);
        return $geo['flag_url'] ?? null;
    }

    protected function geoipCity(?string $ip): string
    {
        $geo = GeoIpHelper::geoipCity($ip);
        return $geo['city'] ?? 'Unknown';
    }

    protected function uaDeviceType(?string $ua): array
    {
        if (!$ua) {
            return ['type' => 'Desktop', 'icon' => $this->config['devices']['Desktop']['icon'] ?? null];
        }

        foreach ($this->config['devices'] as $device => $meta) {
            foreach ($meta['keywords'] as $keyword) {
                if ($keyword !== '' && stripos($ua, $keyword) !== false) {
                    return [
                        'type' => $device,
                        'icon' => $meta['icon'] ?? null,
                    ];
                }
            }
        }

        return ['type' => 'Unknown', 'icon' => null];
    }



    protected function uaOs(?string $ua): array
    {
        if (!$ua) {
            return ['name' => 'Unknown', 'logo' => null];
        }

        foreach (($this->config['oses'] ?? []) as $os => $meta) {
            foreach ($meta['keywords'] as $keyword) {
                if ($keyword !== '' && stripos($ua, $keyword) !== false) {
                    return ['name' => $os, 'logo' => $meta['logo'] ?? null];
                }
            }
        }

        return ['name' => 'Other', 'logo' => null];
    }


    protected function uaBrowser(?string $ua): array
    {
        if (!$ua) {
            return ['name' => 'Unknown', 'logo' => null];
        }

        foreach (($this->config['browsers'] ?? []) as $browser => $meta) {
            foreach ($meta['keywords'] as $keyword) {
                if ($keyword !== '' && stripos($ua, $keyword) !== false) {
                    return ['name' => $browser, 'logo' => $meta['logo'] ?? null];
                }
            }
        }

        return ['name' => 'Other', 'logo' => null];
    }
}
