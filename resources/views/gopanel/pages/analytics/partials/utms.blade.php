<div class="row">
    <div class="col-xl-12">
        <div class="card analytics-utm-card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h4 class="card-title mb-0">UTM Parametrl&#601;ri: son 10 m&#601;lumat</h4>
                <a class="text-muted small" href="{{ route('gopanel.analytics.detail.utm.parameters') }}" data-detail-link>
                    Toplam {{ $utmsCount }} parametr <i class="fas fa-arrow-right"></i>
                </a>
            </div>

            <div class="card-body">
                <div class="row g-3">
                    @forelse($utms as $utm)
                        @php
                            $targetUrl = $utm?->click?->link?->url ?? $utm?->click?->url;
                            $source = $utm->utm_source ?: 'unknown';
                        @endphp
                        <div class="col-xl-6">
                            <div class="analytics-utm-item">
                                <div class="d-flex align-items-start justify-content-between gap-3">
                                    <div class="min-w-0">
                                        <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                                            <span class="analytics-utm-source">{{ strtoupper(substr($source, 0, 1)) }}</span>
                                            <strong class="text-dark">{{ $source }}</strong>
                                            @if($utm->utm_medium)
                                                <span class="badge bg-soft-info text-info">{{ $utm->utm_medium }}</span>
                                            @endif
                                            @if($utm->utm_campaign)
                                                <span class="badge bg-soft-primary text-primary">{{ $utm->utm_campaign }}</span>
                                            @endif
                                        </div>

                                        <div class="analytics-utm-meta">
                                            @if($utm->utm_content)
                                                <span>Content: <strong>{{ $utm->utm_content }}</strong></span>
                                            @endif
                                            @if($utm->utm_term)
                                                <span>Term: <strong>{{ $utm->utm_term }}</strong></span>
                                            @endif
                                        </div>
                                    </div>

                                    @if($targetUrl)
                                        <a class="btn btn-sm btn-light analytics-utm-open" href="{{ $targetUrl }}" target="_blank" title="Linki ac">
                                            <i class="bx bx-link-external"></i>
                                        </a>
                                    @endif
                                </div>

                                @if($targetUrl)
                                    <a class="analytics-utm-url" href="{{ $targetUrl }}" target="_blank">{{ $targetUrl }}</a>
                                @else
                                    <span class="analytics-utm-url text-muted">Link yoxdur</span>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="col-12">
                            <div class="text-muted text-center py-4">UTM m&#601;lumat&#305; yoxdur</div>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
