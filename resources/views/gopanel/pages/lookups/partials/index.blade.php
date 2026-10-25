{{--
    Bütün lookup tiplərinin ortaq siyahı səhifəsi.
    Dəyişənlər BaseLookupController::index()-dən gəlir: $definition (LookupDefinition),
    $items, $lookupMenu, $activeType, $needsIcons. Hesablama yoxdur - yalnız çap.
--}}
<div class="page-content lookup-page">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0 font-size-18">{{ $definition->title }}</h4>
                    <div class="page-title-right">
                        @can($definition->permission('add'))
                        <button type="button" class="btn btn-success" id="open-create-modal" data-route="{{ $definition->route('get.form') }}">
                            <i class="fas fa-plus"></i> Əlavə et
                        </button>
                        @endcan
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <aside class="col-xl-3">
                @include('gopanel.pages.lookups.sidebar')
            </aside>

            <main class="col-xl-9">
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive lookup-table-wrap">
                            <table class="table table-hover align-middle mb-0 lookup-table">
                                <thead>
                                    <tr>
                                        <th class="lookup-sort-column" aria-label="Sıra"></th>
                                        <th>Açar</th>
                                        <th>Ad</th>
                                        @if($definition->hasPreview())
                                        <th>Görünüş</th>
                                        @endif
                                        @if($definition->hasField('icon'))
                                        <th>İkon</th>
                                        @endif
                                        @foreach($definition->flagColumns() as $flagLabel)
                                        <th>{{ $flagLabel }}</th>
                                        @endforeach
                                        @if($definition->hasActiveToggle())
                                        <th>Status</th>
                                        @endif
                                        <th class="text-end">Əməliyyatlar</th>
                                    </tr>
                                </thead>
                                <tbody id="lookup-sortable" data-url="{{ $definition->route('sort') }}" data-toggle-url="{{ $definition->route('toggle') }}">
                                    @foreach ($items as $item)
                                    <tr data-id="{{ $item->identifier_id }}">
                                        <td class="lookup-sort-column">
                                            @can($definition->permission('sort'))
                                            <button type="button" class="btn btn-link lookup-drag-handle" aria-label="Sıranı dəyiş" title="Sürüşdürərək sıranı dəyiş">
                                                <i class="fas fa-grip-vertical"></i>
                                            </button>
                                            @endcan
                                        </td>
                                        <td><code>{{ $item->key }}</code></td>
                                        <td>{{ $item->name ?? $item->key }}</td>
                                        @if($definition->hasPreview())
                                        <td>
                                            <span class="badge lookup-badge {{ $item->class_name }} {{ $item->color ? 'lookup-badge--color' : '' }}" @if($item->color) style="--lookup-color: {{ $item->color }}" @endif>
                                                {{ $item->name ?? $item->key }}
                                            </span>
                                        </td>
                                        @endif
                                        @if($definition->hasField('icon'))
                                        <td>
                                            @if($item->icon)
                                            <i class="{{ $item->icon }} lookup-icon" title="{{ $item->icon }}"></i>
                                            @endif
                                        </td>
                                        @endif
                                        @foreach($definition->flagColumns() as $flag => $flagLabel)
                                        <td>
                                            @if($item->{$flag})
                                            <span class="badge bg-soft-success text-success">Bəli</span>
                                            @else
                                            <span class="badge bg-soft-secondary text-secondary">Xeyr</span>
                                            @endif
                                        </td>
                                        @endforeach
                                        @if($definition->hasActiveToggle())
                                        <td>
                                            @can($definition->permission('edit'))
                                            {!! app('gopanel')->toggle_btn($item, 'is_active', (bool) $item->is_active, [], $definition->route('toggle')) !!}
                                            @else
                                            <span class="badge {{ $item->is_active ? 'bg-success' : 'bg-danger' }}">{{ $item->is_active ? 'Aktiv' : 'Deaktiv' }}</span>
                                            @endcan
                                        </td>
                                        @endif
                                        <td class="text-end text-nowrap">
                                            @can($definition->permission('edit'))
                                            <a href="{{ $definition->route('get.form', $item->identifier_id) }}" class="btn btn-sm btn-outline-success edit" data-bs-toggle="tooltip" title="Düzəliş et">
                                                <i class="fas fa-pen"></i>
                                            </a>
                                            @endcan
                                            @can($definition->permission('delete'))
                                            <button type="button" class="btn btn-sm btn-outline-danger lookup-delete" data-url="{{ $definition->route('destroy', $item->identifier_id) }}" data-bs-toggle="tooltip" title="Sil">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                            @endcan
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        @if($items->isEmpty())
                        <div class="text-center py-5 text-muted lookup-empty">Hələ heç bir sətir əlavə edilməyib</div>
                        @endif
                    </div>
                </div>
            </main>
        </div>
    </div>
</div>

@include('gopanel.pages.lookups.inc.modal')

@if($needsIcons)
@include('gopanel.component.icon-picker-modal')
@endif

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/gopanel/css/lookups.css') }}?v={{ filemtime(public_path('assets/gopanel/css/lookups.css')) }}">
@endpush

@push('scripts')
<script src="{{ asset('assets/gopanel/js/modules/lookups/lookups.js') }}?v={{ filemtime(public_path('assets/gopanel/js/modules/lookups/lookups.js')) }}"></script>
@endpush
