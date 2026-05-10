<?php

use App\Enums\Gopanel\TranslationGroups;
use App\Enums\Gopanel\TranslationPlatfroms;
use App\Livewire\Concerns\AuthorizesGopanel;
use App\Livewire\Forms\TranslationForm as RecordForm;
use App\Models\Geography\Language;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component {
    use AuthorizesGopanel;

    public RecordForm $form;

    public bool $modalOpen = false;

    public string $permissionCreate = 'gopanel.settings.translations.add';
    public string $permissionEdit   = 'gopanel.settings.translations.edit';
    public string $eventSaved       = 'translation-saved';

    public function mount(): void
    {
        $this->form->setBundle(null, null);
    }

    #[Computed]
    public function languages()
    {
        return Language::getCachedAll();
    }

    #[On('translation-form-open')]
    public function openForm(?string $key = null, ?string $platform = null): void
    {
        $this->resetValidation();

        $this->authorize($key ? $this->permissionEdit : $this->permissionCreate);

        $this->form->setBundle($key, $platform);
        $this->modalOpen = true;
    }

    public function save(): void
    {
        $this->authorize($this->form->originalKey ? $this->permissionEdit : $this->permissionCreate);

        $this->form->validate();

        DB::transaction(fn () => $this->form->save());

        $this->modalOpen = false;
        $this->dispatch('notify', type: 'success', message: __('Yadda saxlanıldı'));
        $this->dispatch($this->eventSaved);
    }
}; ?>

<div>
    <x-gopanel.modal
        name="translation-form"
        :title="$form->originalKey ? __('Tərcüməyə düzəliş') : __('Yeni tərcümə')"
        size="lg"
        wireOpen="modalOpen"
    >
        <form wire:submit.prevent="save">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">{{ __('Açar (key)') }} <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" wire:model="form.form.key">
                    @error('form.form.key') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">{{ __('Platforma') }}</label>
                    <select class="form-select" wire:model="form.form.platform">
                        @foreach (TranslationPlatfroms::cases() as $case)
                            <option value="{{ $case->value }}">{{ ucfirst($case->value) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">{{ __('Qrup') }}</label>
                    <select class="form-select" wire:model="form.form.group">
                        @foreach (TranslationGroups::cases() as $case)
                            <option value="{{ $case->value }}">{{ ucfirst($case->value) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">{{ __('Fayl') }}</label>
                <input type="text" class="form-control" wire:model="form.form.filename" placeholder="messages, validation, ...">
            </div>

            <hr>
            <h6>{{ __('Dillər üzrə dəyər') }}</h6>

            @foreach ($this->languages as $lang)
                <div class="mb-3">
                    <label class="form-label">{{ strtoupper($lang->code) }}</label>
                    <textarea class="form-control" rows="2" wire:model="form.values.{{ $lang->code }}"></textarea>
                    @error("form.values.{$lang->code}") <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                </div>
            @endforeach
        </form>

        <x-slot:footer>
            <button type="button" class="btn btn-secondary" x-on:click="isOpen = false">
                {{ __('Bağla') }}
            </button>
            <button type="button" class="btn btn-primary" wire:click="save">
                <span class="lw-not-loading"><i class="fas fa-save me-1"></i> {{ __('Yadda saxla') }}</span>
                <span class="lw-loading"><i class="fas fa-spinner fa-spin me-1"></i> {{ __('Saxlanır...') }}</span>
            </button>
        </x-slot:footer>
    </x-gopanel.modal>
</div>
