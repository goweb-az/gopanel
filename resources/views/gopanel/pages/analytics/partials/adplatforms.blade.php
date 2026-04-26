<!-- Row: Ad Platforms -->
<div class="row">
    <div class="col-xl-12">
        <div class="card analytics-ad-card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h4 class="card-title mb-0">Reklam Platformalar&#305;n&#305;n Performans&#305;</h4>
                <a class="text-muted small" href="{{ route('gopanel.analytics.detail.ad.platforms') }}" data-detail-link>
                    {{ $adPlatforms->count() }} platforma <i class="fas fa-arrow-right"></i>
                </a>
            </div>
            <div class="card-body">
                @php
                    $maxPlatformHits = max((int) $adPlatforms->max('hit_count'), 1);
                @endphp

                <div class="row g-3">
                    @forelse($adPlatforms as $platform)
                        @php
                            $percent = min(100, round(($platform->hit_count / $maxPlatformHits) * 100));
                            $firstVisit = $platform->first_visited_at ? \Carbon\Carbon::parse($platform->first_visited_at)->format('d.m.Y H:i') : '-';
                            $lastVisit = $platform->last_visited_at ? \Carbon\Carbon::parse($platform->last_visited_at)->format('d.m.Y H:i') : '-';
                        @endphp
                        <div class="col-xl-3 col-lg-4 col-sm-6">
                            <div class="analytics-ad-platform">
                                <div class="d-flex align-items-start gap-3">
                                    <span class="analytics-ad-logo">
                                        @if(!empty($platform->logo))
                                            <img src="{{ $platform->logo }}" alt="{{ $platform->name }}" onerror="this.style.display='none'; this.nextElementSibling.style.display='inline-flex';" />
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
                                            <span>Ilk: {{ $firstVisit }}</span>
                                            <span>Son: {{ $lastVisit }}</span>
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
                            <div class="text-muted text-center py-4">Reklam platformas&#305; m&#601;lumat&#305; yoxdur</div>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
