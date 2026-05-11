@props([
    'name',
    'label' => null,
    'accept' => 'image/*',
    'existing' => null,
    'preview' => true,
])

@php
    $id = 'fu_' . md5($name);
@endphp

<div class="mb-3"
     x-data="{
        progress: 0,
        uploading: false,
        localPreview: null,
     }">
    @if ($label)
        <label for="{{ $id }}" class="form-label">{{ $label }}</label>
    @endif

    <input
        type="file"
        id="{{ $id }}"
        x-ref="input"
        class="form-control"
        accept="{{ $accept }}"
        wire:model="{{ $name }}"
        x-on:change="
            const file = $event.target.files[0];
            if (!file || !file.type.startsWith('image/')) { localPreview = null; return; }
            const reader = new FileReader();
            reader.onload = e => { localPreview = e.target.result || null; };
            reader.readAsDataURL(file);
        "
        x-on:livewire-upload-start="uploading = true"
        x-on:livewire-upload-finish="uploading = false; progress = 0"
        x-on:livewire-upload-cancel="uploading = false; progress = 0; localPreview = null"
        x-on:livewire-upload-error="uploading = false; progress = 0; localPreview = null"
        x-on:livewire-upload-progress="progress = $event.detail.progress"
    />

    <div x-show="uploading" class="progress mt-2" style="height: 6px;">
        <div class="progress-bar" role="progressbar" :style="`width: ${progress}%`"></div>
    </div>

    @if ($preview)
        <div class="mt-2 align-items-center gap-2" :class="localPreview ? 'd-flex' : 'd-none'">
            <img :src="localPreview" alt="" class="img-thumbnail" style="max-height: 120px;">
            <button type="button" class="btn btn-sm btn-outline-danger"
                    x-on:click="
                        $refs.input.value = '';
                        $wire.set('{{ $name }}', null);
                        localPreview = null;
                    ">
                <i class="fas fa-times me-1"></i> {{ __('Sil') }}
            </button>
        </div>

        @if ($existing)
            <div class="mt-2" x-show="!localPreview">
                <img src="{{ $existing }}" alt="" class="img-thumbnail" style="max-height: 120px;">
            </div>
        @endif
    @endif

    @error($name)
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
</div>
