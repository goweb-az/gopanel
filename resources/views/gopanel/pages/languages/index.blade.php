@extends('gopanel.layouts.main')
@section('content') 
<div class="page-content">
    <div class="container-fluid">

        <!-- start page title -->
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0 font-size-18">Dillər</h4>

                    <div class="page-title-right">
                        <button class="btn btn-success" id="open-create-modal" data-route="{{route("gopanel.languages.get.form")}}">
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
                        <div class="table-responsive">
                            <table class="table table-bordered mb-0">

                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Adı</th>
                                        <th>Kodu</th>
                                        <th>Ölkəsi</th>
                                        <th>manage</th>
                                    </tr>
                                </thead>
                                <tbody class="sortable" 
                                        data-key="{{ get_class($languagesList->first()) }}" 
                                        data-row="sort_order"
                                        data-url="{{route("gopanel.general.sortable")}}">
                                    @foreach ($languagesList as $language)
                                        <tr id="item_{{$language->id}}">
                                            <th scope="row">{{$loop->iteration}}</th>
                                            <td>{{$language->name}}</td>
                                            <td>{{$language->code}}</td>
                                            <td>{{$language?->country?->name}}</td>
                                            <td>{!! app('gopanel')->is_active_btn($language, "is_active", ($language->is_active == "1" ? true : false)) !!}</td>
                                            <td>
                                                <a href="{{route("gopanel.languages.get.form", $language)}}" class="btn btn-outline-success waves-effect waves-light edit" data-bs-toggle="tooltip" data-bs-placement="top" title="Məlumata düzəliş et"> 
                                                    <i class="fas fa-pen f-20"></i> 
                                                </a>
                                                <a href="{{route("gopanel.general.delete", $language)}}" class="btn btn-outline-danger waves-effect waves-light delete" data-url="" data-key="{{$language->getTable()}}" data-bs-toggle="tooltip" data-bs-placement="top" title="Məlumatı sil"> 
                                                    <i class="fas fa-trash"></i> 
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div><!--end col-->
        </div>
        
    </div> <!-- container-fluid -->
</div>
<!-- End Page-content -->
@include('gopanel.pages.languages.inc.modal')
@endsection