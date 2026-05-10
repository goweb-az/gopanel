@props([
    'name',                    // wire:model key, e.g. "upload" or "form.image"
    'label' => null,
    'accept' => 'image/*',
    'existing' => null,        // existing file URL/path to show as preview
    'preview' => true,
])

@php
    $id = 'fu_' . md5($name);
@endphp

<div class="mb-3" x-data="{ progress: 0, uploading: false }">
    @if ($label)
        <label for="{{ $id }}" class="form-label">{{ $label }}</label>
    @endif

    <input
        type="file"
        id="{{ $id }}"
        class="form-control"
        accept="{{ $accept }}"
        wire:model="{{ $name }}"
        x-on:livewire-upload-start="uploading = true"
        x-on:livewire-upload-finish="uploading = false; progress = 0"
        x-on:livewire-upload-cancel="uploading = false; progress = 0"
        x-on:livewire-upload-error="uploading = false; progress = 0"
        x-on:livewire-upload-progress="progress = $event.detail.progress"
    />

    <div x-show="uploading" class="progress mt-2" style="height: 6px;">
        <div class="progress-bar" role="progressbar" :style="`width: ${progress}%`"></div>
    </div>

    @if ($preview)
        @if ($existing)
            <div class="mt-2">
                <img src="{{ $existing }}" alt="" class="img-thumbnail" style="max-height: 120px;">
            </div>
        @endif

        <div wire:loading.remove wire:target="{{ $name }}">
            @if (request()->session()->get('_lw_preview_' . $name))
                {{-- placeholder for newly uploaded preview if needed --}}
            @endif
        </div>
    @endif

    @error($name)
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
</div>
