<div class="row">
    <!-- Ümumi keçidlər -->
    <div class="col-xl-3 col-md-6">
        <div class="card">
            <div class="card-body">
                <a href="{{route("gopanel.analytics.detail.clicks")}}" data-detail-link class="text-muted mb-2 d-block">Ümumi keçidlər</a>
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h4 class="mb-0" id="summary-total-hits">0</h4>
                    </div>
                    <div class="flex-shrink-0">
                        <div id="chart-total-hits" class="apex-charts" style="height: 50px; width: 100px;"></div>
                    </div>
                </div>
                <div class="mt-3">
                    <span id="summary-total-hits-badge" class="badge bg-soft-success text-success">
                        <span id="summary-total-hits-change">0%</span>
                    </span>
                    <span class="text-muted" id="summary-total-hits-trend">əvvəlki dövrlə müqayisədə</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Ölkələr -->
    <div class="col-xl-3 col-md-6">
        <div class="card">
            <div class="card-body">
                <a href="{{route("gopanel.analytics.detail.countries")}}" data-detail-link class="text-muted mb-2 d-block">Ölkələr</a>
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h4 class="mb-0" id="summary-countries">0</h4>
                    </div>
                    <div class="flex-shrink-0">
                        <div id="chart-countries" class="apex-charts" style="height: 50px; width: 100px;"></div>
                    </div>
                </div>
                <div class="mt-3">
                    <span id="summary-countries-badge" class="badge bg-soft-success text-success">
                        <span id="summary-countries-change">0%</span>
                    </span>
                    <span class="text-muted" id="summary-countries-trend">əvvəlki dövrlə müqayisədə</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Şəhərlər -->
    <div class="col-xl-3 col-md-6">
        <div class="card">
            <div class="card-body">
                <a href="{{route("gopanel.analytics.detail.cities")}}" data-detail-link class="text-muted mb-2 d-block">Şəhərlər</a>
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h4 class="mb-0" id="summary-cities">0</h4>
                    </div>
                    <div class="flex-shrink-0">
                        <div id="chart-cities" class="apex-charts" style="height: 50px; width: 100px;"></div>
                    </div>
                </div>
                <div class="mt-3">
                    <span id="summary-cities-badge" class="badge bg-soft-success text-success">
                        <span id="summary-cities-change">0%</span>
                    </span>
                    <span class="text-muted" id="summary-cities-trend">əvvəlki dövrlə müqayisədə</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Reklam klikləri -->
    <div class="col-xl-3 col-md-6">
        <div class="card">
            <div class="card-body">
                <a href="{{route("gopanel.analytics.detail.ad.platforms")}}" data-detail-link class="text-muted mb-2 d-block">Reklam klikləri</a>
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h4 class="mb-0" id="summary-adclicks">0</h4>
                    </div>
                    <div class="flex-shrink-0">
                        <div id="chart-adclicks" class="apex-charts" style="height: 50px; width: 100px;"></div>
                    </div>
                </div>
                <div class="mt-3">
                    <span id="summary-adclicks-badge" class="badge bg-soft-success text-success">
                        <span id="summary-adclicks-change">0%</span>
                    </span>
                    <span class="text-muted" id="summary-adclicks-trend">əvvəlki dövrlə müqayisədə</span>
                </div>
            </div>
        </div>
    </div>

</div>
