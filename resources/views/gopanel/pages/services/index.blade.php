@extends('gopanel.layouts.main')
@section('content')
<div class="page-content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0 font-size-18">Xidmətlər</h4>
                    <div class="page-title-right">
                        @can('gopanel.services.add')
                        <button class="btn btn-success" id="open-create-modal" data-route="{{route('gopanel.services.get.form')}}">
                            <i class="fas fa-plus"></i> Əlavə et
                        </button>
                        @endcan
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-12">
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width:20px;"></th>
                                        <th style="width:50px;">#</th>
                                        <th>İkon</th>
                                        <th>Şəkil</th>
                                        <th>Başlıq</th>
                                        <th>Qısa məlumat</th>
                                        <th style="width:110px;">Əməliyyat</th>
                                    </tr>
                                </thead>
                                <tbody class="sortable"
                                       data-key="{{ $modelKey }}"
                                       data-row="sort_order"
                                       data-url="{{ route('gopanel.general.sortable') }}">
                                    @foreach($services as $service)
                                    <tr id="item_{{$service->id}}">
                                        <td style="cursor:grab;text-align:center;vertical-align:middle;">
                                            <i class="fas fa-grip-vertical text-muted"></i>
                                        </td>
                                        <td>{{$loop->iteration}}</td>
                                        <td>{!! $service->icon_view !!}</td>
                                        <td>{!! $service->image_view !!}</td>
                                        <td><strong>{{ $service->getTranslation('title', app()->getLocale(), true) ?? '—' }}</strong></td>
                                        <td>{{ $service->short_description_view }}</td>
                                        <td class="text-center">
                                            @can('gopanel.services.edit')
                                            <a href="{{ route('gopanel.services.get.form', $service) }}" class="btn btn-sm btn-outline-success waves-effect waves-light edit" data-bs-toggle="tooltip" title="Düzəliş et">
                                                <i class="fas fa-pen"></i>
                                            </a>
                                            @endcan
                                            @can('gopanel.services.delete')
                                            <a href="javascript:void(0)" class="btn btn-sm btn-outline-danger waves-effect waves-light delete" data-url="{{ route('gopanel.general.delete', $service) }}" data-key="{{ get_class($service) }}" data-bs-toggle="tooltip" title="Sil">
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
        </div>
    </div>
</div>
@include('gopanel.pages.services.inc.modal')
@include('gopanel.component.icon-picker-modal')
@push('scripts')
<script src="{{ asset('/assets/gopanel/libs/ckeditor/ckeditor.js') }}"></script>
<script src="{{ asset('/assets/gopanel/js/modules/services.js?v=' . time()) }}"></script>
@endpush
@endsection
