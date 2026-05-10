@props([
    'name',
    'title' => '',
    'size' => 'md',          // sm | md | lg | xl
    'side' => true,           // right-side off-canvas (default) | false = centered
    'wireOpen' => null,       // optional Livewire property name to entangle e.g. "modalOpen"
])

@php
    $sizeClass = match ($size) {
        'sm' => 'modal-sm',
        'lg' => 'modal-lg',
        'xl' => 'modal-xl',
        default => '',
    };
@endphp

<div
    @if ($wireOpen)
        x-data="{ isOpen: @entangle($wireOpen) }"
    @else
        x-data="{ isOpen: false }"
    @endif
    x-on:open-modal.window="if ($event.detail?.name === '{{ $name }}') isOpen = true"
    x-on:close-modal.window="if ($event.detail?.name === '{{ $name }}') isOpen = false"
    x-on:keydown.escape.window="isOpen = false"
>
    <div
        class="modal fade"
        :class="isOpen ? 'show d-block' : ''"
        x-show="isOpen"
        x-transition.opacity
        tabindex="-1"
        aria-modal="true"
        role="dialog"
        style="background: rgba(0,0,0,.5);"
    >
        <div class="modal-dialog {{ $side ? 'modal-right-side' : 'modal-dialog-centered' }} {{ $sizeClass }}">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ $title }}</h5>
                    <button type="button" class="btn-close" x-on:click="isOpen = false" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    {{ $slot }}
                </div>

                @isset($footer)
                    <div class="modal-footer">
                        {{ $footer }}
                    </div>
                @endisset
            </div>
        </div>
    </div>
</div>
