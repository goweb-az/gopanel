@extends('gopanel.layouts.main')
@section('content')
<div class="page-content">
    <div class="container-fluid">

        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0 font-size-18">Məhsul {{ is_null($item->id) ? 'əlavə et' : 'redaktə' }}</h4>

                    <div class="page-title-right">
                        <a class="btn btn-success" href="{{ route('gopanel.products.index') }}">
                            <i class="fas fa-arrow-left"></i> Geri dön
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-12">
                <div class="card">
                    <div class="card-body">
                        @include('gopanel.pages.products.partials.form')
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection
@push('scripts')
    <script src="{{ asset('/assets/gopanel/libs/ckeditor/ckeditor.js') }}"></script>
    <script src="{{ asset('/assets/gopanel/js/modules/products.js?v=' . time()) }}"></script>
@endpush
