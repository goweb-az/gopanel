<?php

use App\Livewire\Concerns\AnalyticsWidget;
use App\Models\Analytics\AnalyticsCity;
use App\Models\Analytics\AnalyticsClick;
use App\Models\Analytics\AnalyticsCountry;
use Carbon\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Lazy;
use Livewire\Component;

new
#[Lazy]
class extends Component {
    use AnalyticsWidget;

    #[Computed]
    public function data(): array
    {
        return $this->remember('countries', function () {
            [$from, $to] = $this->range();
            $coords = config('seo.analytics.country_coords', []);
            $countries = AnalyticsCountry::where('hit_count', '>', 0)->orderByDesc('hit_count')->limit(50)->get();
            $totalHits = (int) $countries->sum('hit_count');

            $countriesCount = AnalyticsClick::whereBetween('created_at', [$from, $to])->distinct('country_id')->count('country_id');

            $map = [];
            foreach ($countries as $c) {
                $code = strtoupper($c->iso_code);
                $lat = $coords[$code]['lat'] ?? null;
                $lng = $coords[$code]['lng'] ?? null;
                if (! $lat || ! $lng) {
                    continue;
                }

                $topCity = AnalyticsCity::where('country_id', $c->id)->orderByDesc('hit_count')->first();
                $cityCount = AnalyticsCity::where('country_id', $c->id)->count();

                $map[] = [
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

            return [
                'countriesCount' => $countriesCount,
                'map'            => $map,
            ];
        });
    }
}; ?>

@placeholder
    <div>
        <div class="card" style="opacity:.4;">
            <div class="card-header">
                <div class="bg-light rounded" style="height:18px;width:40%;"></div>
            </div>
            <div class="card-body">
                <div class="bg-light rounded" style="height:450px;"></div>
            </div>
        </div>
    </div>
@endplaceholder

<div>
    <div wire:key="analytics-countries-{{ $dateFrom }}-{{ $dateTo }}" class="card analytics-map-card">
        <div class="card-header d-flex align-items-center justify-content-between">
            <h4 class="card-title mb-0">{{ __('Giriş Olunan Ölkələr') }}</h4>
            <a class="text-muted small" wire:navigate href="{{ route('gopanel.analytics.detail.countries') }}">
                {{ $this->data['countriesCount'] }} {{ __('ölkə') }} <i class="fas fa-arrow-right"></i>
            </a>
        </div>
        <div class="card-body" style="position:relative;">
            <div
                wire:ignore
                x-data
                x-init="
                    if (typeof L === 'undefined') return;
                    const data = @js($this->data['map']);
                    const map = L.map($el, { scrollWheelZoom: false, zoomControl: true }).setView([35, 35], 2);
                    L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
                        attribution: '&copy; <a href=&quot;https://www.openstreetmap.org/copyright&quot;>OSM</a> &copy; <a href=&quot;https://carto.com/&quot;>CARTO</a>',
                        subdomains: 'abcd', maxZoom: 19,
                    }).addTo(map);
                    const markers = L.layerGroup().addTo(map);
                    data.forEach(function (item, index) {
                        if (!item.lat || !item.lng) return;
                        const markerHtml = '<div class=&quot;analytics-marker-drop&quot; style=&quot;animation-delay:' + (index * 120) + 'ms&quot;><div class=&quot;leaflet-marker-pin&quot;><span>' + item.hits + '</span></div></div>';
                        const icon = L.divIcon({ className: 'analytics-marker', html: markerHtml, iconSize: [36, 36], iconAnchor: [18, 36], popupAnchor: [0, -38] });
                        const flagHtml = item.flag ? '<img src=&quot;' + item.flag + '&quot; width=&quot;20&quot; style=&quot;vertical-align:middle;margin-right:6px;border-radius:2px;&quot;>' : '';
                        const popup =
                            '<div class=&quot;analytics-popup&quot;><h6>' + flagHtml + item.name + '</h6>' +
                            '<div class=&quot;popup-row&quot;><i class=&quot;bx bx-mouse-alt&quot;></i> Keçid: <strong>' + item.hits + '</strong></div>' +
                            '<div class=&quot;popup-row&quot;><i class=&quot;bx bx-buildings&quot;></i> Şəhər: <strong>' + item.city_count + '</strong></div>' +
                            '<div class=&quot;popup-row&quot;><i class=&quot;bx bx-map-pin&quot;></i> Top şəhər: <strong>' + item.top_city + '</strong></div>' +
                            '<div class=&quot;popup-row&quot;><i class=&quot;bx bx-time-five&quot;></i> Son giriş: ' + item.last_visit + '</div>' +
                            '<div class=&quot;popup-progress&quot;><div class=&quot;popup-progress-bar&quot; style=&quot;width:' + item.percent + '%&quot;></div></div>' +
                            '<div class=&quot;popup-percent&quot;>Trafik payı: ' + item.percent + '%</div></div>';
                        L.marker([item.lat, item.lng], { icon: icon }).bindPopup(popup, { maxWidth: 260, autoPan: true, keepInView: true }).addTo(markers);
                    });
                    if (markers.getLayers().length > 0) {
                        const group = new L.featureGroup(markers.getLayers());
                        map.fitBounds(group.getBounds().pad(0.2));
                    }
                "
                style="height: 450px; border-radius: 6px;"
            ></div>
        </div>
    </div>
</div>
