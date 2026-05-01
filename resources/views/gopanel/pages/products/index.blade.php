@extends('gopanel.layouts.main')
@section('content')
<div class="page-content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <div>
                        <h4 class="mb-sm-0 font-size-18">Məhsullar</h4>
                        <p class="text-muted mb-0 mt-1">Məhsulları başlıq, qiymət, endirim və status ilə birlikdə buradan idarə edə bilərsiniz.</p>
                    </div>

                    <div class="page-title-right">
                        @can('gopanel.products.add')
                        <a class="btn btn-success" href="{{ route('gopanel.products.store') }}">
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
                            '__datatableName' => 'gopanel.product',
                            '__datatableId'   => 'products'
                        ])
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
