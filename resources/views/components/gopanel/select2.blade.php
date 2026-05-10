@props([
    'name',                  // wire:model key, e.g. "form.category_id"
    'label' => null,
    'options' => [],         // [value => label]
    'placeholder' => '',
    'multiple' => false,
    'allowClear' => true,
])

@php
    $id = 's2_' . md5($name);
    $multipleAttr = $multiple ? 'multiple' : '';
@endphp

<div class="mb-3" wire:ignore>
    @if ($label)
        <label for="{{ $id }}" class="form-label">{{ $label }}</label>
    @endif

    <select
        id="{{ $id }}"
        class="form-select"
        {{ $multipleAttr }}
        x-data="{
            init() {
                const $el = window.$($refs.select);
                $el.select2({
                    placeholder: '{{ $placeholder }}',
                    allowClear: {{ $allowClear ? 'true' : 'false' }},
                    width: '100%',
                });
                $el.val($wire.get('{{ $name }}')).trigger('change.select2');
                $el.on('change', () => {
                    $wire.set('{{ $name }}', $el.val());
                });
            }
        }"
        x-init="init()"
        x-ref="select"
    >
        @if (!$multiple && $allowClear)
            <option value=""></option>
        @endif
        @foreach ($options as $value => $label)
            <option value="{{ $value }}">{{ $label }}</option>
        @endforeach
    </select>

    @error($name)
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
</div>
