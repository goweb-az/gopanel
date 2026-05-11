@props([
    'name',
    'label' => null,
    'accept' => 'image/*',
    'existing' => null,
    'preview' => true,
    'design' => 'dropzone',
])

@php
    $id = 'fu_' . md5($name);

    $acceptHint = match (true) {
        $accept === 'image/*' => 'PNG, JPG, GIF, WEBP',
        $accept === 'video/*' => 'MP4, MOV, WEBM',
        $accept === 'audio/*' => 'MP3, WAV, OGG',
        $accept === '*/*' || $accept === '*' => __('Hər növ fayl'),
        default => strtoupper(implode(', ', array_map(
            fn ($t) => ltrim(trim($t), '.'),
            explode(',', $accept)
        ))),
    };
@endphp

<div class="mb-3"
     x-data="{
        progress: 0,
        uploading: false,
        localPreview: null,
        dragging: false,
        hovering: false,
     }">
    @if ($label)
        <label for="{{ $id }}" class="form-label">{{ $label }}</label>
    @endif

    @if ($design === 'dropzone')
        <label for="{{ $id }}"
               class="d-flex flex-column align-items-center justify-content-center text-center p-4 rounded"
               style="cursor: pointer; border: 2px dashed #ced4da; background-color: #f8f9fa; min-height: 160px; transition: border-color .15s;"
               :style="{ borderColor: (dragging || hovering) ? '#556ee6' : '#ced4da' }"
               x-on:mouseenter="hovering = true"
               x-on:mouseleave="hovering = false"
               x-on:dragover.prevent="dragging = true"
               x-on:dragleave.prevent="dragging = false"
               x-on:drop.prevent="
                    dragging = false;
                    if ($event.dataTransfer.files.length) {
                        $refs.input.files = $event.dataTransfer.files;
                        $refs.input.dispatchEvent(new Event('change', { bubbles: true }));
                    }
               ">
            <div class="d-flex align-items-center justify-content-center mb-2"
                 style="width: 48px; height: 48px; border-radius: 50%; background-color: #e9ecef;">
                <i class="fas fa-plus text-secondary" style="font-size: 20px;"></i>
            </div>
            <div class="fw-semibold text-dark">{{ __('Fayl seçin və ya bura sürükləyin') }}</div>
            <div class="text-muted small mt-1">{{ $acceptHint }}</div>
        </label>
    @endif

    <input
        type="file"
        id="{{ $id }}"
        x-ref="input"
        class="{{ $design === 'dropzone' ? 'd-none' : 'form-control' }}"
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
