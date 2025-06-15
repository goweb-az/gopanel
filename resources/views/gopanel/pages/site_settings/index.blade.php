@extends('gopanel.layouts.main')
@section('content') 
<div class="page-content">
    <div class="container-fluid">

        <!-- start page title -->
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0 font-size-18">Intro</h4>

                    <div class="page-title-right">
                        {{-- <button class="btn btn-success" id="open-create-modal" data-route="{{route("gopanel.site.menu.get.form")}}">
                            <i class="fas fa-plus"></i> Əlavə et
                        </button> --}}
                    </div>

                </div>
            </div>
        </div>
        <!-- end page title -->

        <div class="row">
            <div class="col-xl-12">
                <div class="card">
                    <div class="card-body">
                        @include("gopanel.pages.site_settings.partials.form")
                    </div>
                </div>
            </div><!--end col-->
        </div>
        
    </div> <!-- container-fluid -->
</div>
<!-- End Page-content -->
@endsection