@extends('gopanel.layouts.main')
@section('content') 
<div class="page-content">
    <div class="container-fluid">
        <!-- start page title -->
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0 font-size-18">Tərcümələr </h4>

                    <div class="page-title-right">

                        @isset($_GET['locale'])
                            <a class="btn btn-warning" href="{{route("gopanel.translations.index")}}">
                                All
                            </a>
                        @endisset

                        @foreach ($languages as $language)
                            <a class="btn btn-primary {{$language->code == $locale ? 'active' : ''}}" href="{{route("gopanel.translations.index",['locale' => $language->code])}}">
                                {{$language->code}}
                            </a>
                        @endforeach

                        <button class="btn btn-success" id="open-create-modal" data-route="{{route("gopanel.translations.get.form")}}">
                            <i class="fas fa-plus"></i> Əlavə et
                        </button>
                    </div>

                </div>
            </div>
        </div>
        <!-- end page title -->

        <div class="row">
            <div class="col-xl-12">
                <div class="card">
                    <div class="card-body">
                        @include('gopanel.component.datatable',[
                            '__datatableName' => 'gopanel.translations.translation',
                            '__datatableId' => 'translation'
                        ])
                    </div>
                </div>
            </div><!--end col-->
        </div>
        
    </div> <!-- container-fluid -->
</div>
<!-- End Page-content -->
@include('gopanel.pages.translations.inc.modal')
@endsection