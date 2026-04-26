@extends('gopanel.layouts.main')
@section('content') 
<div class="page-content">
    <div class="container-fluid">

        <!-- start page title -->
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0 font-size-18">Klikləmələr üzrə statistikalar</h4>

                    <div class="page-title-right">
                        @if (count($_GET))
                            <a href="{{ route('gopanel.analytics.detail.clicks') }}" class="btn btn-outline-danger">
                                <i class="fas fa-times-circle"></i> Filteri Sil
                            </a>
                        @endif
                            <button class="btn btn-outline-primary" id="openFilter">
                                @if (count($_GET))
                                    <i class="fas fa-times"></i> Filteri bağla
                                @else
                                    <i class="fas fa-filter"></i> Filteri aç
                                @endif
                            </button>
                            <a href="{{ route('gopanel.analytics.index', request()->only(['from','to'])) }}" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left"></i> Analitikə dön
                            </a>
                    </div>

                </div>
            </div>
        </div>
        <!-- end page title -->

        <div class="row" id="filterWrapper" @if (count($_GET) == 0) style="display:none;" @endif>
            <div class="col-xl-12">
                <div class="card">
                    <div class="card-body">
                        <form action="{{ route('gopanel.analytics.detail.clicks', $_GET) }}" method="GET">
                            <div class="row">
                                <div class="col-lg-2 col-md-2 col-sm-12 col-xs-12 mb-3">
                                    <div class="form_group">
                                        <label class="form-label" for="device_id">Cihazlar</label>
                                        <select class="form-select" name="device_id" id="device_id">
                                            <option value="">Bütün cihazlar</option>
                                            @foreach ($devices as $device)
                                                <option value="{{$device->id}}" @selected($device->id == request()->device_id)>{{$device->device_type}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-2 col-md-2 col-sm-12 col-xs-12 mb-3">
                                    <label class="form-label" for="os_id"> Əməliyyat sistemi</label>
                                    <select name="os_id" class="form-select" id="os_id">
                                        <option value="">Bütün əməliyyat sistemləri</option>
                                        @foreach ($operations as $os)
                                        <option value="{{$os->id}}" @selected($os->id == request()->os_id)>{{$os->name}}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-lg-2 col-md-2 col-sm-12 col-xs-12 mb-3">
                                    <label class="form-label" for="browser_id"> Brauzerlər</label>
                                    <select name="browser_id" class="form-select" id="browser_id">
                                        <option value="">Bütün brauzerlər</option>
                                        @foreach ($browsers as $browser)
                                            <option value="{{$browser->id}}" @selected($browser->id == request()->browser_id)>{{$browser->name}}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-lg-2 col-md-2 col-sm-12 col-xs-12 mb-3">
                                    <label for="from_date" class="form-label">Başlama</label>
                                    <input type="date" class="form-control" name="from" id="from_date" placeholder="" value="{{request()->from}}">
                                </div>

                                <div class="col-lg-2 col-md-2 col-sm-12 col-xs-12 mb-3">
                                    <label for="to_date" class="form-label">Bitmə</label>
                                    <input type="date" class="form-control" name="to" id="to_date" placeholder="" value="{{request()->to}}">
                                </div>

                                <div class="col-lg-2 col-md-2 col-sm-12 col-xs-12 mb-3 filter-btn-div">
                                    <button type="submit" class="btn btn-success filter-btn">
                                        <i class="fa fa-search"></i> Axtar
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-12">
                <div class="card">
                    <div class="card-body">
                        {{-- <h4 class="card-title">Striped columns</h4>
                        <p class="card-title-desc">Use <code>.table-striped-columns</code> to add zebra-striping to any table column.</p> --}}
                        @include('gopanel.component.datatable',[
                            '__datatableName' => 'gopanel.Analytics.clicks',
                            '__datatableId' => 'blog'
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
    <script src="{{asset("/assets/gopanel/js/modules/analytics.js?=" . time())}}"></script>
@endpush