@props([
    'tabs',
    'default' => null,
])

@php
    $items = collect($tabs);
    $defaultKey = $default ?? $items->keys()->first();
@endphp

<div x-data="{ tab: @js($defaultKey) }" {{ $attributes }}>
    <ul class="nav nav-tabs gp-tabs" role="tablist">
        @foreach ($items as $key => $label)
            <li class="nav-item">
                <button type="button"
                        class="nav-link"
                        :class="tab === @js($key) ? 'active' : ''"
                        x-on:click.prevent="tab = @js($key)">
                    {!! is_array($label) && isset($label['icon']) ? '<i class="' . e($label['icon']) . ' me-1"></i> ' : '' !!}{{ is_array($label) ? ($label['label'] ?? $key) : $label }}
                </button>
            </li>
        @endforeach
    </ul>

    {{ $slot }}
</div>
