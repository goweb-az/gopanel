<?php

namespace App\Traits\Presentable\Analytics;

/**
 * `AnalyticsClick` modelinin siyahı hüceyrələri (link, ölçü üzrə süzgəc linkləri).
 *
 * Niyə: klik siyahısında hər ölçü (cihaz, ölkə, brauzer...) həmin ölçü ilə
 * süzülmüş siyahıya link olur - bu HTML bir yerdə yığılsın deyə. Adlar
 * DƏYİŞMƏYİB - analitika detal datatable-ı işlədir.
 *
 * Əlaqə adları və IP ziyarətçidən (User-Agent, GeoIP) gəlir - ona görə
 * hamısı `e()` ilə qaçırılır. Əlaqələr siyahıda `with()` ilə yüklənməlidir.
 */
trait HasAnalyticsClickPresentation
{
    public function getUrlLinkAttribute()
    {
        return $this->shortExternalLink($this->url);
    }

    public function getRefererLinkAttribute()
    {
        return $this->shortExternalLink($this->referer);
    }

    public function getDeviceLinkAttribute()
    {
        return $this->dimensionLink(['device_id' => $this->device_id], $this?->device?->device_type);
    }

    public function getOperatingLinkAttribute()
    {
        return $this->dimensionLink(['os_id' => $this->os_id], $this?->operatingSystem?->name);
    }

    public function getBrowserLinkAttribute()
    {
        return $this->dimensionLink(['browser_id' => $this->browser_id], $this?->browser?->name);
    }

    public function getCountryLinkAttribute()
    {
        return $this->dimensionLink(['country_id' => $this->country_id], $this?->country?->name);
    }

    public function getCityLinkAttribute()
    {
        return $this->dimensionLink(['city_id' => $this->city_id], $this?->city?->name);
    }

    public function getLanguageLinkAttribute()
    {
        return $this->dimensionLink(['language_id' => $this->language_id], $this?->language?->name);
    }

    public function getIpAddressClickAttribute()
    {
        return '<span class="toSearch">' . e($this?->ip_address) . '</span>';
    }

    /** Uzun URL 35 simvola qısaldılır, tam ünvan `href`-də qalır. */
    private function shortExternalLink(?string $url): ?string
    {
        if (empty($url)) {
            return null;
        }

        $max = 35;

        $short = strlen($url) > $max
            ? substr($url, 0, $max) . '...'
            : $url;

        return '<a href="' . e($url) . '" target="_blank" rel="noopener noreferrer">' . e($short) . '</a>';
    }

    private function dimensionLink(array $filter, ?string $text): string
    {
        return '<a href="' . route("gopanel.analytics.detail.clicks", $filter) . '">' . e($text) . '</a>';
    }
}
