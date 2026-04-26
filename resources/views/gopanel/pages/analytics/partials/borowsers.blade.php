<div class="col-xl-6">
    <div class="card analytics-chart-card" id="analyticsBrowsersCard">
        <div class="card-header d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-2">
                <h4 class="card-title mb-0">Brauzer &uuml;zr&#601; Trafik</h4>
                <button type="button" class="btn btn-sm btn-light analytics-fullscreen-toggle" data-target="#analyticsBrowsersCard" data-chart="browsers" title="Tam ekran">
                    <i class="bx bx-fullscreen"></i>
                </button>
            </div>
            <a class="text-muted small analytics-card-detail-link" href="{{ route('gopanel.analytics.detail.browsers') }}" data-detail-link>
                {{ number_format($browsers->sum('hit_count')) }} giri&#351; <i class="fas fa-arrow-right"></i>
            </a>
        </div>

        <div class="card-body">
            <div id="browser-chart" class="apex-charts analytics-chart-surface"></div>
        </div>
    </div>
</div>

@push('scripts')
    <!-- BROWSER CHART DATA -->
    <script>
        var browserLabels = {!! json_encode($browserLabels) !!};
        var browserHits   = {!! json_encode($browserHits) !!};
    </script>
@endpush
