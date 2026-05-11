@props([
    'form' => 'form',
    'fields' => [],
])

@php
    $languages = \App\Facades\Locale::all();
    $defaultLocale = \App\Facades\Locale::defaultCode();
    $tabList = $languages->mapWithKeys(fn ($lang) => [$lang->code => strtoupper($lang->code)])->all();
@endphp

<x-gopanel.tabs :tabs="$tabList" :default="$defaultLocale">
    @foreach ($languages as $lang)
        <x-gopanel.tab :name="$lang->code" wire:key="lang-pane-{{ $lang->code }}">
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
        </x-gopanel.tab>
    @endforeach
</x-gopanel.tabs>
