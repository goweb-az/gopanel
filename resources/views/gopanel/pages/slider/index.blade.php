@extends('gopanel.layouts.main')
@section('content')
<div class="page-content">
    <div class="container-fluid">

        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0 font-size-18">Slayder</h4>

                    <div class="page-title-right">
                        @can('gopanel.slider.add')
                        <button class="btn btn-success" id="open-create-modal" data-route="{{ route('gopanel.slider.get.form') }}">
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
                                        <th>Şəkil</th>
                                        <th>Başlıq</th>
                                        <th>Məlumat</th>
                                        <th style="width:90px;" class="text-center">Status</th>
                                        <th style="width:110px;" class="text-center">Əməliyyat</th>
                                    </tr>
                                </thead>
                                <tbody class="sortable"
                                       data-key="{{ $modelKey }}"
                                       data-row="sort_order"
                                       data-url="{{ route('gopanel.general.sortable') }}">
                                    @foreach($sliders as $slider)
                                    <tr id="item_{{ $slider->id }}">
                                        <td style="cursor:grab;text-align:center;vertical-align:middle;">
                                            <i class="fas fa-grip-vertical text-muted"></i>
                                        </td>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{!! $slider->image_view !!}</td>
                                        <td><strong>{{ $slider->getTranslation('title', app()->getLocale(), true) ?? '—' }}</strong></td>
                                        <td>{{ \Illuminate\Support\Str::limit(strip_tags($slider->getTranslation('description', app()->getLocale(), true) ?? ''), 80) }}</td>
                                        <td class="text-center">
                                            {!! app('gopanel')->toggle_btn($slider, 'is_active', $slider->is_active == 1) !!}
                                        </td>
                                        <td class="text-center">
                                            @can('gopanel.slider.edit')
                                            <a href="{{ route('gopanel.slider.get.form', $slider) }}" class="btn btn-sm btn-outline-success waves-effect waves-light edit" data-bs-toggle="tooltip" title="Düzəliş et">
                                                <i class="fas fa-pen"></i>
                                            </a>
                                            @endcan
                                            @can('gopanel.slider.delete')
                                            <a href="javascript:void(0)" class="btn btn-sm btn-outline-danger waves-effect waves-light delete" data-url="{{ route('gopanel.general.delete', $slider) }}" data-key="{{ get_class($slider) }}" data-bs-toggle="tooltip" title="Sil">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                            @endcan
                                        </td>
                                    </tr>
                                    @endforeach

                                    @if($sliders->isEmpty())
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">
                                            <i class="fas fa-images fa-2x mb-2 d-block"></i>
                                            Heç bir slayd tapılmadı
                                        </td>
                                    </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
@include('gopanel.pages.slider.inc.modal')
@push('scripts')
<script src="{{ asset('/assets/gopanel/libs/ckeditor/ckeditor.js') }}"></script>
@endpush
@endsection
