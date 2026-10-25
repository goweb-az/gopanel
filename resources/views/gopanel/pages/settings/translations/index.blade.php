@extends('gopanel.layouts.main')
@section('content')
<div class="page-content">
    <div class="container-fluid">
        <div id="translation-page"
             data-pages='@json($allPages)'
             data-export-url="{{ route('gopanel.settings.translations.export-json') }}"
             data-excel-import-url="{{ route('gopanel.settings.translations.excel.import') }}">

        <!-- start page title -->
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0 font-size-18">Tərcümələr</h4>

                    <div class="page-title-right translation-toolbar">
                        @can('gopanel.settings.translations.add')
                        <button class="btn btn-success" id="open-create-modal" data-route="{{ route('gopanel.settings.translations.get.form') }}">
                            <i class="fas fa-plus"></i> Əlavə et
                        </button>
                        @endcan

                        @can('gopanel.settings.translations.export')
                        <a class="btn btn-outline-success" href="{{ $excelExportUrl }}" title="Cari platforma/səhifə filtrinə görə">
                            <i class="fas fa-file-excel"></i> Excel-ə çıxar
                        </a>
                        @endcan

                        @can('gopanel.settings.translations.import')
                        <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#translation-excel-modal">
                            <i class="bx bx-import"></i> Excel-dən yüklə
                        </button>
                        @endcan

                        @can('gopanel.settings.translations.export')
                        <button class="btn btn-outline-secondary" id="translation-export" type="button" title="database/seeders/json-data/translations/ altına seeder faylları yazır">
                            <span class="button-label"><i class="bx bx-export"></i> JSON-a çıxar</span>
                            <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                        </button>
                        @endcan

                        <button class="btn {{ $hasFilters ? 'btn-dark' : 'btn-secondary' }}" type="button"
                                data-bs-toggle="collapse" data-bs-target="#translation-filter-panel"
                                aria-expanded="{{ $hasFilters ? 'true' : 'false' }}" aria-controls="translation-filter-panel">
                            <i class="fas fa-filter"></i> Filter
                        </button>
                    </div>

                </div>
            </div>
        </div>
        <!-- end page title -->

        <!-- filter panel -->
        <div class="row">
            <div class="col-12">
                <div class="collapse {{ $hasFilters ? 'show' : '' }}" id="translation-filter-panel">
                    <div class="card">
                        <div class="card-body">
                            <div class="row g-3 align-items-end">
                                <div class="col-md-4">
                                    <label class="form-label">Dil</label>
                                    <select id="filter-locale" class="form-select translation-filter-select" data-placeholder="Dil seçin...">
                                        <option></option>
                                        @foreach ($languages as $language)
                                            <option value="{{ $language->code }}" @selected(($filters['locale'] ?? '') === $language->code)>{{ $language->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label">Platforma</label>
                                    <select id="filter-platform" class="form-select translation-filter-select" data-placeholder="Platforma seçin...">
                                        <option></option>
                                        @foreach (array_keys($allPages) as $platformKey)
                                            <option value="{{ $platformKey }}" @selected(($filters['platform'] ?? '') === $platformKey)>{{ $platformKey }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label">Səhifə</label>
                                    <select id="filter-page" class="form-select translation-filter-select" data-placeholder="Səhifə seçin...">
                                        <option></option>
                                        @foreach ($filterPages as $pageValue => $pageLabel)
                                            <option value="{{ $pageValue }}" @selected(($filters['page'] ?? '') === $pageValue)>{{ $pageLabel }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-1 d-flex gap-2">
                                    <button type="button" class="btn btn-primary" id="translation-filter-apply">
                                        <i class="fas fa-search"></i> Filtrlə
                                    </button>
                                    <a href="{{ route('gopanel.settings.translations.index') }}" class="btn btn-light">Sıfırla</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

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
        </div><!-- #translation-page -->
    </div> <!-- container-fluid -->
</div>
<!-- End Page-content -->
@include('gopanel.pages.settings.translations.inc.modal')
@can('gopanel.settings.translations.import')
@include('gopanel.pages.settings.translations.modals.excel-import')
@endcan
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset_v('assets/gopanel/css/translations.css') }}">
@endpush

@push('scripts')
<script src="{{ asset_v('assets/gopanel/js/modules/translations.js') }}"></script>
@endpush
