<div class="col-xl-6">
    <div class="card analytics-chart-card" id="analyticsOperatingSystemsCard">
        <div class="card-header d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-2">
                <h4 class="card-title mb-0">&#399;m&#601;liyyat sisteml&#601;ri</h4>
                <button type="button" class="btn btn-sm btn-light analytics-fullscreen-toggle" data-target="#analyticsOperatingSystemsCard" data-chart="os" title="Tam ekran">
                    <i class="bx bx-fullscreen"></i>
                </button>
            </div>
            <a class="text-muted small analytics-card-detail-link" href="{{ route('gopanel.analytics.detail.operating.systems') }}" data-detail-link>
                Toplam {{ $operatingsCount }} sistem <i class="fas fa-arrow-right"></i>
            </a>
        </div>
        <div class="card-body">
            <div class="analytics-chart-canvas-wrap">
                <canvas id="osBarChart"></canvas>
            </div>
        </div>
    </div>
</div>
