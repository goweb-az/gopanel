<?php

use App\Livewire\Concerns\AnalyticsWidget;
use App\Models\Analytics\AnalyticsBrowser;
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
        return $this->remember('browsers', function () {
            $rows = AnalyticsBrowser::orderByDesc('hit_count')->limit(10)->get();

            return [
                'totalHits' => (int) $rows->sum('hit_count'),
                'labels'    => $rows->pluck('name')->all(),
                'hits'      => $rows->pluck('hit_count')->all(),
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
                <div class="bg-light rounded" style="height:280px;"></div>
            </div>
        </div>
    </div>
@endplaceholder

<div>
    <div wire:key="analytics-browsers-{{ $dateFrom }}-{{ $dateTo }}" class="card analytics-chart-card">
        <div class="card-header d-flex align-items-center justify-content-between">
            <h4 class="card-title mb-0">{{ __('Brauzer üzrə Trafik') }}</h4>
            <a class="text-muted small" wire:navigate href="{{ route('gopanel.analytics.detail.browsers') }}">
                {{ number_format($this->data['totalHits']) }} {{ __('giriş') }} <i class="fas fa-arrow-right"></i>
            </a>
        </div>

        <div class="card-body">
            <div
                wire:ignore
                x-data
                x-init="
                    if (typeof ApexCharts === 'undefined') return;
                    const labels = @js($this->data['labels']);
                    const hits   = @js($this->data['hits']);
                    new ApexCharts($el, {
                        chart: { type: 'donut', height: 300 },
                        series: hits,
                        labels: labels,
                        legend: { position: 'bottom' },
                        colors: ['#556ee6', '#34c38f', '#f1b44c', '#f46a6a', '#50a5f1', '#74788d', '#fd7e14', '#6f42c1', '#20c997', '#e83e8c'],
                        dataLabels: { enabled: true, formatter: function (v) { return Math.round(v) + '%'; } },
                        tooltip: { y: { formatter: function (v) { return v.toLocaleString() + ' giriş'; } } }
                    }).render();
                "
                style="min-height: 300px;"
            ></div>
        </div>
    </div>
</div>
