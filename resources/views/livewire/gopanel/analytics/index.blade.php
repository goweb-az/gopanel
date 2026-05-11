<?php

use App\Models\Analytics\AnalyticsAdPlatform;
use App\Models\Analytics\AnalyticsBrowser;
use App\Models\Analytics\AnalyticsCity;
use App\Models\Analytics\AnalyticsClick;
use App\Models\Analytics\AnalyticsCountry;
use App\Models\Analytics\AnalyticsDevice;
use App\Models\Analytics\AnalyticsLanguage;
use App\Models\Analytics\AnalyticsLink;
use App\Models\Analytics\AnalyticsOperatingSystem;
use App\Models\Analytics\AnalyticsUtmParameter;
use Carbon\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

new
#[Layout('gopanel.layouts.main')]
class extends Component {
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

    public string $countrySearch = '';
    public string $citySearch = '';

    public function mount(): void
    {
        if ($this->dateFrom === '') {
            $this->dateFrom = Carbon::now()->subDays(6)->format('Y-m-d');
        }
        if ($this->dateTo === '') {
            $this->dateTo = Carbon::now()->format('Y-m-d');
        }
    }

    private function range(): array
    {
        return [
            Carbon::parse($this->dateFrom)->startOfDay(),
            Carbon::parse($this->dateTo)->endOfDay(),
        ];
    }

    public function applyDateRange(string $from, string $to): void
    {
        $this->dateFrom = $from;
        $this->dateTo = $to;
        $this->dispatch('analytics-data-updated', data: $this->chartPayload());
    }

    public function applyFilters(): void
    {
        $this->dispatch('analytics-data-updated', data: $this->chartPayload());
    }

    public function clearFilters(): void
    {
        $this->countryId = null;
        $this->cityId = null;
        $this->browserId = null;
        $this->deviceId = null;
        $this->dispatch('analytics-data-updated', data: $this->chartPayload());
    }

    #[Computed]
    public function topHits(): array
    {
        [$from, $to] = $this->range();
        $days = $from->diffInDays($to);
        $prevEnd = (clone $from)->subDay()->endOfDay();
        $prevStart = (clone $prevEnd)->subDays($days)->startOfDay();

        $build = function ($from, $to, $build) {
            return [
                'total'     => AnalyticsClick::whereBetween('created_at', [$from, $to])->count(),
                'countries' => AnalyticsClick::whereBetween('created_at', [$from, $to])->distinct('country_id')->count('country_id'),
                'cities'    => AnalyticsClick::whereBetween('created_at', [$from, $to])->distinct('city_id')->count('city_id'),
                'adclicks'  => AnalyticsClick::whereBetween('created_at', [$from, $to])->whereHas('adPlatformData')->count(),
            ];
        };

        $current  = $build($from, $to, null);
        $previous = $build($prevStart, $prevEnd, null);

        $stat = function (string $key) use ($current, $previous) {
            $cur = $current[$key];
            $prev = $previous[$key];
            $change = $prev > 0 ? (($cur - $prev) / $prev) * 100 : 0;
            return [
                'current'  => $cur,
                'previous' => $prev,
                'change'   => round($change, 2),
                'trend'    => $change >= 0 ? 'increase' : 'decrease',
            ];
        };

        return [
            'total'     => $stat('total'),
            'countries' => $stat('countries'),
            'cities'    => $stat('cities'),
            'adclicks'  => $stat('adclicks'),
        ];
    }

    public function countriesMap(): array
    {
        $coords = config('seo.analytics.country_coords', []);
        $countries = AnalyticsCountry::where('hit_count', '>', 0)->orderByDesc('hit_count')->limit(50)->get();
        $totalHits = $countries->sum('hit_count');

        $result = [];
        foreach ($countries as $c) {
            $code = strtoupper($c->iso_code);
            $lat = $coords[$code]['lat'] ?? null;
            $lng = $coords[$code]['lng'] ?? null;
            if (!$lat || !$lng) continue;

            $topCity = AnalyticsCity::where('country_id', $c->id)->orderByDesc('hit_count')->first();
            $cityCount = AnalyticsCity::where('country_id', $c->id)->count();

            $result[] = [
                'name'       => $c->name,
                'code'       => $code,
                'hits'       => $c->hit_count,
                'lat'        => $lat,
                'lng'        => $lng,
                'flag'       => $c->flag,
                'top_city'   => $topCity?->name ?? '-',
                'city_count' => $cityCount,
                'last_visit' => $c->last_visited_at ? Carbon::parse($c->last_visited_at)->format('d.m.Y H:i') : '-',
                'percent'    => $totalHits > 0 ? round(($c->hit_count / $totalHits) * 100, 1) : 0,
            ];
        }
        return $result;
    }

    public function citiesChart(): array
    {
        $cities = AnalyticsCity::with('country')->where('hit_count', '>', 0)->orderByDesc('hit_count')->limit(10)->get();
        return [
            'labels'    => $cities->pluck('name')->all(),
            'countries' => $cities->pluck('country.name')->all(),
            'hits'      => $cities->pluck('hit_count')->all(),
        ];
    }

    public function languagesChart(): array
    {
        $data = AnalyticsLanguage::orderByDesc('hit_count')->limit(10)->get();
        return [
            'labels' => $data->pluck('code')->all(),
            'name'   => $data->pluck('name')->all(),
            'hits'   => $data->pluck('hit_count')->all(),
        ];
    }

    public function osChart(): array
    {
        $data = AnalyticsOperatingSystem::orderByDesc('hit_count')->limit(10)->get();
        return [
            'labels' => $data->pluck('name')->all(),
            'hits'   => $data->pluck('hit_count')->all(),
        ];
    }

    private function chartPayload(): array
    {
        return [
            'topHits'      => $this->topHits,
            'countriesMap' => $this->countriesMap(),
            'cities'       => $this->citiesChart(),
            'languages'    => $this->languagesChart(),
            'os'           => $this->osChart(),
        ];
    }

    public function updatedCountrySearch(): void {}
    public function updatedCitySearch(): void {}

    #[Computed]
    public function countryOptions()
    {
        $query = AnalyticsCountry::orderBy('name');
        if ($this->countrySearch !== '') {
            $query->where('name', 'like', '%' . $this->countrySearch . '%');
        }
        return $query->limit(10)->get(['id', 'name', 'flag']);
    }

    #[Computed]
    public function cityOptions()
    {
        $query = AnalyticsCity::with('country')->orderBy('name');
        if ($this->citySearch !== '') {
            $query->where('name', 'like', '%' . $this->citySearch . '%');
        }
        return $query->limit(10)->get(['id', 'name', 'country_id']);
    }

    #[Computed]
    public function viewData(): array
    {
        [$from, $to] = $this->range();
        $devices  = AnalyticsDevice::orderByDesc('hit_count')->limit(10)->get();
        $browsers = AnalyticsBrowser::orderByDesc('hit_count')->limit(10)->get();
        $clicksQuery = AnalyticsClick::whereBetween('created_at', [$from, $to]);

        return [
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
            'deviceLabels'      => $devices->pluck('device_type')->all(),
            'deviceHits'        => $devices->pluck('hit_count')->all(),
            'browserLabels'     => $browsers->pluck('name')->all(),
            'browserHits'       => $browsers->pluck('hit_count')->all(),
            'latestClicks'      => AnalyticsClick::with(['country', 'city', 'device', 'browser', 'operatingSystem', 'language'])
                ->whereBetween('created_at', [$from, $to])->latest()->limit(10)->get(),
            'clicksCount'       => (clone $clicksQuery)->count(),
            'latestLinks'       => AnalyticsLink::orderByDesc('hit_count')->limit(10)->get(),
            'linksCount'        => AnalyticsLink::count(),
            'allBrowsers'       => AnalyticsBrowser::orderBy('name')->get(['id', 'name']),
            'allDevices'        => AnalyticsDevice::orderBy('device_type')->get(['id', 'device_type']),
            'dateFrom'          => $this->dateFrom,
            'dateTo'            => $this->dateTo,
        ];
    }
}; ?>

<div>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />

    @php $vd = $this->viewData; extract($vd); @endphp

    <div wire:ignore.self class="page-content" id="analyticsWrapperDahboard" style="position:relative;"
         x-data="{ filterOpen: {{ ($countryId || $cityId || $browserId || $deviceId) ? 'true' : 'false' }} }">
        <div class="analytics-loader-overlay" id="analyticsLoader" wire:loading.class="active">
            <div class="analytics-loader-spinner"></div>
        </div>

        <div class="container-fluid">
            <x-gopanel.page-header :title="__('Analitika')" :showCreateButton="false">
                <x-slot:actions>
                    <div id="analyticsDateRange" class="btn btn-light border d-flex align-items-center gap-2" style="cursor:pointer; min-width:220px;" wire:ignore>
                        <i class="bx bx-calendar font-size-16"></i>
                        <span id="dateRangeLabel">{{ Carbon::parse($dateFrom)->format('d/m/Y') }} – {{ Carbon::parse($dateTo)->format('d/m/Y') }}</span>
                        <i class="bx bx-chevron-down ms-auto"></i>
                    </div>
                    <button type="button" class="btn btn-outline-secondary" x-on:click="filterOpen = !filterOpen">
                        <template x-if="filterOpen"><span><i class="fas fa-times"></i> {{ __('Filteri bağla') }}</span></template>
                        <template x-if="!filterOpen"><span><i class="fas fa-filter"></i> Filter</span></template>
                    </button>
                </x-slot:actions>
            </x-gopanel.page-header>

            <div class="mb-3" x-show="filterOpen" x-cloak>
                <div class="card card-body analytics-filter-panel">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label">{{ __('Ölkə') }}</label>
                            <select class="form-select" wire:model.live="countryId">
                                <option value="">{{ __('Hamısı') }}</option>
                                @foreach ($this->countryOptions as $c)
                                    <option value="{{ $c->id }}">{{ $c->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ __('Şəhər') }}</label>
                            <select class="form-select" wire:model.live="cityId">
                                <option value="">{{ __('Hamısı') }}</option>
                                @foreach ($this->cityOptions as $c)
                                    <option value="{{ $c->id }}">{{ $c->name }}@if ($c->country) ({{ $c->country->name }})@endif</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">{{ __('Brauzerlər') }}</label>
                            <select class="form-select" wire:model.live="browserId">
                                <option value="">{{ __('Hamısı') }}</option>
                                @foreach ($allBrowsers as $b)
                                    <option value="{{ $b->id }}">{{ $b->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">{{ __('Cihaz') }}</label>
                            <select class="form-select" wire:model.live="deviceId">
                                <option value="">{{ __('Hamısı') }}</option>
                                @foreach ($allDevices as $d)
                                    <option value="{{ $d->id }}">{{ $d->device_type }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-auto d-flex align-items-end gap-2">
                            <button type="button" class="btn btn-primary" wire:click="applyFilters">
                                <i class="fas fa-search"></i> {{ __('Filtrlə') }}
                            </button>
                            <button type="button" class="btn btn-light" wire:click="clearFilters">{{ __('Sıfırla') }}</button>
                        </div>
                    </div>
                </div>
            </div>

            @include('gopanel.pages.analytics.partials.summary')

            <div class="row">
                @include('gopanel.pages.analytics.partials.borowsers')
                @include('gopanel.pages.analytics.partials.devices')
            </div>

            <div class="row">
                @include('gopanel.pages.analytics.partials.countries')
                @include('gopanel.pages.analytics.partials.city')
            </div>

            @include('gopanel.pages.analytics.partials.adplatforms')

            <div class="row">
                @include('gopanel.pages.analytics.partials.languages')
                @include('gopanel.pages.analytics.partials.operatings')
            </div>

            @include('gopanel.pages.analytics.partials.utms')
            @include('gopanel.pages.analytics.partials.clicks')
            @include('gopanel.pages.analytics.partials.links')
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/moment@2.30.1/moment.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="{{ asset('/assets/gopanel/libs/apexcharts/apexcharts.min.js') }}"></script>
    <script src="{{ asset('/assets/gopanel/libs/chart.js/Chart.bundle.min.js') }}"></script>

    <script>
        window.gpAnalyticsBoot = @js([
            'dateFrom'  => $dateFrom,
            'dateTo'    => $dateTo,
            'devices'   => ['labels' => $deviceLabels,  'hits' => $deviceHits],
            'browsers'  => ['labels' => $browserLabels, 'hits' => $browserHits],
            'topHits'   => $this->topHits,
            'countriesMap' => $this->countriesMap(),
            'cities'    => $this->citiesChart(),
            'languages' => $this->languagesChart(),
            'os'        => $this->osChart(),
        ]);
    </script>

    <script src="{{ asset('/assets/gopanel/js/modules/analytics.js?v=' . time()) }}"></script>
</div>
