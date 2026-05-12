<?php

use App\Livewire\Concerns\AnalyticsWidget;
use App\Models\Analytics\AnalyticsLanguage;
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
        return $this->remember('languages', function () {
            $rows = AnalyticsLanguage::orderByDesc('hit_count')->limit(10)->get();

            return [
                'count'  => AnalyticsLanguage::count(),
                'labels' => $rows->pluck('code')->all(),
                'name'   => $rows->pluck('name')->all(),
                'hits'   => $rows->pluck('hit_count')->all(),
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
    <div wire:key="analytics-languages-{{ $dateFrom }}-{{ $dateTo }}" class="card analytics-chart-card">
        <div class="card-header d-flex align-items-center justify-content-between">
            <h4 class="card-title mb-0">{{ __('Dillər üzrə statistika') }}</h4>
            <a class="text-muted small" wire:navigate href="{{ route('gopanel.analytics.detail.languages') }}">
                {{ __('Toplam') }} {{ $this->data['count'] }} {{ __('dil') }} <i class="fas fa-arrow-right"></i>
            </a>
        </div>
        <div class="card-body">
            <div class="analytics-chart-canvas-wrap" style="height: 430px;">
                <canvas
                    wire:ignore
                    x-data
                    x-init="
                        if (typeof Chart === 'undefined') return;
                        const labels = @js($this->data['labels']);
                        const name   = @js($this->data['name']);
                        const hits   = @js($this->data['hits']);
                        const ctx = $el.getContext('2d');
                        const gradient = ctx.createLinearGradient(0, 0, 0, 430);
                        gradient.addColorStop(0, 'rgba(85,110,230,0.28)');
                        gradient.addColorStop(1, 'rgba(85,110,230,0.02)');
                        new Chart(ctx, {
                            type: 'line',
                            data: {
                                labels: labels.map(function (code, i) { return code + ' – ' + name[i]; }),
                                datasets: [{
                                    label: 'Dil üzrə keçid', data: hits,
                                    borderColor: '#556ee6', backgroundColor: gradient,
                                    fill: true, borderWidth: 3, tension: 0.42,
                                    pointBackgroundColor: '#fff', pointBorderColor: '#556ee6', pointBorderWidth: 2,
                                    pointRadius: 4, pointHoverRadius: 6
                                }]
                            },
                            options: {
                                responsive: true, maintainAspectRatio: false,
                                scales: {
                                    y: { beginAtZero: true, grid: { color: '#edf1f5' }, ticks: { color: '#74788d', precision: 0 } },
                                    x: { grid: { display: false }, ticks: { color: '#74788d' } }
                                },
                                plugins: { legend: { display: false }, tooltip: { callbacks: { label: function (c) { return c.parsed.y.toLocaleString() + ' keçid'; } } } }
                            }
                        });
                    "
                ></canvas>
            </div>
        </div>
    </div>
</div>
