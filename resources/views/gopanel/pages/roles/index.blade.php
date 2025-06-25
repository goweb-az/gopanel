@extends('gopanel.layouts.main')
@section('content') 
<div class="page-content">
    <div class="container-fluid">

        <!-- start page title -->
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0 font-size-18">Vəzifələr</h4>

                    <div class="page-title-right">
                        <a class="btn btn-success" href="{{route("gopanel.admins.roles.store")}}">
                            <i class="fas fa-plus"></i> Əlavə et
                        </a>
                    </div>

                </div>
            </div>
        </div>
        <!-- end page title -->

        <div class="row">
            <div class="col-xl-12">
                <div class="card">
                    <div class="card-body">
                        {{-- <h4 class="card-title">Striped columns</h4>
                        <p class="card-title-desc">Use <code>.table-striped-columns</code> to add zebra-striping to any table column.</p> --}}
                        @include('gopanel.component.datatable',[
                            '__datatableName' => 'gopanel.admins.role',
                            '__datatableId' => 'roles'
                        ])
                    </div>
                </div>
            </div><!--end col-->
        </div>
        
    </div> <!-- container-fluid -->
</div>
<!-- End Page-content -->
@endsection
@push('scripts')
    <script src="{{asset("assets/gopanel/js/modules/admins.js")}}"></script>
@endpush