@extends('gopanel.layouts.main')
@section('content')
<div class="page-content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0 font-size-18">Reklam parametrlər üzrə statistika</h4>
                    <div class="page-title-right d-flex align-items-center gap-2">
                        <button class="btn btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#analyticsFilterPanel">
                            <i class="fas fa-filter"></i> Filter
                        </button>
                        <a href="{{ route('gopanel.analytics.index', request()->only(['from','to'])) }}" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left"></i> Analitikə dön
                        </a>
                    </div>
                </div>
            </div>
        </div>
        @include('gopanel.pages.analytics.partials.detail_filter')
        <div class="row">
            <div class="col-xl-12">
                <div class="card">
                    <div class="card-body">
                        @include('gopanel.component.datatable',[
                            '__datatableName' => 'gopanel.Analytics.ad_platform_data',
                            '__datatableId' => 'ad_platform_data'
                        ])
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@push('scripts')
    <script src="{{asset("/assets/gopanel/js/modules/analytics.js?v=" . time())}}"></script>
@endpush