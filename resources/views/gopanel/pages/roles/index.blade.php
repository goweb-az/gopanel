@extends('gopanel.layouts.main')
@section('content')
<div class="page-content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <div>
                        <h4 class="mb-sm-0 font-size-18">Vəzifələr</h4>
                        <p class="text-muted mb-0 mt-1">Admin rollarını və onlara təyin olunmuş icazələri buradan idarə edə bilərsiniz.</p>
                    </div>

                    <div class="page-title-right">
                        @can('gopanel.admins.roles.add')
                        <a class="btn btn-success" href="{{ route('gopanel.admins.roles.store') }}">
                            <i class="fas fa-plus"></i> Əlavə et
                        </a>
                        @endcan
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-12">
                <div class="card">
                    <div class="card-body">
                        @include('gopanel.component.datatable',[
                            '__datatableName' => 'gopanel.admins.role',
                            '__datatableId' => 'roles'
                        ])
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@push('scripts')
    <script src="{{ asset('assets/gopanel/js/modules/admins.js?=' . time()) }}"></script>
@endpush
