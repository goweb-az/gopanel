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
        return $this->remember('clicks', function () {
            [$from, $to] = $this->range();
            $clicksQuery = AnalyticsClick::whereBetween('created_at', [$from, $to]);

            return [
                'count'  => (clone $clicksQuery)->count(),
                'latest' => AnalyticsClick::with(['country', 'city', 'device', 'browser', 'operatingSystem', 'language'])
                    ->whereBetween('created_at', [$from, $to])->latest()->limit(10)->get(),
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
                <div class="bg-light rounded" style="height:240px;"></div>
            </div>
        </div>
    </div>
@endplaceholder

<div>
    <div wire:key="analytics-clicks-{{ $dateFrom }}-{{ $dateTo }}" class="card analytics-clicks-card">
        <div class="card-header d-flex align-items-center justify-content-between">
            <h4 class="card-title mb-0">{{ __('Son 10 Kliklər') }}</h4>
            <a class="text-muted small" wire:navigate href="{{ route('gopanel.analytics.detail.clicks') }}">
                {{ __('Toplam') }} {{ number_format($this->data['count']) }} {{ __('klik') }} <i class="fas fa-arrow-right"></i>
            </a>
        </div>
        <div class="card-body">
            <div class="analytics-click-feed">
                @forelse ($this->data['latest'] as $click)
                    @php
                        $country  = $click?->country?->name ?? '-';
                        $city     = $click?->city?->name ?? '-';
                        $device   = $click?->device?->device_type ?? '-';
                        $browser  = $click?->browser?->name ?? '-';
                        $os       = $click?->operatingSystem?->name ?? '-';
                        $language = $click?->language?->code ?? '-';
                        $host     = $click->url ? parse_url($click->url, PHP_URL_HOST) : null;
                    @endphp

                    <div class="analytics-click-item">
                        <div class="analytics-click-id">#{{ $click->id }}</div>
                        <div class="analytics-click-main min-w-0">
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <strong class="text-dark text-truncate">{{ $host ?: 'direct' }}</strong>
                                <span class="badge bg-soft-success text-success">{{ $device }}</span>
                                <span class="badge bg-soft-info text-info">{{ $browser }}</span>
                                <span class="badge bg-light text-muted">{{ $os }}</span>
                            </div>

                            <div class="analytics-click-url">
                                {!! $click->url_link ?? e($click->url) !!}
                            </div>

                            <div class="analytics-click-meta">
                                <span><i class="bx bx-map"></i> {{ $country }} / {{ $city }}</span>
                                <span><i class="bx bx-globe"></i> {{ strtoupper($language) }}</span>
                                <span><i class="bx bx-wifi"></i> {{ $click->ip_address }}</span>
                                <span><i class="bx bx-time-five"></i> {{ optional($click->created_at)->format('d.m.Y H:i') }}</span>
                            </div>

                            @if ($click->referer)
                                <div class="analytics-click-ref">
                                    <i class="bx bx-link"></i> {!! $click->referer_link !!}
                                </div>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="text-muted text-center py-4">{{ __('Klik məlumatı yoxdur') }}</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
