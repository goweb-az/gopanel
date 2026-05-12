<?php

use App\Livewire\Concerns\AnalyticsWidget;
use App\Models\Analytics\AnalyticsOperatingSystem;
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
        return $this->remember('operating-systems', function () {
            $rows = AnalyticsOperatingSystem::orderByDesc('hit_count')->limit(10)->get();

            return [
                'count'  => AnalyticsOperatingSystem::count(),
                'labels' => $rows->pluck('name')->all(),
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
    <div wire:key="analytics-os-{{ $dateFrom }}-{{ $dateTo }}" class="card analytics-chart-card">
        <div class="card-header d-flex align-items-center justify-content-between">
            <h4 class="card-title mb-0">{{ __('Əməliyyat sistemləri') }}</h4>
            <a class="text-muted small" wire:navigate href="{{ route('gopanel.analytics.detail.operating.systems') }}">
                {{ __('Toplam') }} {{ $this->data['count'] }} {{ __('sistem') }} <i class="fas fa-arrow-right"></i>
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
                        const hits   = @js($this->data['hits']);
                        new Chart($el.getContext('2d'), {
                            type: 'bar',
                            data: {
                                labels: labels,
                                datasets: [{
                                    label: 'Keçid sayı', data: hits,
                                    backgroundColor: ['rgba(85,110,230,0.72)', 'rgba(52,195,143,0.72)', 'rgba(80,165,241,0.72)', 'rgba(241,180,76,0.72)', 'rgba(244,106,106,0.72)', 'rgba(111,66,193,0.72)'],
                                    borderColor: ['#556ee6', '#34c38f', '#50a5f1', '#f1b44c', '#f46a6a', '#6f42c1'],
                                    borderWidth: 1, borderRadius: 8, barPercentage: 0.72, categoryPercentage: 0.72
                                }]
                            },
                            options: {
                                indexAxis: 'y', responsive: true, maintainAspectRatio: false,
                                scales: {
                                    x: { beginAtZero: true, grid: { color: '#edf1f5' }, ticks: { color: '#74788d', precision: 0 } },
                                    y: { grid: { display: false }, ticks: { color: '#343a40' } }
                                },
                                plugins: { legend: { display: false }, tooltip: { callbacks: { label: function (c) { return c.parsed.x.toLocaleString() + ' keçid'; } } } }
                            }
                        });
                    "
                ></canvas>
            </div>
        </div>
    </div>
</div>
