@extends('gopanel.layouts.main')
@section('content')
@include('gopanel.pages.settings.partials.sortable-styles')
<div class="page-content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0 font-size-18">Dillər</h4>

                    <div class="page-title-right d-flex align-items-center gap-2">
                        <form method="GET" action="{{ route('gopanel.settings.languages.index') }}" id="language-country-filter">
                            <select name="country_id" class="form-select" onchange="document.getElementById('language-country-filter').submit()">
                                <option value="">Bütün ölkələr</option>
                                @foreach ($countries as $country)
                                    <option value="{{ $country->id }}" @selected((string) $countryId === (string) $country->id)>{{ $country->name }}</option>
                                @endforeach
                            </select>
                        </form>

                        @can('gopanel.settings.languages.add')
                        <button class="btn btn-success" id="open-create-modal" data-route="{{ route('gopanel.settings.languages.get.form') }}">
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
                        @if ($countryId)
                            <div class="alert alert-info py-2 mb-3">
                                Ölkə filtri aktivdir — sıralama yalnız filtrsiz siyahıda mümkündür.
                            </div>
                        @endif

                        <div class="table-responsive sortable-shell" id="language-shell">
                            <div class="sortable-overlay d-none" id="language-overlay">
                                <div class="spinner-border text-primary" role="status" aria-hidden="true"></div>
                            </div>
                            <table class="table align-middle mb-0" id="language-table">
                                <thead>
                                    <tr>
                                        <th style="width:40px" class="text-center">#</th>
                                        <th style="width:70px">Bayraq</th>
                                        <th>Ad</th>
                                        <th>Kod</th>
                                        <th>Ölkə</th>
                                        <th>Default</th>
                                        <th>Göstər</th>
                                        <th>Status</th>
                                        <th>Əməliyyatlar</th>
                                    </tr>
                                </thead>
                                <tbody id="language-sortable"
                                    data-sortable-enabled="{{ $countryId ? '0' : '1' }}"
                                    data-sort-url="{{ route('gopanel.settings.languages.sort') }}">
                                    @foreach ($languagesList as $language)
                                    @php $cc = strtolower(optional($language->country)->code ?? ''); @endphp
                                    <tr id="item_{{ $language->id }}" data-id="{{ $language->id }}">
                                        <td class="text-center">
                                            @can('gopanel.settings.languages.sort')
                                                @unless($countryId)
                                                <span class="drag-handle language-drag-handle" role="button" aria-label="Sırala {{ $language->name }}">
                                                    <i class="fas fa-grip-vertical"></i>
                                                </span>
                                                @endunless
                                            @endcan
                                        </td>
                                        <td>
                                            @if ($cc)
                                                <img src="https://flagcdn.com/32x24/{{ $cc }}.png"
                                                     srcset="https://flagcdn.com/64x48/{{ $cc }}.png 2x"
                                                     width="32" height="24" class="rounded"
                                                     alt="{{ optional($language->country)->code }}"
                                                     onerror="this.style.display='none'">
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td><strong>{{ $language->name }}</strong></td>
                                        <td><span class="badge bg-primary rounded-pill">{{ $language->upper_code }}</span></td>
                                        <td>{{ optional($language->country)->name ?? '—' }}</td>
                                        <td>{!! app('gopanel')->toggle_btn($language, 'default', $language->default == 1, [], route('gopanel.settings.languages.toggle.default'), 'Bəli', 'Xeyr') !!}</td>
                                        <td>{!! app('gopanel')->toggle_btn($language, 'is_show', $language->is_show == 1) !!}</td>
                                        <td>{!! app('gopanel')->toggle_btn($language, 'is_active', $language->is_active == 1) !!}</td>
                                        <td>
                                            <div class="d-flex gap-1">
                                                @can('gopanel.settings.languages.edit')
                                                <a href="{{ route('gopanel.settings.languages.get.form', $language) }}" class="btn btn-outline-success waves-effect waves-light edit" data-bs-toggle="tooltip" data-bs-placement="top" title="Məlumata düzəliş et">
                                                    <i class="fas fa-pen f-20"></i>
                                                </a>
                                                @endcan
                                                @can('gopanel.settings.languages.delete')
                                                <a href="{{ route('gopanel.general.delete', $language) }}" class="btn btn-outline-danger waves-effect waves-light delete" data-url="{{ route('gopanel.general.delete', $language) }}" data-key="{{ get_class($language) }}" data-bs-toggle="tooltip" data-bs-placement="top" title="Məlumatı sil">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                                @endcan
                                            </div>
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

@push('scripts')
<script src="{{ asset('/assets/gopanel/js/modules/languages.js?v=' . time()) }}"></script>
@endpush
