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
    x-effect="document.body.classList.toggle('gp-modal-open', isOpen)"
>
    {{-- Backdrop: click to close, fade transition --}}
    <div
        class="gp-modal-backdrop"
        x-show="isOpen"
        x-transition.opacity.duration.250ms
        x-on:click="isOpen = false"
        x-cloak
    ></div>

    {{-- Drawer panel: slides from right --}}
    <div
        class="gp-modal {{ $side ? 'gp-modal--side' : 'gp-modal--center' }} gp-modal--{{ $size }}"
        x-show="isOpen"
        @if ($side)
            x-transition:enter="gp-modal-slide-enter"
            x-transition:enter-start="gp-modal-slide-enter-start"
            x-transition:enter-end="gp-modal-slide-enter-end"
            x-transition:leave="gp-modal-slide-leave"
            x-transition:leave-start="gp-modal-slide-leave-start"
            x-transition:leave-end="gp-modal-slide-leave-end"
        @else
            x-transition.opacity.duration.200ms
        @endif
        tabindex="-1"
        aria-modal="true"
        role="dialog"
        x-cloak
    >
        <div class="gp-modal__content">
            <div class="gp-modal__header">
                <h5 class="gp-modal__title">{{ $title }}</h5>
                <button type="button" class="btn-close" x-on:click="isOpen = false" aria-label="Close"></button>
            </div>

            <div class="gp-modal__body">
                {{ $slot }}
            </div>

            @isset($footer)
                <div class="gp-modal__footer">
                    {{ $footer }}
                </div>
            @endisset
        </div>
    </div>
</div>
