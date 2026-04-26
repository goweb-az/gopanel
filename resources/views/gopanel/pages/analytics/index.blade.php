@extends('gopanel.layouts.main')
@section('content')
<div class="page-content" id="analyticsWrapperDahboard" style="position:relative;">
    <!-- Loader Overlay (inside content only) -->
    <div class="analytics-loader-overlay" id="analyticsLoader">
        <div class="analytics-loader-spinner"></div>
    </div>

    <div class="container-fluid">

        <!-- Page title + Date Range + Filter Button -->
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-flex align-items-center justify-content-between">
                    <h4 class="mb-0">Analitika</h4>
                    <div class="d-flex align-items-center gap-2">
                        <div id="analyticsDateRange" class="btn btn-light border d-flex align-items-center gap-2" style="cursor:pointer; min-width:220px;">
                            <i class="bx bx-calendar font-size-16"></i>
                            <span id="dateRangeLabel">{{ \Carbon\Carbon::parse($dateFrom)->format('d/m/Y') }} – {{ \Carbon\Carbon::parse($dateTo)->format('d/m/Y') }}</span>
                            <i class="bx bx-chevron-down ms-auto"></i>
                        </div>
                        <button class="btn btn-outline-secondary" type="button" id="toggleFilterBtn" data-bs-toggle="collapse" data-bs-target="#analyticsFilterPanel">
                            @if(request()->hasAny(['country_id','city_id','browser_id','device_id']))
                                <i class="fas fa-times"></i> Filteri bağla
                            @else
                                <i class="fas fa-filter"></i> Filter
                            @endif
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter Panel -->
        <div class="collapse mb-3 {{ request()->hasAny(['country_id','city_id','browser_id','device_id']) ? 'show' : '' }}" id="analyticsFilterPanel">
            <div class="card card-body analytics-filter-panel">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">Ölkə</label>
                        <select class="form-select" id="filter-country" style="width:100%;">
                            <option value="">Hamısı</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Şəhər</label>
                        <select class="form-select" id="filter-city" style="width:100%;">
                            <option value="">Hamısı</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Brauzer</label>
                        <select class="form-select" id="filter-browser">
                            <option value="">Hamısı</option>
                            @foreach($allBrowsers as $b)
                                <option value="{{ $b->id }}" @selected(request('browser_id') == $b->id)>{{ $b->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Cihaz</label>
                        <select class="form-select" id="filter-device">
                            <option value="">Hamısı</option>
                            @foreach($allDevices as $d)
                                <option value="{{ $d->id }}" @selected(request('device_id') == $d->id)>{{ $d->device_type }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-auto d-flex align-items-end gap-2">
                        <button type="button" class="btn btn-primary" id="applyAnalyticsFilters">
                            <i class="fas fa-search"></i> Filtrlə
                        </button>
                        <button type="button" class="btn btn-light" id="clearAnalyticsFilters">Sıfırla</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Summary cards -->
        @include("gopanel.pages.analytics.partials.summary")

        <!-- Row: Browsers & Devices -->
        <div class="row">
            @include("gopanel.pages.analytics.partials.borowsers")
            @include("gopanel.pages.analytics.partials.devices")
        </div>

        <!-- Row: Countries & Cities -->
        <div class="row">
            @include("gopanel.pages.analytics.partials.countries")
            @include("gopanel.pages.analytics.partials.city")
        </div>

        <!-- Row: Ad Platforms -->
        @include("gopanel.pages.analytics.partials.adplatforms")

        <div class="row">
            @include("gopanel.pages.analytics.partials.languages")
            @include("gopanel.pages.analytics.partials.operatings")
        </div>

        @include("gopanel.pages.analytics.partials.utms")

        <!-- Latest Clicks -->
        @include("gopanel.pages.analytics.partials.clicks")
        @include("gopanel.pages.analytics.partials.links")

    </div>
</div>
@endsection

@push('styles')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
    <link rel="stylesheet" href="{{asset('/assets/gopanel/libs/select2/css/select2.min.css')}}">
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/moment@2.30.1/moment.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="{{asset("/assets/gopanel/libs/apexcharts/apexcharts.min.js")}}"></script>
    <script src="{{asset("/assets/gopanel/libs/chart.js/Chart.bundle.min.js")}}"></script>
    <script src="{{asset('/assets/gopanel/libs/select2/js/select2.min.js')}}"></script>

    <script>
        var analyticsConfig = {
            dateFrom: '{{ $dateFrom }}',
            dateTo: '{{ $dateTo }}',
            routes: {
                topHits: '/gopanel/analytics/get/top-hits',
                countriesMap: '/gopanel/analytics/get/countries-map',
                citiesChart: '/gopanel/analytics/get/cities-chart',
                languagesChart: '/gopanel/analytics/get/languages-chart',
                osChart: '/gopanel/analytics/get/os-chart',
                searchCountries: '/gopanel/analytics/api/countries',
                searchCities: '/gopanel/analytics/api/cities',
            }
        };
        var deviceLabels = {!! json_encode($deviceLabels) !!};
        var deviceHits   = {!! json_encode($deviceHits) !!};
        var browserLabels = {!! json_encode($browserLabels) !!};
        var browserHits   = {!! json_encode($browserHits) !!};
    </script>

    <script src="{{asset("/assets/gopanel/js/modules/analytics.js?v=" . time())}}"></script>
@endpush
