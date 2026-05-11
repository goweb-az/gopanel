@props([
    'name' => null,          // wire:model key (form binding)
    'label' => null,
    'description' => null,
    'live' => true,          // wire:model.live vs wire:model
    'onChange' => null,      // wire:change="methodName" (paired with $name)
    'click' => null,         // wire:click="methodName" (alternative: stateless click toggle)
    'checked' => false,      // initial state when using click variant
    'disabled' => false,
])

@php
    $id = 'tg_' . md5(($name ?? '') . ($click ?? '') . uniqid('', true));
    $wireDirective = $live ? 'wire:model.live' : 'wire:model';
    $stacked = $label || $description;
@endphp

<div class="{{ $stacked ? 'd-flex align-items-center justify-content-between gap-3 py-2' : 'form-check form-switch d-inline-block m-0' }}">
    @if ($stacked)
        <div class="flex-grow-1">
            @if ($label)
                <label for="{{ $id }}" class="form-label mb-0 fw-medium" style="cursor: pointer;">{{ $label }}</label>
            @endif
            @if ($description)
                <div class="text-muted small">{{ $description }}</div>
            @endif
        </div>
        <div class="form-check form-switch m-0" style="min-width: 3.5em;">
    @endif

    <input type="checkbox"
           id="{{ $id }}"
           class="form-check-input"
           role="switch"
           style="width: 3em; height: 1.5em; cursor: pointer;"
           @if ($name) {{ $wireDirective }}="{{ $name }}" @endif
           @if ($click) wire:click="{{ $click }}" @endif
           @if ($onChange) wire:change="{{ $onChange }}" @endif
           @if ($checked && ! $name) checked @endif
           @disabled($disabled)>

    @if ($stacked)
        </div>
    @endif
</div>

@if ($name)
    @error($name)
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
@endif
