<?php

use App\Livewire\Concerns\AuthorizesGopanel;
use App\Livewire\Forms\LanguageForm as RecordForm;
use App\Models\Geography\Country;
use App\Models\Geography\Language as RecordModel;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component {
    use AuthorizesGopanel;

    public RecordForm $form;

    public bool $modalOpen = false;

    public string $permissionCreate = 'gopanel.settings.languages.add';
    public string $permissionEdit   = 'gopanel.settings.languages.edit';
    public string $eventSaved       = 'language-saved';

    public function mount(): void
    {
        $this->form->setItem(new RecordModel());
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

        $record = $id ? RecordModel::findOrFail($id) : new RecordModel();
        $this->authorize($id ? $this->permissionEdit : $this->permissionCreate);

        $this->form->setItem($record);
        $this->modalOpen = true;
    }

    public function save(): void
    {
        $this->authorize($this->form->form['id'] ? $this->permissionEdit : $this->permissionCreate);

        $this->form->validate();

        DB::transaction(fn () => $this->form->save());

        $this->modalOpen = false;
        $this->dispatch('notify', type: 'success', message: __('Yadda saxlanıldı'));
        $this->dispatch($this->eventSaved);
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

            <div class="mb-3">
                <label class="form-label">{{ __('Sıra') }}</label>
                <input type="number" class="form-control" wire:model="form.form.sort_order" min="0">
            </div>

            <div class="border rounded p-2">
                <x-gopanel.toggle
                    name="form.form.is_active"
                    :label="__('Aktiv')"
                    :description="__('Dil panel və site dropdownlarında görünsün')"
                    :live="false"
                />
                <x-gopanel.toggle
                    name="form.form.is_show"
                    :label="__('Sayt göstər')"
                    :description="__('Site frontend-də dil seçiminə qoy')"
                    :live="false"
                />
                <x-gopanel.toggle
                    name="form.form.default"
                    :label="__('Default dil')"
                    :description="__('Default seçildikdə avtomatik aktiv olur.')"
                    :live="false"
                />
            </div>
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
