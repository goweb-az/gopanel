@extends('gopanel.layouts.main')
@section('content')
<div class="page-content">
    <div class="container-fluid">

        <!-- start page title -->
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0 font-size-18">Əməliyyat Logları</h4>

                    <div class="page-title-right d-flex align-items-center gap-2">
                        @if(auth('gopanel')->user()->can('gopanel.activity.activity-logs.cleanup'))
                        <button class="btn btn-outline-danger" id="cleanup-btn" data-url="{{ route('gopanel.activity.activity-logs.cleanup') }}">
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
        @include('gopanel.pages.activity.activity_logs.inc.filter')
        <div class="row">
            <div class="col-xl-12">
                <div class="card">
                    <div class="card-body">
                    
                        @include('gopanel.component.datatable',[
                            '__datatableName' => 'gopanel.activity.activityLog',
                            '__datatableId' => 'histories'
                        ])
                    </div>
                </div>
            </div><!--end col-->
        </div>

    </div> <!-- container-fluid -->
</div>
<!-- End Page-content -->
@include('gopanel.pages.activity.activity_logs.inc.modal')
@endsection
@push('styles')
<link rel="stylesheet" href="{{asset('/assets/gopanel/libs/select2/css/select2.min.css')}}">
<link rel="stylesheet" href="{{asset('/assets/gopanel/libs/toastr/build/toastr.min.css')}}">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/jquery.json-viewer@1.5.0/json-viewer/jquery.json-viewer.css">
<style>
    .json-document { font-size: 13px; }
    .json-viewer-section { border: 1px solid #e9ecef; border-radius: 6px; margin-bottom: 1rem; }
    .json-viewer-section .section-header {
        background: #f8f9fa; padding: 10px 15px; cursor: pointer;
        display: flex; justify-content: space-between; align-items: center;
        border-bottom: 1px solid #e9ecef; border-radius: 6px 6px 0 0;
    }
    .json-viewer-section .section-header:hover { background: #e9ecef; }
    .json-viewer-section .section-body { padding: 15px; }
    .json-viewer-section.collapsed .section-body { display: none; }
    .json-viewer-section.collapsed .section-header { border-radius: 6px; border-bottom: none; }
    .log-meta-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 12px; }
    .log-meta-item { background: #f8f9fa; padding: 10px 14px; border-radius: 6px; }
    .log-meta-item .label { font-size: 11px; color: #6c757d; text-transform: uppercase; margin-bottom: 2px; }
    .log-meta-item .value { font-size: 14px; font-weight: 500; }
    .copy-json-btn { font-size: 12px; }
</style>
@endpush
@push('scripts')
<script src="{{asset('/assets/gopanel/libs/select2/js/select2.min.js')}}"></script>
<script src="{{asset('/assets/gopanel/libs/toastr/build/toastr.min.js')}}"></script>
<script src="https://cdn.jsdelivr.net/npm/jquery.json-viewer@1.5.0/json-viewer/jquery.json-viewer.js"></script>
<script src="{{asset("/assets/gopanel/js/modules/activity/activity-logs.js?v=".time())}}"></script>
@endpush
