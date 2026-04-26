<?php

namespace App\Http\Controllers\Gopanel\Seo;

use App\Http\Controllers\GoPanelController;
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
use Illuminate\Http\Request;

class AnalyticsController extends GoPanelController
{
    protected Carbon $from;
    protected Carbon $to;

    public function __construct(Request $request)
    {
        parent::__construct();

        $this->to = $request->filled('to')
            ? Carbon::parse($request->input('to'))->endOfDay()
            : Carbon::now()->endOfDay();

        $this->from = $request->filled('from')
            ? Carbon::parse($request->input('from'))->startOfDay()
            : Carbon::now()->subDays(6)->startOfDay();
    }

    public function index(Request $request)
    {
        $clicksQuery = AnalyticsClick::whereBetween('created_at', [$this->from, $this->to]);

        $countriesCount  = (clone $clicksQuery)->distinct('country_id')->count('country_id');
        $citiesCount     = (clone $clicksQuery)->distinct('city_id')->count('city_id');
        $languagesCount  = AnalyticsLanguage::count();
        $operatingsCount = AnalyticsOperatingSystem::count();

        $devices     = AnalyticsDevice::orderByDesc('hit_count')->limit(10)->get();
        $browsers    = AnalyticsBrowser::orderByDesc('hit_count')->limit(10)->get();
        $adPlatforms = AnalyticsAdPlatform::orderByDesc('hit_count')->limit(10)->get();
        $utms        = AnalyticsUtmParameter::with('click.link')->latest()->limit(10)->get();
        $utmsCount   = AnalyticsUtmParameter::count();
        $anayticsLanguages = AnalyticsLanguage::orderByDesc('hit_count')->limit(10)->get();

        $deviceLabels  = $devices->pluck('device_type');
        $deviceHits    = $devices->pluck('hit_count');
        $browserLabels = $browsers->pluck('name');
        $browserHits   = $browsers->pluck('hit_count');

        $latestClicks = AnalyticsClick::with(['country', 'city', 'device', 'browser', 'operatingSystem', 'language'])
            ->whereBetween('created_at', [$this->from, $this->to])
            ->latest()->limit(10)->get();
        $clicksCount = (clone $clicksQuery)->count();

        $latestLinks = AnalyticsLink::orderByDesc('hit_count')->limit(10)->get();
        $linksCount  = AnalyticsLink::count();

        $dateFrom = $this->from->format('Y-m-d');
        $dateTo   = $this->to->format('Y-m-d');

        // Filter data for selects
        $allCountries = AnalyticsCountry::orderBy('name')->get(['id', 'name']);
        $allBrowsers  = AnalyticsBrowser::orderBy('name')->get(['id', 'name']);
        $allDevices   = AnalyticsDevice::orderBy('device_type')->get(['id', 'device_type']);

        return view('gopanel.pages.analytics.index', compact(
            'countriesCount', 'citiesCount', 'languagesCount', 'operatingsCount',
            'devices', 'deviceLabels', 'deviceHits',
            'browsers', 'browserLabels', 'browserHits',
            'anayticsLanguages', 'adPlatforms',
            'utms', 'utmsCount',
            'latestClicks', 'clicksCount',
            'latestLinks', 'linksCount',
            'dateFrom', 'dateTo',
            'allCountries', 'allBrowsers', 'allDevices'
        ));
    }


    public function getTopHits(Request $request)
    {
        $days      = $this->from->diffInDays($this->to);
        $prevEnd   = (clone $this->from)->subDay()->endOfDay();
        $prevStart = (clone $prevEnd)->subDays($days)->startOfDay();

        // Total Hits
        $currentTotalHits = AnalyticsClick::whereBetween('created_at', [$this->from, $this->to])->count();
        $prevTotalHits    = AnalyticsClick::whereBetween('created_at', [$prevStart, $prevEnd])->count();
        $totalHitsChange  = $prevTotalHits > 0 ? (($currentTotalHits - $prevTotalHits) / $prevTotalHits) * 100 : 0;

        // Countries
        $currentCountries = AnalyticsClick::whereBetween('created_at', [$this->from, $this->to])->distinct('country_id')->count('country_id');
        $prevCountries    = AnalyticsClick::whereBetween('created_at', [$prevStart, $prevEnd])->distinct('country_id')->count('country_id');
        $countriesChange  = $prevCountries > 0 ? (($currentCountries - $prevCountries) / $prevCountries) * 100 : 0;

        // Cities
        $currentCities = AnalyticsClick::whereBetween('created_at', [$this->from, $this->to])->distinct('city_id')->count('city_id');
        $prevCities    = AnalyticsClick::whereBetween('created_at', [$prevStart, $prevEnd])->distinct('city_id')->count('city_id');
        $citiesChange  = $prevCities > 0 ? (($currentCities - $prevCities) / $prevCities) * 100 : 0;

        // Ad Clicks
        $currentAdClicks = AnalyticsClick::whereBetween('created_at', [$this->from, $this->to])
            ->whereHas('adPlatformData')->count();
        $prevAdClicks = AnalyticsClick::whereBetween('created_at', [$prevStart, $prevEnd])
            ->whereHas('adPlatformData')->count();
        $adClicksChange = $prevAdClicks > 0 ? (($currentAdClicks - $prevAdClicks) / $prevAdClicks) * 100 : 0;

        return response()->json([
            'total' => [
                'current'  => $currentTotalHits,
                'previous' => $prevTotalHits,
                'change'   => round($totalHitsChange, 2),
                'trend'    => $totalHitsChange >= 0 ? 'increase' : 'decrease',
            ],
            'countries' => [
                'current'  => $currentCountries,
                'previous' => $prevCountries,
                'change'   => round($countriesChange, 2),
                'trend'    => $countriesChange >= 0 ? 'increase' : 'decrease',
            ],
            'cities' => [
                'current'  => $currentCities,
                'previous' => $prevCities,
                'change'   => round($citiesChange, 2),
                'trend'    => $citiesChange >= 0 ? 'increase' : 'decrease',
            ],
            'adclicks' => [
                'current'  => $currentAdClicks,
                'previous' => $prevAdClicks,
                'change'   => round($adClicksChange, 2),
                'trend'    => $adClicksChange >= 0 ? 'increase' : 'decrease',
            ],
        ]);
    }

    public function getCountriesMap(Request $request)
    {
        $coords = config('seo.analytics.country_coords', []);

        // Use analytics_countries table directly (hit_count based, no date filter needed for map)
        $countries = AnalyticsCountry::where('hit_count', '>', 0)
            ->orderByDesc('hit_count')
            ->limit(50)
            ->get();

        $totalHits = $countries->sum('hit_count');

        $result = [];
        foreach ($countries as $c) {
            $code = strtoupper($c->iso_code);
            $lat  = $coords[$code]['lat'] ?? null;
            $lng  = $coords[$code]['lng'] ?? null;

            // Skip if no coordinates
            if (!$lat || !$lng) continue;

            // Get top city for this country
            $topCity = AnalyticsCity::where('country_id', $c->id)
                ->orderByDesc('hit_count')
                ->first();

            $cityCount = AnalyticsCity::where('country_id', $c->id)->count();

            $result[] = [
                'name'       => $c->name,
                'code'       => $code,
                'hits'       => $c->hit_count,
                'lat'        => $lat,
                'lng'        => $lng,
                'flag'       => $c->flag,
                'top_city'   => $topCity ? $topCity->name : '-',
                'city_count' => $cityCount,
                'last_visit' => $c->last_visited_at ? Carbon::parse($c->last_visited_at)->format('d.m.Y H:i') : '-',
                'percent'    => $totalHits > 0 ? round(($c->hit_count / $totalHits) * 100, 1) : 0,
            ];
        }

        return response()->json($result);
    }

    public function getCitiesChart(Request $request)
    {
        $cities = AnalyticsCity::with('country')
            ->where('hit_count', '>', 0)
            ->orderByDesc('hit_count')
            ->limit(10)
            ->get();

        return response()->json([
            'labels'    => $cities->pluck('name'),
            'countries' => $cities->pluck('country.name'),
            'hits'      => $cities->pluck('hit_count'),
        ]);
    }

    public function getLanguagesChart(Request $request)
    {
        $data = AnalyticsLanguage::orderByDesc('hit_count')->limit(10)->get();

        return response()->json([
            'labels' => $data->pluck('code'),
            'name'   => $data->pluck('name'),
            'hits'   => $data->pluck('hit_count'),
        ]);
    }

    public function getOperatingSystemsChart(Request $request)
    {
        $data = AnalyticsOperatingSystem::orderByDesc('hit_count')->limit(10)->get();

        return response()->json([
            'labels' => $data->pluck('name'),
            'hits'   => $data->pluck('hit_count'),
        ]);
    }

    /**
     * Select2 AJAX: ölkə axtarışı
     */
    public function searchCountries(Request $request)
    {
        $q = $request->input('q', '');
        $query = AnalyticsCountry::orderBy('name');

        if ($q) {
            $query->where('name', 'like', "%{$q}%");
        }

        $items = $query->limit(10)->get(['id', 'name', 'flag']);

        return response()->json([
            'results' => $items->map(fn($c) => [
                'id'   => $c->id,
                'text' => $c->name,
                'flag' => $c->flag,
            ]),
        ]);
    }

    /**
     * Select2 AJAX: şəhər axtarışı
     */
    public function searchCities(Request $request)
    {
        $q = $request->input('q', '');
        $query = AnalyticsCity::with('country')->orderBy('name');

        if ($q) {
            $query->where('name', 'like', "%{$q}%");
        }

        $items = $query->limit(10)->get(['id', 'name', 'country_id']);

        return response()->json([
            'results' => $items->map(fn($c) => [
                'id'   => $c->id,
                'text' => $c->name . ($c->country ? ' (' . $c->country->name . ')' : ''),
            ]),
        ]);
    }
}
