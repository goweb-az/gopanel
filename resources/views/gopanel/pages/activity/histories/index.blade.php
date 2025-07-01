@extends('gopanel.layouts.main')
@section('content')
<div class="page-content">
    <div class="container-fluid">

        <!-- start page title -->
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0 font-size-18">Tarixçə</h4>

                    @if (count($_GET))
                    <div class="page-title-right">
                        <a class="btn btn-outline-primary" href="{{route("gopanel.activity.history.index")}}">
                            <i class="fas fa-trash-restore-alt"></i> Filteri sıfırla
                        </a>
                    </div>
                    @endif

                </div>
            </div>
        </div>
        <!-- end page title -->
        @include('gopanel.pages.activity.histories.inc.filter')
        <div class="row">
            <div class="col-xl-12">
                <div class="card">
                    <div class="card-body">
                    
                        @include('gopanel.component.datatable',[
                            '__datatableName' => 'gopanel.activity.history',
                            '__datatableId' => 'histories'
                        ])
                    </div>
                </div>
            </div><!--end col-->
        </div>

    </div> <!-- container-fluid -->
</div>
<!-- End Page-content -->
@include('gopanel.pages.activity.histories.inc.modal')
@endsection
@push('scripts')
<script src="{{asset("assets/gopanel/libs/json-viewer/jquery.json-viewer.js")}}"></script>
<script src="{{asset('/assets/gopanel/js/modules/activity.js?v='.time())}}"></script>
@endpush
