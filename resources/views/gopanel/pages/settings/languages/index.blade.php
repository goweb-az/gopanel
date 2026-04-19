@extends('gopanel.layouts.main')
@section('content')
<div class="page-content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0 font-size-18">Diller</h4>

                    <div class="page-title-right">
                        @can('gopanel.settings.languages.add')
                        <button class="btn btn-success" id="open-create-modal" data-route="{{ route('gopanel.settings.languages.get.form') }}">
                            <i class="fas fa-plus"></i> Elave et
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
                            <table class="table table-bordered mb-0">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Adi</th>
                                        <th>Kodu</th>
                                        <th>Olkesi</th>
                                        <th>Default</th>
                                        <th>Status</th>
                                        <th>Manage</th>
                                    </tr>
                                </thead>
                                <tbody class="sortable"
                                    data-key="{{ get_class($languagesList->first()) }}"
                                    data-row="sort_order"
                                    data-url="{{ route('gopanel.general.sortable') }}">
                                    @foreach ($languagesList as $language)
                                    <tr id="item_{{ $language->id }}">
                                        <th scope="row">{{ $loop->iteration }}</th>
                                        <td>{{ $language->name }}</td>
                                        <td>{{ $language->code }}</td>
                                        <td>{{ $language?->country?->name }}</td>
                                        <td>{!! app('gopanel')->toggle_btn($language, 'default', $language->default == 1, [], route('gopanel.settings.languages.toggle.default'), 'Beli', 'Xeyr') !!}</td>
                                        <td>{!! app('gopanel')->toggle_btn($language, 'is_active', $language->is_active == 1) !!}</td>
                                        <td>
                                            @can('gopanel.settings.languages.edit')
                                            <a href="{{ route('gopanel.settings.languages.get.form', $language) }}" class="btn btn-outline-success waves-effect waves-light edit" data-bs-toggle="tooltip" data-bs-placement="top" title="Melumata duzelis et">
                                                <i class="fas fa-pen f-20"></i>
                                            </a>
                                            @endcan
                                            @can('gopanel.settings.languages.delete')
                                            <a href="{{ route('gopanel.general.delete', $language) }}" class="btn btn-outline-danger waves-effect waves-light delete" data-url="{{ route('gopanel.general.delete', $language) }}" data-key="{{ get_class($language) }}" data-bs-toggle="tooltip" data-bs-placement="top" title="Melumati sil">
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
@include('gopanel.pages.settings.languages.inc.modal')
@endsection
