@props([
    'name',                 // wire:model key e.g. "form.icon"
    'label' => 'Icon',
    'limit' => 200,         // max icons rendered at once
])

@php
    $iconBuckets = \App\Helpers\Gopanel\IconPickerHelper::all();
    $icons = collect($iconBuckets)->flatten()->unique()->values()->all();
    $id = 'ip_' . md5($name);
@endphp

<div class="mb-3" x-data="{
    open: false,
    search: '',
    selected: $wire.get('{{ $name }}') || '',
    icons: @js($icons),
    limit: {{ $limit }},
    get filtered() {
        const q = this.search.trim().toLowerCase();
        const list = q
            ? this.icons.filter(i => i.toLowerCase().includes(q))
            : this.icons;
        return list.slice(0, this.limit);
    },
    pick(icon) {
        this.selected = icon;
        $wire.set('{{ $name }}', icon);
        this.open = false;
    }
}">
    <label class="form-label">{{ $label }}</label>

    <div class="input-group">
        <span class="input-group-text">
            <i :class="selected || 'mdi mdi-help-circle-outline'"></i>
        </span>
        <input
            type="text"
            id="{{ $id }}"
            class="form-control"
            readonly
            x-model="selected"
            x-on:click="open = true"
        >
        <button type="button" class="btn btn-outline-secondary" x-on:click="open = !open">Browse</button>
    </div>

    <div x-show="open" x-cloak class="card mt-2" style="max-height: 320px; overflow: hidden;">
        <div class="card-body p-2">
            <input type="text" class="form-control form-control-sm mb-2" placeholder="Search icon..." x-model.debounce.200ms="search">

            <template x-if="open">
                <div class="d-flex flex-wrap gap-1" style="max-height: 240px; overflow-y: auto;">
                    <template x-for="icon in filtered" :key="icon">
                        <button
                            type="button"
                            class="btn btn-sm btn-light"
                            :class="selected === icon ? 'border border-primary' : ''"
                            x-on:click="pick(icon)"
                            :title="icon"
                        >
                            <i :class="icon"></i>
                        </button>
                    </template>
                </div>
            </template>

            <div class="text-muted small mt-1" x-show="icons.length > limit && !search">
                Showing first <span x-text="limit"></span> of <span x-text="icons.length"></span>. Type to search.
            </div>
        </div>
    </div>

    @error($name)
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
</div>
