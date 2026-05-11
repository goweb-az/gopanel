@props([
    'name',
    'label' => null,
    'rows' => 18,
    'placeholder' => '',
])

@php
    $id = 'fs_' . md5($name);
@endphp

<div class="mb-3" x-data="{ fullscreen: false }">
    @if ($label)
        <div class="d-flex align-items-center justify-content-between mb-1">
            <label for="{{ $id }}" class="form-label mb-0">{{ $label }}</label>
            <button type="button" class="btn btn-sm btn-outline-secondary" x-on:click="fullscreen = !fullscreen" :title="fullscreen ? @js(__('Kiçilt')) : @js(__('Tam ekran'))">
                <i class="fas" :class="fullscreen ? 'fa-compress' : 'fa-expand'"></i>
            </button>
        </div>
    @endif

    <div
        :class="fullscreen ? 'position-fixed top-0 start-0 w-100 h-100 p-3' : ''"
        :style="fullscreen ? 'z-index: 1080; background: rgba(0,0,0,0.85);' : ''"
    >
        <template x-if="fullscreen">
            <button type="button" class="btn btn-sm btn-light position-absolute" style="top: 16px; right: 16px; z-index: 2;"
                    x-on:click="fullscreen = false" title="{{ __('Kiçilt') }}">
                <i class="fas fa-compress me-1"></i> {{ __('Kiçilt') }}
            </button>
        </template>

        <textarea
            id="{{ $id }}"
            class="form-control font-monospace"
            :rows="fullscreen ? 30 : {{ $rows }}"
            :class="fullscreen ? 'h-100' : ''"
            wire:model="{{ $name }}"
            placeholder="{{ $placeholder }}"
        ></textarea>
    </div>

    @error($name)
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
</div>
