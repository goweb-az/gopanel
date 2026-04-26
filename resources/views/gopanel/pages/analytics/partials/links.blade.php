<div class="row">
    <div class="col-xl-12">
        <div class="card analytics-links-card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h4 class="card-title mb-0">Top 10 link</h4>
                <a class="text-muted small" href="{{ route('gopanel.analytics.detail.links') }}" data-detail-link>
                    Toplam {{ number_format($linksCount) }} link <i class="fas fa-arrow-right"></i>
                </a>
            </div>
            <div class="card-body">
                @php
                    $maxLinkHits = max((int) $latestLinks->max('hit_count'), 1);
                @endphp

                <div class="row g-3">
                    @forelse($latestLinks as $index => $link)
                        @php
                            $percent = min(100, round(($link->hit_count / $maxLinkHits) * 100));
                            $host = $link->url ? parse_url($link->url, PHP_URL_HOST) : null;
                            $firstVisit = $link->first_visited_at ? \Carbon\Carbon::parse($link->first_visited_at)->format('d.m.Y H:i') : '-';
                            $lastVisit = $link->last_visited_at ? \Carbon\Carbon::parse($link->last_visited_at)->format('d.m.Y H:i') : '-';
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
                                            @if($link->url)
                                                <a href="{{ $link->url }}" target="_blank">{{ $host ?: $link->url }}</a>
                                            @else
                                                <span class="text-muted">URL yoxdur</span>
                                            @endif
                                        </div>
                                        <div class="analytics-link-meta">
                                            <span>Dil: <strong>{{ strtoupper($link->locale ?: '-') }}</strong></span>
                                            <span>Ilk: {{ $firstVisit }}</span>
                                            <span>Son: {{ $lastVisit }}</span>
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
                            <div class="text-muted text-center py-4">Link m&#601;lumat&#305; yoxdur</div>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
