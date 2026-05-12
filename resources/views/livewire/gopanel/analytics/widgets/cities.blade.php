<?php

use App\Livewire\Concerns\AnalyticsWidget;
use App\Models\Analytics\AnalyticsCity;
use App\Models\Analytics\AnalyticsClick;
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
        return $this->remember('cities', function () {
            [$from, $to] = $this->range();
            $rows = AnalyticsCity::with('country')->where('hit_count', '>', 0)->orderByDesc('hit_count')->limit(10)->get();

            return [
                'citiesCount' => AnalyticsClick::whereBetween('created_at', [$from, $to])->distinct('city_id')->count('city_id'),
                'labels'      => $rows->pluck('name')->all(),
                'countries'   => $rows->pluck('country.name')->all(),
                'hits'        => $rows->pluck('hit_count')->all(),
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
                <div class="bg-light rounded" style="height:430px;"></div>
            </div>
        </div>
    </div>
@endplaceholder

<div>
    <div wire:key="analytics-cities-{{ $dateFrom }}-{{ $dateTo }}" class="card analytics-chart-card">
        <div class="card-header d-flex align-items-center justify-content-between">
            <h4 class="card-title mb-0">{{ __('Şəhərlərə görə trafik') }}</h4>
            <a class="text-muted small" wire:navigate href="{{ route('gopanel.analytics.detail.cities') }}">
                {{ $this->data['citiesCount'] }} {{ __('şəhər') }} <i class="fas fa-arrow-right"></i>
            </a>
        </div>
        <div class="card-body">
            @if (empty($this->data['hits']))
                <x-gopanel.empty-state icon="bx bx-buildings" height="430px" />
            @else
                <div
                    wire:ignore
                    x-data
                    x-init="
                        if (typeof ApexCharts === 'undefined') return;
                        const labels = @js($this->data['labels']);
                        const hits   = @js($this->data['hits']);
                        const topValue = Math.max.apply(null, hits.length ? hits : [0]);
                        new ApexCharts($el, {
                            chart: { type: 'bar', height: 430, toolbar: { show: false }, animations: { enabled: true, easing: 'easeinout', speed: 650 } },
                            plotOptions: { bar: { horizontal: true, borderRadius: 6, distributed: true, barHeight: '64%' } },
                            colors: ['#556ee6', '#34c38f', '#50a5f1', '#f1b44c', '#f46a6a', '#6f42c1', '#2ab57d', '#fd7e14'],
                            series: [{ name: 'Klik sayı', data: hits }],
                            xaxis: {
                                categories: labels,
                                max: topValue ? Math.ceil(topValue * 1.15) : undefined,
                                labels: { style: { fontSize: '12px', colors: '#74788d' } },
                                axisBorder: { show: false }, axisTicks: { show: false }
                            },
                            yaxis: { labels: { style: { fontSize: '13px', colors: '#343a40' } } },
                            grid: { borderColor: '#edf1f5', strokeDashArray: 4 },
                            dataLabels: { enabled: true, textAnchor: 'start', offsetX: 8, style: { colors: ['#343a40'], fontSize: '12px', fontWeight: 600 }, formatter: function (v) { return v.toLocaleString(); } },
                            legend: { show: false },
                            tooltip: { y: { formatter: function (v) { return v.toLocaleString() + ' keçid'; } } }
                        }).render();
                    "
                    style="min-height: 430px;"
                ></div>
            @endif
        </div>
    </div>
</div>
