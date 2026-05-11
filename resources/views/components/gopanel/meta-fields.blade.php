@props([
    'form' => 'form',
])

@php
    $metaLanguages = \App\Facades\Locale::all();
    $metaDefault = \App\Facades\Locale::defaultCode();
    $metaTabList = $metaLanguages->mapWithKeys(fn ($lang) => [$lang->code => strtoupper($lang->code)])->all();
    $formObject = data_get($__data, $form);
@endphp

<div class="card">
    <div class="card-body">
        <h5 class="card-title">{{ __('SEO meta') }}</h5>

        <x-gopanel.tabs :tabs="$metaTabList" :default="$metaDefault">
            @foreach ($metaLanguages as $lang)
                <x-gopanel.tab :name="$lang->code" wire:key="meta-pane-{{ $form }}-{{ $lang->code }}">
                    @php
                        $existingImage = $formObject->meta[$lang->code]['image'] ?? '';
                    @endphp

                    <div class="mb-3">
                        <label class="form-label">{{ __('Meta başlıq') }}</label>
                        <input type="text" class="form-control" wire:model="{{ $form }}.meta.{{ $lang->code }}.title">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Meta məlumat') }}</label>
                        <textarea class="form-control" rows="4" wire:model="{{ $form }}.meta.{{ $lang->code }}.description"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Meta açar sözlər') }}</label>
                        <input type="text" class="form-control" wire:model="{{ $form }}.meta.{{ $lang->code }}.keywords" placeholder="laravel, php, web">
                    </div>
                    <x-gopanel.file-upload
                        :name="$form . '.metaUploads.' . $lang->code"
                        :label="__('Şəkil') . ' (600x600)'"
                        accept="image/*"
                        :existing="$existingImage !== '' ? \Illuminate\Support\Facades\Storage::disk(gopanel_disk())->url($existingImage) : null"
                    />
                </x-gopanel.tab>
            @endforeach
        </x-gopanel.tabs>
    </div>
</div>
