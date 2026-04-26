<!-- Countries -->
<div class="col-xl-6">
    <div class="card analytics-map-card" id="analyticsCountriesMapCard">
        <div class="card-header d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-2">
                <h4 class="card-title mb-0">Giri&#351; Olunan &Ouml;lk&#601;l&#601;r</h4>
                <button type="button" class="btn btn-sm btn-light analytics-map-fullscreen-toggle" id="mapFullscreenBtn" title="Tam ekran">
                    <i class="bx bx-fullscreen"></i>
                </button>
            </div>
            <a class="text-muted small analytics-map-detail-link" href="{{ route('gopanel.analytics.detail.countries') }}" data-detail-link>
                {{ $countriesCount }} &ouml;lk&#601; <i class="fas fa-arrow-right"></i>
            </a>
        </div>
        <div class="card-body" style="position:relative;">
            <div id="leaflet-map" style="height: 450px; border-radius: 6px;"></div>
        </div>
    </div>
</div>
