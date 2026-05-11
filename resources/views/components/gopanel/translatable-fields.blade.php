@props([
    'form' => 'form',          // form path prefix (e.g. "form" -> wire:model="form.translations.az.title")
    'fields' => [],            // [['name' => 'title', 'label' => 'Title', 'type' => 'text'], ...]
])

@php
    $languages = \App\Facades\Locale::all();
    $defaultLocale = \App\Facades\Locale::defaultCode();
@endphp

<div x-data="{ active: '{{ $defaultLocale }}' }">
    <ul class="nav nav-tabs mb-3" role="tablist">
        @foreach ($languages as $lang)
            <li class="nav-item" wire:key="lang-tab-{{ $lang->code }}">
                <button
                    type="button"
                    class="nav-link"
                    :class="active === '{{ $lang->code }}' ? 'active' : ''"
                    x-on:click.prevent="active = '{{ $lang->code }}'"
                >
                    {{ strtoupper($lang->code) }}
                </button>
            </li>
        @endforeach
    </ul>

    <div class="tab-content">
        @foreach ($languages as $lang)
            <div
                wire:key="lang-pane-{{ $lang->code }}"
                x-show="active === '{{ $lang->code }}'"
                x-cloak
            >
                @foreach ($fields as $field)
                    @php
                        $name = $field['name'] ?? null;
                        $label = $field['label'] ?? $name;
                        $type = $field['type'] ?? 'text';
                        $key = "{$form}.translations.{$lang->code}.{$name}";
                        $id = 'tf_' . md5($key);
                    @endphp

                    <div class="mb-3">
                        <label for="{{ $id }}" class="form-label">
                            {{ $label }} <span class="text-muted small">({{ strtoupper($lang->code) }})</span>
                        </label>

                        @if ($type === 'textarea')
                            <textarea id="{{ $id }}" class="form-control" rows="4" wire:model.lazy="{{ $key }}"></textarea>
                        @else
                            <input type="text" id="{{ $id }}" class="form-control" wire:model.lazy="{{ $key }}">
                        @endif

                        @error($key)
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                @endforeach
            </div>
        @endforeach
    </div>
</div>
