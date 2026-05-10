<?php

use App\Livewire\Concerns\AuthorizesGopanel;
use App\Livewire\Forms\LanguageForm;
use App\Models\Geography\Country;
use App\Models\Geography\Language;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component {
    use AuthorizesGopanel;

    public LanguageForm $form;

    public bool $modalOpen = false;

    public function mount(): void
    {
        $this->form->setItem(new Language());
    }

    #[Computed]
    public function countries()
    {
        return Country::orderBy('name')->get(['id', 'name']);
    }

    #[On('language-form-open')]
    public function openForm(?int $id = null): void
    {
        $this->resetValidation();

        $language = $id ? Language::findOrFail($id) : new Language();
        $perm = $id ? 'gopanel.settings.languages.edit' : 'gopanel.settings.languages.add';
        $this->authorize($perm);

        $this->form->setItem($language);
        $this->modalOpen = true;
    }

    public function save(): void
    {
        $perm = $this->form->form['id']
            ? 'gopanel.settings.languages.edit'
            : 'gopanel.settings.languages.add';
        $this->authorize($perm);

        $this->form->validate();

        DB::transaction(fn () => $this->form->save());

        $this->modalOpen = false;
        $this->dispatch('notify', type: 'success', message: __('Yadda saxlanıldı'));
        $this->dispatch('language-saved');
    }
}; ?>

<div>
    <x-gopanel.modal
        name="language-form"
        :title="$form->form['id'] ? __('Dilə düzəliş') : __('Yeni dil')"
        size="md"
        wireOpen="modalOpen"
    >
        <form wire:submit.prevent="save">
            <div class="row">
                <div class="col-md-8 mb-3">
                    <label class="form-label">{{ __('Adı') }} <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" wire:model="form.form.name">
                    @error('form.form.name') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">{{ __('Kod') }} <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" wire:model="form.form.code" maxlength="5" placeholder="az">
                    @error('form.form.code') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">{{ __('Ölkə') }}</label>
                <select class="form-select" wire:model="form.form.country_id">
                    <option value="">{{ __('Seçin...') }}</option>
                    @foreach ($this->countries as $country)
                        <option value="{{ $country->id }}">{{ $country->name }}</option>
                    @endforeach
                </select>
                @error('form.form.country_id') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
            </div>

            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">{{ __('Sıra') }}</label>
                    <input type="number" class="form-control" wire:model="form.form.sort_order" min="0">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">{{ __('Status') }}</label>
                    <select class="form-select" wire:model="form.form.is_active">
                        <option value="1">{{ __('Aktiv') }}</option>
                        <option value="0">{{ __('Deaktiv') }}</option>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">{{ __('Sayt göstər') }}</label>
                    <select class="form-select" wire:model="form.form.is_show">
                        <option value="1">{{ __('Bəli') }}</option>
                        <option value="0">{{ __('Xeyr') }}</option>
                    </select>
                </div>
            </div>

            <div class="form-check form-switch mb-2">
                <input type="checkbox" class="form-check-input" id="defaultSwitch" wire:model="form.form.default">
                <label class="form-check-label" for="defaultSwitch">{{ __('Default dil') }}</label>
            </div>
            <small class="text-muted">{{ __('Default seçildikdə avtomatik aktiv olur.') }}</small>
        </form>

        <x-slot:footer>
            <button type="button" class="btn btn-secondary" x-on:click="isOpen = false" wire:loading.attr="disabled">
                {{ __('Bağla') }}
            </button>
            <button type="button" class="btn btn-primary" wire:click="save" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="save"><i class="fas fa-save me-1"></i> {{ __('Yadda saxla') }}</span>
                <span wire:loading wire:target="save"><i class="fas fa-spinner fa-spin me-1"></i> {{ __('Saxlanır...') }}</span>
            </button>
        </x-slot:footer>
    </x-gopanel.modal>
</div>
