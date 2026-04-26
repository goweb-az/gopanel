<div class="row">
    <div class="col-xl-12">
        <div class="card analytics-clicks-card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h4 class="card-title mb-0">Son 10 Klikl&#601;m&#601;l&#601;r</h4>
                <a class="text-muted small" href="{{ route('gopanel.analytics.detail.clicks') }}" data-detail-link>
                    Toplam {{ number_format($clicksCount) }} klik <i class="fas fa-arrow-right"></i>
                </a>
            </div>
            <div class="card-body">
                <div class="analytics-click-feed">
                    @forelse($latestClicks as $click)
                        @php
                            $country = $click?->country?->name ?? '-';
                            $city = $click?->city?->name ?? '-';
                            $device = $click?->device?->device_type ?? '-';
                            $browser = $click?->browser?->name ?? '-';
                            $os = $click?->operatingSystem?->name ?? '-';
                            $language = $click?->language?->code ?? '-';
                            $host = $click->url ? parse_url($click->url, PHP_URL_HOST) : null;
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

                                @if($click->referer)
                                    <div class="analytics-click-ref">
                                        <i class="bx bx-link"></i> {!! $click->referer_link !!}
                                    </div>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="text-muted text-center py-4">Klik m&#601;lumat&#305; yoxdur</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
