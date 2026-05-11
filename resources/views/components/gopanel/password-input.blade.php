@props([
    'name',
    'label' => null,
    'autocomplete' => 'new-password',
    'required' => false,
])

@php
    $id = 'pw_' . md5($name);
@endphp

<div class="mb-3" x-data="{ show: false }">
    @if ($label)
        <label for="{{ $id }}" class="form-label">
            {{ $label }}
            @if ($required) <span class="text-danger">*</span> @endif
        </label>
    @endif

    <div class="input-group">
        <input
            :type="show ? 'text' : 'password'"
            id="{{ $id }}"
            class="form-control"
            wire:model="{{ $name }}"
            autocomplete="{{ $autocomplete }}"
        >
        <button type="button" class="btn btn-outline-secondary" x-on:click="show = !show" tabindex="-1"
                :title="show ? @js(__('Gizlət')) : @js(__('Göstər'))">
            <i class="fas" :class="show ? 'fa-eye-slash' : 'fa-eye'"></i>
        </button>
    </div>

    @error($name)
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
</div>
