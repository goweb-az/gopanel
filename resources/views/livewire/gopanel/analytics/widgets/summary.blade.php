<?php

use App\Livewire\Concerns\AnalyticsWidget;
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
        return $this->remember('summary', function () {
            [$from, $to] = $this->range();
            $days = $from->diffInDays($to);
            $prevEnd   = (clone $from)->subDay()->endOfDay();
            $prevStart = (clone $prevEnd)->subDays($days)->startOfDay();

            $snapshot = fn ($a, $b) => [
                'total'     => AnalyticsClick::whereBetween('created_at', [$a, $b])->count(),
                'countries' => AnalyticsClick::whereBetween('created_at', [$a, $b])->distinct('country_id')->count('country_id'),
                'cities'    => AnalyticsClick::whereBetween('created_at', [$a, $b])->distinct('city_id')->count('city_id'),
                'adclicks'  => AnalyticsClick::whereBetween('created_at', [$a, $b])->whereHas('adPlatformData')->count(),
            ];

            $current  = $snapshot($from, $to);
            $previous = $snapshot($prevStart, $prevEnd);

            $cards = [
                'total'     => ['label' => __('Ümumi keçidlər'),  'route' => 'gopanel.analytics.detail.clicks'],
                'countries' => ['label' => __('Ölkələr'),         'route' => 'gopanel.analytics.detail.countries'],
                'cities'    => ['label' => __('Şəhərlər'),        'route' => 'gopanel.analytics.detail.cities'],
                'adclicks'  => ['label' => __('Reklam klikləri'), 'route' => 'gopanel.analytics.detail.ad.platforms'],
            ];

            $out = [];
            foreach ($cards as $key => $meta) {
                $cur = $current[$key];
                $prev = $previous[$key];
                $change = $prev > 0 ? (($cur - $prev) / $prev) * 100 : 0;
                $out[$key] = [
                    'label'   => $meta['label'],
                    'route'   => $meta['route'],
                    'current' => $cur,
                    'change'  => round(abs($change), 1),
                    'trend'   => $change >= 0 ? 'increase' : 'decrease',
                ];
            }

            return $out;
        });
    }
}; ?>

@placeholder
    <div>
        <div class="row" x-data="{ slots: 4 }">
            <template x-for="i in 4" :key="i">
                <div class="col-xl-3 col-md-6">
                    <div class="card" style="opacity:.4;">
                        <div class="card-body">
                            <div class="bg-light rounded mb-2" style="height:12px;width:50%;"></div>
                            <div class="bg-light rounded mb-3" style="height:24px;width:60%;"></div>
                            <div class="bg-light rounded" style="height:10px;width:70%;"></div>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>
@endplaceholder

<div>
    <div class="row" wire:key="analytics-summary-{{ $dateFrom }}-{{ $dateTo }}">
        @foreach ($this->data as $key => $card)
            <div class="col-xl-3 col-md-6">
                <div class="card">
                    <div class="card-body">
                        <a wire:navigate href="{{ route($card['route']) }}" class="text-muted mb-2 d-block">{{ $card['label'] }}</a>
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h4 class="mb-0">{{ number_format($card['current']) }}</h4>
                            </div>
                            <div class="flex-shrink-0">
                                <div class="apex-charts" data-summary-spark="{{ $key }}" style="height: 50px; width: 100px;"></div>
                            </div>
                        </div>
                        <div class="mt-3">
                            <span class="badge {{ $card['trend'] === 'increase' ? 'bg-soft-success text-success' : 'bg-soft-danger text-danger' }}">
                                {{ $card['change'] }}%
                            </span>
                            <span class="text-muted">
                                {{ $card['trend'] === 'increase' ? __('artım') : __('azalma') }}
                                {{ __('əvvəlki dövrlə') }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach

        @once
            @push('scripts')
                <script>
                    (function () {
                        function renderSparks(root) {
                            if (typeof ApexCharts === 'undefined') return;
                            root.querySelectorAll('[data-summary-spark]').forEach(function (el) {
                                if (el.dataset.sparkRendered === '1') return;
                                el.dataset.sparkRendered = '1';
                                new ApexCharts(el, {
                                    chart: { type: 'area', height: 60, sparkline: { enabled: true } },
                                    stroke: { curve: 'smooth', width: 2 },
                                    fill: { opacity: 0.2 },
                                    colors: ['#556ee6'],
                                    series: [{ data: Array.from({ length: 12 }, function () { return Math.floor(Math.random() * 40) + 10; }) }]
                                }).render();
                            });
                        }
                        function scan() { renderSparks(document); }
                        document.addEventListener('DOMContentLoaded', scan);
                        document.addEventListener('livewire:navigated', scan);
                        document.addEventListener('livewire:init', function () {
                            Livewire.hook('morph.added', function ({ el }) { renderSparks(el); });
                            Livewire.hook('commit',      function ({ succeed }) { succeed(function () { scan(); }); });
                        });
                    })();
                </script>
            @endpush
        @endonce
    </div>
</div>
