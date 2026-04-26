@extends('gopanel.layouts.main')
@section('content') 
<div class="page-content">
    <div class="container-fluid">

        <!-- start page title -->
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0 font-size-18">Analitika</h4>

                    <div class="page-title-right">
                        {{-- <button class="btn btn-success" id="open-create-modal" data-route="{{route("gopanel.site.menu.get.form")}}">
                            <i class="fas fa-plus"></i> Əlavə et
                        </button> --}}
                    </div>

                </div>
            </div>
        </div>
        <!-- end page title -->
        @include("gopanel.pages.analytics.partials.clicks")
        @include("gopanel.pages.analytics.partials.links")
        @include("gopanel.pages.analytics.partials.countries")
        @include("gopanel.pages.analytics.partials.city")
        @include("gopanel.pages.analytics.partials.devices")
        @include("gopanel.pages.analytics.partials.borowsers")
        @include("gopanel.pages.analytics.partials.operating")
        @include("gopanel.pages.analytics.partials.languages")
        @include("gopanel.pages.analytics.partials.adplatforms")
        
    </div> <!-- container-fluid -->
</div>
<!-- End Page-content -->
@endsection
@push('scripts')
    <script src="/assets/gopanel/js/modules/analytics.js?={{time()}}"></script>
@endpush