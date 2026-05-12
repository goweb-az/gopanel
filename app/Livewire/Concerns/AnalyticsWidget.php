<?php

namespace App\Livewire\Concerns;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Url;

/**
 * Shared plumbing for analytics dashboard widgets.
 *
 * Each widget consumes the same set of URL-bound filters (from, to,
 * country_id, city_id, browser_id, device_id) and exposes a tiny remember()
 * helper that namespaces a cache key on top of those filters. Concrete
 * widgets only need to implement their query logic and call remember().
 */
trait AnalyticsWidget
{
    #[Url(as: 'from')]
    public string $dateFrom = '';

    #[Url(as: 'to')]
    public string $dateTo = '';

    #[Url(as: 'country_id')]
    public ?int $countryId = null;

    #[Url(as: 'city_id')]
    public ?int $cityId = null;

    #[Url(as: 'browser_id')]
    public ?int $browserId = null;

    #[Url(as: 'device_id')]
    public ?int $deviceId = null;

    public function mount(): void
    {
        if ($this->dateFrom === '') {
            $this->dateFrom = Carbon::now()->subDays(6)->format('Y-m-d');
        }
        if ($this->dateTo === '') {
            $this->dateTo = Carbon::now()->format('Y-m-d');
        }
    }

    /**
     * [from, to] Carbon range bounded by the current filters.
     */
    protected function range(): array
    {
        return [
            Carbon::parse($this->dateFrom)->startOfDay(),
            Carbon::parse($this->dateTo)->endOfDay(),
        ];
    }

    /**
     * Cache the result of $callback under a key that is namespaced by
     * widget class + every filter the widget reacts to. TTL defaults to 5
     * minutes — analytics dashboards tolerate slight staleness.
     */
    protected function remember(string $bucket, \Closure $callback, int $ttl = 300): mixed
    {
        $key = sprintf(
            'analytics:%s:%s:%s|%s|%s|%s|%s|%s',
            static::class,
            $bucket,
            $this->dateFrom,
            $this->dateTo,
            $this->countryId ?? '',
            $this->cityId ?? '',
            $this->browserId ?? '',
            $this->deviceId ?? '',
        );

        return Cache::remember($key, $ttl, $callback);
    }
}
