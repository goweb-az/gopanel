<?php

namespace App\Repositories;

use App\Models\Analytics\AnalyticsAdPlatform;
use App\Models\Analytics\AnalyticsAdPlatformData;
use App\Models\Analytics\AnalyticsBrowser;
use App\Models\Analytics\AnalyticsCity;
use App\Models\Analytics\AnalyticsClick;
use App\Models\Analytics\AnalyticsCountry;
use App\Models\Analytics\AnalyticsDevice;
use App\Models\Analytics\AnalyticsEventLog;
use App\Models\Analytics\AnalyticsLanguage;
use App\Models\Analytics\AnalyticsLink;
use App\Models\Analytics\AnalyticsOperatingSystem;
use App\Models\Analytics\AnalyticsUtmParameter;

class AnalyticsRepository
{
    public function registerLink(array $data): ?AnalyticsLink
    {
        if (empty($data['slug'])) {
            return null;
        }

        $link = AnalyticsLink::firstOrCreate(
            ['slug' => $data['slug']],
            ['locale' => $data['locale'] ?? null, 'url' => $data['url'] ?? null]
        );
        $link->increment('hit_count');

        return $link;
    }

    public function registerDevice(?string $type, ?string $icon): ?AnalyticsDevice
    {
        if (!$type) {
            return null;
        }

        $device = AnalyticsDevice::firstOrCreate(
            ['device_type' => $type],
            ['icon' => $icon]
        );
        $device->increment('hit_count');

        return $device;
    }

    public function registerOperatingSystem(?string $name, ?string $icon): ?AnalyticsOperatingSystem
    {
        if (!$name) {
            return null;
        }

        $os = AnalyticsOperatingSystem::firstOrCreate(
            ['name' => $name],
            ['icon' => $icon]
        );
        if ($icon && $os->icon !== $icon) {
            $os->forceFill(['icon' => $icon])->save();
        }
        $os->increment('hit_count');

        return $os;
    }

    public function registerBrowser(?string $name, ?string $icon): ?AnalyticsBrowser
    {
        if (!$name) {
            return null;
        }

        $browser = AnalyticsBrowser::firstOrCreate(
            ['name' => $name],
            ['icon' => $icon]
        );
        if ($icon && $browser->icon !== $icon) {
            $browser->forceFill(['icon' => $icon])->save();
        }
        $browser->increment('hit_count');

        return $browser;
    }

    public function registerCountry(?string $name, ?string $isoCode, ?string $flagUrl): ?AnalyticsCountry
    {
        if (!$name || $name === 'Unknown') {
            return null;
        }

        $country = AnalyticsCountry::firstOrCreate(
            ['iso_code' => $isoCode ?? 'XX'],
            ['name' => $name, 'flag' => $flagUrl]
        );
        $country->increment('hit_count');

        return $country;
    }

    public function registerCity(?string $name, ?int $countryId): ?AnalyticsCity
    {
        if (!$name || $name === 'Unknown') {
            return null;
        }

        $city = AnalyticsCity::firstOrCreate(
            ['name' => $name, 'country_id' => $countryId],
        );
        $city->increment('hit_count');

        return $city;
    }

    public function registerLanguage(?string $code, ?string $name): ?AnalyticsLanguage
    {
        if (!$code) {
            return null;
        }

        $lang = AnalyticsLanguage::firstOrCreate(
            ['code' => $code],
            ['name' => $name ?? ucfirst($code)]
        );
        $lang->increment('hit_count');

        return $lang;
    }

    public function registerClick(array $data): AnalyticsClick
    {
        return AnalyticsClick::create($data);
    }

    public function registerUtmParameters(array $data): AnalyticsUtmParameter
    {
        return AnalyticsUtmParameter::create($data);
    }

    public function registerAdPlatform(?string $name, ?string $logo): AnalyticsAdPlatform
    {
        $platform = AnalyticsAdPlatform::firstOrCreate(
            ['name' => $name],
            ['logo' => $logo]
        );
        if ($logo && $platform->logo !== $logo) {
            $platform->forceFill(['logo' => $logo])->save();
        }
        $platform->increment('hit_count');

        return $platform;
    }

    public function registerAdPlatformData(array $data): AnalyticsAdPlatformData
    {
        return AnalyticsAdPlatformData::create($data);
    }

    public function registerEventLog(array $data): AnalyticsEventLog
    {
        return AnalyticsEventLog::create($data);
    }
}
