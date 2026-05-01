@extends('gopanel.layouts.main')
@section('content') 
<div class="page-content">
    <div class="container-fluid">

        <!-- start page title -->
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0 font-size-18">Kateqoriyalar</h4>

                    <div class="page-title-right">
                        @can('gopanel.categories.add')
                        <button class="btn btn-success" id="open-create-modal" data-route="{{route("gopanel.categories.get.form")}}">
                            <i class="fas fa-plus"></i> Əlavə et
                        </button>
                        @endcan
                    </div>

                </div>
            </div>
        </div>
        <!-- end page title -->

        <div class="row">
            <div class="col-xl-12">

                {{-- Parent categories sortable wrapper --}}
                <div id="parent-sortable" 
                     data-key="{{ $categories->isNotEmpty() ? get_class($categories->first()) : '' }}" 
                     data-row="sort_order"
                     data-url="{{route("gopanel.general.sortable")}}"
                     data-move-url="{{ route('gopanel.categories.move') }}">

                @foreach($categories as $parent)
                <div class="card mb-3" id="item_{{$parent->id}}">
                    <div class="card-header bg-white py-3" style="cursor: default;">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center">
                                <div class="parent-drag-handle me-2" style="cursor: grab;">
                                    <i class="fas fa-grip-vertical text-muted"></i>
                                </div>
                                <span class="badge rounded-pill me-2 d-inline-flex align-items-center justify-content-center" style="background-color: {{ $parent->color ?? '#6c757d' }}; font-size: 13px; padding: 5px 8px; min-width: 26px; min-height: 26px;">
                                    @if($parent->icon)
                                        @if($parent->icon_type?->value === 'image')
                                            <img src="{{ asset($parent->icon) }}" alt="icon" style="width:16px;height:16px;object-fit:contain;">
                                        @else
                                            <i class="{{ $parent->icon }}"></i>
                                        @endif
                                    @else
                                        <i class="fas fa-folder"></i>
                                    @endif
                                </span>
                                <h6 class="mb-0 fw-bold">{{ $parent->getTranslation('name', app()->getLocale(), true) ?? '—' }}</h6>
                            </div>
                            <div class="d-flex align-items-center gap-1 flex-shrink-0 ms-3">
                                {!! app('gopanel')->toggle_btn($parent, "is_active", ($parent->is_active == "1" ? true : false)) !!}
                                @can('gopanel.categories.edit')
                                <a href="{{route("gopanel.categories.get.form", $parent)}}" class="btn btn-sm btn-outline-success waves-effect waves-light edit" data-bs-toggle="tooltip" title="Düzəliş et"> 
                                    <i class="fas fa-pen"></i> 
                                </a>
                                @endcan
                                @can('gopanel.categories.delete')
                                <a href="javascript:void(0)" class="btn btn-sm btn-outline-danger waves-effect waves-light delete" data-url="{{route("gopanel.general.delete", $parent)}}" data-key="{{ get_class($parent) }}" data-bs-toggle="tooltip" title="Sil"> 
                                    <i class="fas fa-trash"></i> 
                                </a>
                                @endcan
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="category-children-wrap">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 20px;" class="text-center"></th>
                                        <th style="width: 40px;" class="text-center">#</th>
                                        <th style="width: 50px;" class="text-center">İkon</th>
                                        <th>Başlıq</th>
                                        <th>Slug</th>
                                        <th style="width: 60px;" class="text-center">Sıra</th>
                                        <th style="width: 80px;" class="text-center">Status</th>
                                        <th style="width: 100px;" class="text-center">Əməliyyat</th>
                                    </tr>
                                </thead>
                                <tbody class="category-sortable sortable" 
                                        data-parent-id="{{ $parent->id }}"
                                        data-key="{{ get_class($parent) }}" 
                                        data-row="sort_order"
                                        data-url="{{route("gopanel.general.sortable")}}">
                                    @foreach ($parent->children as $child)
                                        <tr id="item_{{$child->id}}">
                                            <td style="cursor: grab; text-align: center; vertical-align: middle;">
                                                <i class="fas fa-grip-vertical text-muted"></i>
                                            </td>
                                            <td class="text-center">{{$loop->iteration}}</td>
                                            <td class="text-center">
                                                @if($child->icon)
                                                    @if($child->icon_type?->value === 'image')
                                                        <img src="{{ asset($child->icon) }}" alt="icon" style="width:20px;height:20px;object-fit:contain;">
                                                    @else
                                                        <i class="{{ $child->icon }}" style="color: {{ $child->color ?? '#333' }}; font-size: 16px;"></i>
                                                    @endif
                                                @else
                                                    <i class="fas fa-tag text-muted"></i>
                                                @endif
                                            </td>
                                            <td><strong>{{ $child->getTranslation('name', app()->getLocale(), true) ?? '—' }}</strong></td>
                                            <td><span class="text-primary">{{ $child->getTranslation('slug', 'en', true) ?? '—' }}</span></td>
                                            <td class="text-center">{{ $child->sort_order }}</td>
                                            <td class="text-center">
                                                {!! app('gopanel')->toggle_btn($child, "is_active", ($child->is_active == "1" ? true : false)) !!}
                                            </td>
                                            <td class="text-center">
                                                @can('gopanel.categories.edit')
                                                <a href="{{route("gopanel.categories.get.form", $child)}}" class="btn btn-sm btn-outline-success waves-effect waves-light edit" data-bs-toggle="tooltip" title="Düzəliş et"> 
                                                    <i class="fas fa-pen"></i> 
                                                </a>
                                                @endcan
                                                @can('gopanel.categories.delete')
                                                <a href="javascript:void(0)" class="btn btn-sm btn-outline-danger waves-effect waves-light delete" data-url="{{route("gopanel.general.delete", $child)}}" data-key="{{ get_class($child) }}" data-bs-toggle="tooltip" title="Sil"> 
                                                    <i class="fas fa-trash"></i> 
                                                </a>
                                                @endcan
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        </div>
                    </div>
                </div>
                @endforeach
                </div>{{-- end #parent-sortable --}}

                @if($categories->isEmpty())
                    <div class="card">
                        <div class="card-body text-center py-5">
                            <i class="fas fa-folder-open fa-3x text-muted mb-3"></i>
                            <p class="text-muted">Heç bir kateqoriya tapılmadı</p>
                        </div>
                    </div>
                @endif

            </div><!--end col-->
        </div>
        
    </div> <!-- container-fluid -->
</div>
<!-- End Page-content -->
@include('gopanel.pages.categories.inc.modal')
@include('gopanel.component.icon-picker-modal')

@push('scripts')
<style>
    .category-children-wrap {
        margin-left: 34px;
        border-left: 3px solid #e3e8f0;
        background: #fbfcff;
    }

    .category-children-wrap .table {
        background: #fff;
    }

    .category-children-wrap tbody tr td:first-child {
        border-left: 0;
    }

    @media (max-width: 575.98px) {
        .category-children-wrap {
            margin-left: 14px;
        }
    }
</style>
<script src="{{asset("/assets/gopanel/js/modules/categories.js?v=" . time())}}"></script>
@endpush
@endsection
