@extends('gopanel.layouts.main')
@section('content')
<style>
    /* Keep select2 dropdowns above cards and modals so they always open on top. */
    .select2-container { z-index: 1; }
    .select2-container--open { z-index: 1060; }
    .select2-container--open .select2-dropdown { z-index: 1060; }
    #bulk-import-modal .select2-container--open,
    #bulk-import-modal .select2-container--open .select2-dropdown { z-index: 1065; }
    /* Match select2 single height to Bootstrap form controls. */
    .select2-container .select2-selection--single { height: calc(1.5em + .75rem + 2px); }
    .select2-container--default .select2-selection--single .select2-selection__rendered { line-height: calc(1.5em + .75rem); }
    .select2-container--default .select2-selection--single .select2-selection__arrow { height: calc(1.5em + .75rem); }
</style>
<div class="page-content">
    <div class="container-fluid">
        <div id="translation-page"
             data-pages='@json($allPages)'
             data-export-url="{{ route('gopanel.settings.translations.export-json') }}"
             data-import-url="{{ route('gopanel.settings.translations.bulk-import') }}">

        @php
            $hasFilters = !empty($filters['locale']) || !empty($filters['platform']) || !empty($filters['page']);
        @endphp

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

                        @can('gopanel.settings.translations.import')
                        <button class="btn btn-primary" id="translation-bulk-open" type="button" data-bs-toggle="modal" data-bs-target="#bulk-import-modal">
                            <i class="bx bx-import"></i> Toplu idxal
                        </button>
                        @endcan

                        @can('gopanel.settings.translations.export')
                        <button class="btn btn-outline-secondary" id="translation-export" type="button">
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
                                        @php $filterPlatform = $filters['platform'] ?? null; @endphp
                                        @if ($filterPlatform && isset($allPages[$filterPlatform]))
                                            @foreach ($allPages[$filterPlatform] as $pageValue => $pageLabel)
                                                <option value="{{ $pageValue }}" @selected(($filters['page'] ?? '') === $pageValue)>{{ $pageLabel }}</option>
                                            @endforeach
                                        @endif
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
@include('gopanel.pages.settings.translations.modals.bulk-import')
@endsection

@push('scripts')
<script src="{{asset("/assets/gopanel/js/modules/translations.js?v=" . time())}}"></script>
@endpush
