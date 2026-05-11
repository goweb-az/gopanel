@props([
    'rows',                     // LengthAwarePaginator
    'columns',                  // array of ['key', 'label', 'sortable' => bool]
    'sortField' => 'id',
    'sortDirection' => 'desc',
    'perPage' => 15,
    'searchable' => true,
    'emptyMessage' => null,
])

<div class="gp-datatable">
    @if ($searchable)
        <div class="gp-datatable__toolbar">
            <div class="gp-datatable__search">
                <i class="fas fa-search gp-datatable__search-icon"></i>
                <input
                    type="text"
                    placeholder="{{ __('Axtar...') }}"
                    wire:model.live.debounce.400ms="search"
                >
                <button
                    type="button"
                    class="gp-datatable__search-clear lw-not-loading"
                    x-data
                    x-show="$wire.search"
                    x-cloak
                    x-on:click="$wire.set('search', '')"
                    aria-label="{{ __('Təmizlə') }}"
                >
                    <i class="fas fa-times"></i>
                </button>
                <span class="gp-datatable__search-spinner lw-loading">
                    <i class="fas fa-circle-notch fa-spin"></i>
                </span>
            </div>

            <div class="gp-datatable__per-page">
                <label class="gp-datatable__per-page-label">{{ __('Göstər') }}</label>
                <select wire:model.live="perPage">
                    @foreach ([10, 15, 25, 50, 100] as $n)
                        <option value="{{ $n }}">{{ $n }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    @endif

    <div class="gp-datatable__wrapper">
        <table class="gp-datatable__table">
            <thead>
                <tr>
                    @foreach ($columns as $col)
                        @php
                            $key = $col['key'];
                            $label = $col['label'] ?? $key;
                            $sortable = (bool) ($col['sortable'] ?? false);
                            $width = $col['width'] ?? null;
                            $align = $col['align'] ?? null;
                            $isSortedBy = $sortField === $key;
                        @endphp
                        <th
                            @if ($width) style="width: {{ $width }}" @endif
                            @if ($align) class="text-{{ $align }}" @endif
                            @if ($isSortedBy) data-sorted="{{ $sortDirection }}" @endif
                        >
                            @if ($sortable)
                                <button
                                    type="button"
                                    wire:click="sortBy('{{ $key }}')"
                                    class="gp-datatable__sort {{ $isSortedBy ? 'is-active' : '' }}"
                                >
                                    <span>{{ $label }}</span>
                                    @if ($isSortedBy)
                                        <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                                    @else
                                        <i class="fas fa-sort"></i>
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
                    <tr class="gp-datatable__empty-row">
                        <td colspan="{{ count($columns) }}">
                            <div class="gp-datatable__empty">
                                <i class="fas fa-inbox"></i>
                                <p>{{ $emptyMessage ?? __('Heç nə tapılmadı') }}</p>
                            </div>
                        </td>
                    </tr>
                @else
                    {{ $slot }}
                @endif
            </tbody>
        </table>
    </div>

    @if ($rows->hasPages())
        <div class="gp-datatable__paginator">
            {{ $rows->onEachSide(1)->links('livewire::bootstrap') }}
        </div>
    @endif
</div>
