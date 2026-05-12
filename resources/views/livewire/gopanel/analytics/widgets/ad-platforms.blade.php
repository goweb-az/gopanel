<?php

use App\Livewire\Concerns\AnalyticsWidget;
use App\Models\Analytics\AnalyticsAdPlatform;
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
        return $this->remember('ad-platforms', function () {
            $rows = AnalyticsAdPlatform::orderByDesc('hit_count')->limit(10)->get();

            return [
                'platforms' => $rows,
                'maxHits'   => max((int) $rows->max('hit_count'), 1),
                'count'     => $rows->count(),
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
                <div class="bg-light rounded" style="height:200px;"></div>
            </div>
        </div>
    </div>
@endplaceholder

<div>
    <div wire:key="analytics-ad-platforms-{{ $dateFrom }}-{{ $dateTo }}" class="card analytics-ad-card">
        <div class="card-header d-flex align-items-center justify-content-between">
            <h4 class="card-title mb-0">{{ __('Reklam Platformalarının Performansı') }}</h4>
            <a class="text-muted small" wire:navigate href="{{ route('gopanel.analytics.detail.ad.platforms') }}">
                {{ $this->data['count'] }} {{ __('platforma') }} <i class="fas fa-arrow-right"></i>
            </a>
        </div>
        <div class="card-body">
            <div class="row g-3">
                @forelse ($this->data['platforms'] as $platform)
                    @php
                        $percent = min(100, round(($platform->hit_count / $this->data['maxHits']) * 100));
                        $firstVisit = $platform->first_visited_at ? \Carbon\Carbon::parse($platform->first_visited_at)->format('d.m.Y H:i') : '-';
                        $lastVisit = $platform->last_visited_at ? \Carbon\Carbon::parse($platform->last_visited_at)->format('d.m.Y H:i') : '-';
                    @endphp
                    <div class="col-xl-3 col-lg-4 col-sm-6">
                        <div class="analytics-ad-platform">
                            <div class="d-flex align-items-start gap-3">
                                <span class="analytics-ad-logo">
                                    @if (! empty($platform->logo))
                                        <img src="{{ $platform->logo }}" alt="{{ $platform->name }}" onerror="this.style.display='none'; this.nextElementSibling.style.display='inline-flex';">
                                        <i class="mdi mdi-bullhorn analytics-logo-fallback"></i>
                                    @else
                                        <i class="mdi mdi-bullhorn"></i>
                                    @endif
                                </span>
                                <div class="min-w-0 flex-grow-1">
                                    <div class="d-flex align-items-start justify-content-between gap-2">
                                        <h5 class="font-size-14 mb-1 text-truncate">{{ $platform->name }}</h5>
                                        <span class="badge bg-soft-primary text-primary">{{ number_format($platform->hit_count) }}</span>
                                    </div>
                                    <div class="analytics-ad-meta">
                                        <span>{{ __('İlk') }}: {{ $firstVisit }}</span>
                                        <span>{{ __('Son') }}: {{ $lastVisit }}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="analytics-mini-progress mt-3">
                                <span style="width: {{ $percent }}%"></span>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12">
                        <div class="text-muted text-center py-4">{{ __('Reklam platforması məlumatı yoxdur') }}</div>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
