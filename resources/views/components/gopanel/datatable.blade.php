@props([
    'rows',                     // LengthAwarePaginator
    'columns',                  // array of ['key', 'label', 'sortable' => bool]
    'sortField' => 'id',
    'sortDirection' => 'desc',
    'perPage' => 15,
    'searchable' => true,
    'emptyMessage' => null,
])

<div>
    @if ($searchable)
        <div class="row mb-3">
            <div class="col-md-4">
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                    <input
                        type="text"
                        class="form-control"
                        placeholder="{{ __('Axtar...') }}"
                        wire:model.live.debounce.400ms="search"
                    >
                </div>
            </div>
            <div class="col-md-2 ms-auto">
                <select class="form-select" wire:model.live="perPage">
                    <option value="10">10</option>
                    <option value="15">15</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
            </div>
        </div>
    @endif

    <div class="table-responsive">
        <table class="table table-bordered table-hover mb-0">
            <thead class="table-light">
                <tr>
                    @foreach ($columns as $col)
                        @php
                            $key = $col['key'];
                            $label = $col['label'] ?? $key;
                            $sortable = (bool) ($col['sortable'] ?? false);
                            $width = $col['width'] ?? null;
                            $align = $col['align'] ?? null;
                        @endphp
                        <th
                            @if ($width) style="width: {{ $width }}" @endif
                            @if ($align) class="text-{{ $align }}" @endif
                        >
                            @if ($sortable)
                                <button
                                    type="button"
                                    wire:click="sortBy('{{ $key }}')"
                                    class="btn btn-link p-0 text-decoration-none text-dark fw-bold"
                                >
                                    {{ $label }}
                                    @if ($sortField === $key)
                                        <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ms-1"></i>
                                    @else
                                        <i class="fas fa-sort text-muted ms-1"></i>
                                    @endif
                                </button>
                            @else
                                {{ $label }}
                            @endif
                        </th>
                    @endforeach
                </tr>
            </thead>

            <tbody>
                @if ($rows->isEmpty())
                    <tr>
                        <td colspan="{{ count($columns) }}" class="text-center text-muted py-4">
                            {{ $emptyMessage ?? __('Heç nə tapılmadı') }}
                        </td>
                    </tr>
                @else
                    {{ $slot }}
                @endif
            </tbody>
        </table>
    </div>

    @if ($rows->hasPages())
        <div class="mt-3 datatable-paginator">
            {{ $rows->onEachSide(1)->links('livewire::bootstrap') }}
        </div>
    @endif
</div>
