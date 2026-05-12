<?php

use App\Livewire\Concerns\AnalyticsWidget;
use App\Models\Analytics\AnalyticsLink;
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
        return $this->remember('links', function () {
            $rows = AnalyticsLink::orderByDesc('hit_count')->limit(10)->get();

            return [
                'links'   => $rows,
                'count'   => AnalyticsLink::count(),
                'maxHits' => max((int) $rows->max('hit_count'), 1),
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
    <div wire:key="analytics-links-{{ $dateFrom }}-{{ $dateTo }}" class="card analytics-links-card">
        <div class="card-header d-flex align-items-center justify-content-between">
            <h4 class="card-title mb-0">{{ __('Top 10 link') }}</h4>
            <a class="text-muted small" wire:navigate href="{{ route('gopanel.analytics.detail.links') }}">
                {{ __('Toplam') }} {{ number_format($this->data['count']) }} {{ __('link') }} <i class="fas fa-arrow-right"></i>
            </a>
        </div>
        <div class="card-body">
            <div class="row g-3">
                @forelse ($this->data['links'] as $index => $link)
                    @php
                        $percent    = min(100, round(($link->hit_count / $this->data['maxHits']) * 100));
                        $host       = $link->url ? parse_url($link->url, PHP_URL_HOST) : null;
                        $firstVisit = $link->first_visited_at ? \Carbon\Carbon::parse($link->first_visited_at)->format('d.m.Y H:i') : '-';
                        $lastVisit  = $link->last_visited_at ? \Carbon\Carbon::parse($link->last_visited_at)->format('d.m.Y H:i') : '-';
                    @endphp
                    <div class="col-xl-6">
                        <div class="analytics-link-item">
                            <div class="d-flex align-items-start gap-3">
                                <span class="analytics-link-rank">{{ $index + 1 }}</span>
                                <div class="min-w-0 flex-grow-1">
                                    <div class="d-flex align-items-start justify-content-between gap-2 mb-1">
                                        <strong class="text-dark text-truncate">{{ $link->slug ?: '/' }}</strong>
                                        <span class="badge bg-soft-primary text-primary">{{ number_format($link->hit_count) }}</span>
                                    </div>
                                    <div class="analytics-link-url">
                                        @if ($link->url)
                                            <a href="{{ $link->url }}" target="_blank">{{ $host ?: $link->url }}</a>
                                        @else
                                            <span class="text-muted">{{ __('URL yoxdur') }}</span>
                                        @endif
                                    </div>
                                    <div class="analytics-link-meta">
                                        <span>{{ __('Dil') }}: <strong>{{ strtoupper($link->locale ?: '-') }}</strong></span>
                                        <span>{{ __('İlk') }}: {{ $firstVisit }}</span>
                                        <span>{{ __('Son') }}: {{ $lastVisit }}</span>
                                    </div>
                                    <div class="analytics-mini-progress mt-2">
                                        <span style="width: {{ $percent }}%"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12">
                        <div class="text-muted text-center py-4">{{ __('Link məlumatı yoxdur') }}</div>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
