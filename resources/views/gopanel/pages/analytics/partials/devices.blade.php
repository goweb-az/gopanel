<div class="col-xl-6">
    <div class="card analytics-chart-card" id="analyticsDevicesCard">
        <div class="card-header d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-2">
                <h4 class="card-title mb-0">Cihaza g&ouml;r&#601; Trafik</h4>
                <button type="button" class="btn btn-sm btn-light analytics-fullscreen-toggle" data-target="#analyticsDevicesCard" data-chart="devices" title="Tam ekran">
                    <i class="bx bx-fullscreen"></i>
                </button>
            </div>
            <a class="text-muted small analytics-card-detail-link" href="{{ route('gopanel.analytics.detail.devices') }}" data-detail-link>
                {{ number_format($devices->sum('hit_count')) }} giri&#351; <i class="fas fa-arrow-right"></i>
            </a>
        </div>

        <div class="card-body">
            <div id="device-chart" class="apex-charts analytics-chart-surface"></div>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        var deviceLabels = {!! json_encode($deviceLabels) !!};
        var deviceHits   = {!! json_encode($deviceHits) !!};
    </script>
@endpush
