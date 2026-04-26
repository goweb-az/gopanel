<div class="col-xl-6">
    <div class="card analytics-chart-card" id="analyticsCitiesCard">
        <div class="card-header d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-2">
                <h4 class="card-title mb-0">&#350;&#601;h&#601;rl&#601;r&#601; g&ouml;r&#601; trafik</h4>
                <button type="button" class="btn btn-sm btn-light analytics-fullscreen-toggle" data-target="#analyticsCitiesCard" data-chart="cities" title="Tam ekran">
                    <i class="bx bx-fullscreen"></i>
                </button>
            </div>
            <a class="text-muted small analytics-card-detail-link" href="{{ route('gopanel.analytics.detail.cities') }}" data-detail-link>
                {{ $citiesCount }} &#351;&#601;h&#601;r <i class="fas fa-arrow-right"></i>
            </a>
        </div>
        <div class="card-body">
            <div id="cities-bar-chart" class="analytics-chart-surface"></div>
        </div>
    </div>
</div>
