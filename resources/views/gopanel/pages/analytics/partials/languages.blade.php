<div class="col-xl-6">
    <div class="card analytics-chart-card" id="analyticsLanguagesCard">
        <div class="card-header d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-2">
                <h4 class="card-title mb-0">Dill&#601;r &uuml;zr&#601; statistika</h4>
                <button type="button" class="btn btn-sm btn-light analytics-fullscreen-toggle" data-target="#analyticsLanguagesCard" data-chart="languages" title="Tam ekran">
                    <i class="bx bx-fullscreen"></i>
                </button>
            </div>
            <a class="text-muted small analytics-card-detail-link" href="{{ route('gopanel.analytics.detail.languages') }}" data-detail-link>
                Toplam {{ $languagesCount }} dil <i class="fas fa-arrow-right"></i>
            </a>
        </div>
        <div class="card-body">
            <div class="analytics-chart-canvas-wrap">
                <canvas id="languagesLineChart"></canvas>
            </div>
        </div>
    </div>
</div>
