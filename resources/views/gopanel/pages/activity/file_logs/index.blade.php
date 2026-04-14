@extends('gopanel.layouts.main')
@section('content')
<div class="page-content">
    <div class="container-fluid">

        <!-- start page title -->
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0 font-size-18">Sistem Logları</h4>

                    <div class="page-title-right d-flex align-items-center gap-2">
                        <a href="/log-viewer" target="_blank" class="btn btn-outline-primary">
                            <i class="fas fa-external-link-alt"></i> Log Viewer
                        </a>
                        @if(auth('gopanel')->user()->can('gopanel.activity.file-logs.cleanup'))
                        <button class="btn btn-outline-danger" id="cleanup-btn" data-url="{{ route('gopanel.activity.file-logs.cleanup') }}">
                            <i class="fas fa-broom"></i> Təmizlə
                        </button>
                        @endif
                        <button class="btn btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#filterPanel">
                            <i class="fas fa-filter"></i> Filter
                        </button>
                    </div>

                </div>
            </div>
        </div>
        <!-- end page title -->
        @include('gopanel.pages.activity.file_logs.inc.filter')
        <div class="row">
            <div class="col-xl-12">
                <div class="card">
                    <div class="card-body">
                        @include('gopanel.component.datatable',[
                            '__datatableName' => 'gopanel.activity.filelogs',
                            '__datatableId' => 'filelogs'
                        ])
                    </div>
                </div>
            </div><!--end col-->
        </div>

    </div> <!-- container-fluid -->
</div>
<!-- End Page-content -->
@include('gopanel.pages.activity.file_logs.inc.modal')
@endsection

@push('styles')
<link rel="stylesheet" href="{{asset('/assets/gopanel/libs/select2/css/select2.min.css')}}">
<link rel="stylesheet" href="{{asset('/assets/gopanel/libs/toastr/build/toastr.min.css')}}">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/jquery.json-viewer@1.5.0/json-viewer/jquery.json-viewer.css">
@endpush

@push('scripts')
<script src="{{asset('/assets/gopanel/libs/select2/js/select2.min.js')}}"></script>
<script src="{{asset('/assets/gopanel/libs/toastr/build/toastr.min.js')}}"></script>
<script src="https://cdn.jsdelivr.net/npm/jquery.json-viewer@1.5.0/json-viewer/jquery.json-viewer.js"></script>
<script src="{{asset("/assets/gopanel/js/modules/activity/file-logs.js?v=".time())}}"></script>
@endpush

