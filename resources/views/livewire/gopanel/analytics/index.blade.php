<?php

use App\Models\Analytics\AnalyticsAdPlatform;
use App\Models\Analytics\AnalyticsBrowser;
use App\Models\Analytics\AnalyticsClick;
use App\Models\Analytics\AnalyticsCountry;
use App\Models\Analytics\AnalyticsDevice;
use App\Models\Analytics\AnalyticsLanguage;
use App\Models\Analytics\AnalyticsLink;
use App\Models\Analytics\AnalyticsOperatingSystem;
use App\Models\Analytics\AnalyticsUtmParameter;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Component;

new
#[Layout('gopanel.layouts.main')]
class extends Component {
    public array $viewData = [];

    public function mount(): void
    {
        $request = request();

        $to = $request->filled('to')
            ? Carbon::parse($request->input('to'))->endOfDay()
            : Carbon::now()->endOfDay();

        $from = $request->filled('from')
            ? Carbon::parse($request->input('from'))->startOfDay()
            : Carbon::now()->subDays(6)->startOfDay();

        $clicksQuery = AnalyticsClick::whereBetween('created_at', [$from, $to]);

        $devices  = AnalyticsDevice::orderByDesc('hit_count')->limit(10)->get();
        $browsers = AnalyticsBrowser::orderByDesc('hit_count')->limit(10)->get();

        $this->viewData = [
            'countriesCount'    => (clone $clicksQuery)->distinct('country_id')->count('country_id'),
            'citiesCount'       => (clone $clicksQuery)->distinct('city_id')->count('city_id'),
            'languagesCount'    => AnalyticsLanguage::count(),
            'operatingsCount'   => AnalyticsOperatingSystem::count(),
            'devices'           => $devices,
            'browsers'          => $browsers,
            'adPlatforms'       => AnalyticsAdPlatform::orderByDesc('hit_count')->limit(10)->get(),
            'utms'              => AnalyticsUtmParameter::with('click.link')->latest()->limit(10)->get(),
            'utmsCount'         => AnalyticsUtmParameter::count(),
            'anayticsLanguages' => AnalyticsLanguage::orderByDesc('hit_count')->limit(10)->get(),
            'deviceLabels'      => $devices->pluck('device_type'),
            'deviceHits'        => $devices->pluck('hit_count'),
            'browserLabels'     => $browsers->pluck('name'),
            'browserHits'       => $browsers->pluck('hit_count'),
            'latestClicks'      => AnalyticsClick::with(['country', 'city', 'device', 'browser', 'operatingSystem', 'language'])
                ->whereBetween('created_at', [$from, $to])
                ->latest()->limit(10)->get(),
            'clicksCount'       => (clone $clicksQuery)->count(),
            'latestLinks'       => AnalyticsLink::orderByDesc('hit_count')->limit(10)->get(),
            'linksCount'        => AnalyticsLink::count(),
            'dateFrom'          => $from->format('Y-m-d'),
            'dateTo'            => $to->format('Y-m-d'),
            'allCountries'      => AnalyticsCountry::orderBy('name')->get(['id', 'name']),
            'allBrowsers'       => AnalyticsBrowser::orderBy('name')->get(['id', 'name']),
            'allDevices'        => AnalyticsDevice::orderBy('device_type')->get(['id', 'device_type']),
        ];
    }
}; ?>

<div wire:ignore>
    @include('gopanel.pages.analytics._body', $this->viewData)
</div>
