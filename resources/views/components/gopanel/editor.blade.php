@props([
    'name',                 // wire:model key, e.g. "form.body"
    'label' => null,
    'rows' => 8,
    'config' => '{}',        // JSON-encoded CKEditor config
])

@php
    $id = 'ck_' . md5($name);
@endphp

<div class="mb-3" wire:ignore>
    @if ($label)
        <label for="{{ $id }}" class="form-label">{{ $label }}</label>
    @endif

    <textarea
        id="{{ $id }}"
        rows="{{ $rows }}"
        class="form-control"
        x-data="{
            instance: null,
            init() {
                if (typeof CKEDITOR === 'undefined') {
                    console.warn('CKEditor not loaded');
                    return;
                }
                this.instance = CKEDITOR.replace($el, {{ $config }});
                this.instance.setData($wire.get('{{ $name }}') || '');
                this.instance.on('change', () => {
                    $wire.set('{{ $name }}', this.instance.getData());
                });
            },
        }"
        x-init="init()"
    >{!! data_get($attributes->getAttributes(), 'value') !!}</textarea>

    @error($name)
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
</div>
