@extends('gopanel.layouts.main')
@section('content')
@include('gopanel.pages.settings.partials.sortable-styles')
<div class="page-content">
    <div class="container-fluid">

        <!-- start page title -->
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0 font-size-18">Menyu</h4>

                    <div class="page-title-right d-flex align-items-center gap-2">
                        @if ($parent)
                            <a class="btn btn-outline-secondary" href="{{ route('gopanel.settings.menu.index', ['parent_id' => $parent->parent_id]) }}">
                                <i class="fas fa-arrow-left"></i> Geri
                            </a>
                        @endif
                        @can('gopanel.settings.menu.add')
                        <a class="btn btn-success" href="{{ route('gopanel.settings.menu.store', ['parent_id' => $parent_id]) }}">
                            <i class="fas fa-plus"></i> Əlavə et
                        </a>
                        @endcan
                    </div>

                </div>
            </div>
        </div>
        <!-- end page title -->

        @if ($parent)
            <div class="row">
                <div class="col-12">
                    <div class="alert alert-info py-2">
                        <strong>{{ $parent->title }}</strong> menyusunun alt elementləri
                    </div>
                </div>
            </div>
        @endif

        <div class="row">
            <div class="col-xl-12">
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive sortable-shell" id="menu-shell">
                            <div class="sortable-overlay d-none" id="menu-overlay">
                                <div class="spinner-border text-primary" role="status" aria-hidden="true"></div>
                            </div>
                            <table class="table table-bordered align-middle mb-0" id="menu-table">
                                <thead>
                                    <tr>
                                        <th style="width:40px" class="text-center">#</th>
                                        <th style="width:60px">#</th>
                                        <th>Adı</th>
                                        <th>Alt Menyular</th>
                                        <th>Tip</th>
                                        <th>Mövqe</th>
                                        <th>Status</th>
                                        <th>Əməliyyat</th>
                                    </tr>
                                </thead>
                                <tbody id="menu-sortable" data-sort-url="{{ route('gopanel.settings.menu.sort') }}">
                                    @forelse ($menuList as $menu)
                                    @php
                                        $typeEnum = \App\Enums\Common\Menu\MenuTypeEnum::tryFrom($menu->type);
                                        $posEnum  = \App\Enums\Common\Menu\MenuPositionEnum::tryFrom($menu->position);
                                    @endphp
                                    <tr id="item_{{ $menu->id }}" data-id="{{ $menu->id }}">
                                        <td class="text-center">
                                            @can('gopanel.settings.menu.sort')
                                            <span class="drag-handle menu-row-drag-handle" role="button" aria-label="Sırala {{ $menu->title }}">
                                                <i class="fas fa-grip-vertical"></i>
                                            </span>
                                            @endcan
                                        </td>
                                        <th scope="row">{{ $loop->iteration }}</th>
                                        <td><strong>{{ $menu->title ?: '—' }}</strong></td>
                                        <td>
                                            <a href="{{ route('gopanel.settings.menu.index', ['parent_id' => $menu->id]) }}">
                                                Alt Menyular [{{ $menu->children_admin_count }}]
                                            </a>
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $typeEnum?->className() ?? 'secondary' }}">
                                                {{ $typeEnum?->label() ?? $menu->type }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $posEnum?->className() ?? 'secondary' }}">
                                                {{ $posEnum?->label() ?? $menu->position }}
                                            </span>
                                        </td>
                                        <td>{!! app('gopanel')->toggle_btn($menu, 'is_active', $menu->is_active == 1) !!}</td>
                                        <td>
                                            <div class="d-flex gap-1">
                                                @can('gopanel.settings.menu.edit')
                                                <a href="{{ route('gopanel.settings.menu.store', $menu) }}" class="btn btn-outline-success waves-effect waves-light" data-bs-toggle="tooltip" data-bs-placement="top" title="Məlumata düzəliş et">
                                                    <i class="fas fa-pen f-20"></i>
                                                </a>
                                                @endcan
                                                @can('gopanel.settings.menu.delete')
                                                <a href="{{ route('gopanel.general.delete', $menu) }}" class="btn btn-outline-danger waves-effect waves-light delete" data-url="{{ route('gopanel.general.delete', $menu) }}" data-key="{{ get_class($menu) }}" data-bs-toggle="tooltip" data-bs-placement="top" title="Məlumatı sil">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-4">Menyu elementi yoxdur.</td>
                                    </tr>
                                    @endforelse
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
@endsection

@push('scripts')
<script src="{{ asset('/assets/gopanel/js/modules/menus.js?v=' . time()) }}"></script>
@endpush
