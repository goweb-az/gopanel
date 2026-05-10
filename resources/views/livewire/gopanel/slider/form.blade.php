<?php

use App\Livewire\Forms\SliderForm;
use App\Models\Site\Slider;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component {
    use WithFileUploads;

    public SliderForm $form;

    public bool $modalOpen = false;

    public function mount(): void
    {
        $this->form->setItem(new Slider());
    }

    #[On('slider-form-open')]
    public function openForm(?int $id = null): void
    {
        $this->resetValidation();

        $slider = $id ? Slider::findOrFail($id) : new Slider();
        $perm = $id ? 'gopanel.slider.edit' : 'gopanel.slider.add';
        $this->authorize($perm);

        $this->form->setItem($slider);
        $this->modalOpen = true;
    }

    public function save(): void
    {
        $perm = $this->form->form['id'] ? 'gopanel.slider.edit' : 'gopanel.slider.add';
        $this->authorize($perm);

        $this->form->validate();

        DB::transaction(fn () => $this->form->save());

        $this->modalOpen = false;
        $this->dispatch('notify', type: 'success', message: __('Yadda saxlanıldı'));
        $this->dispatch('slider-saved');
    }
}; ?>

<div>
    <x-gopanel.modal
        name="slider-form"
        :title="$form->form['id'] ? __('Slayderə düzəliş') : __('Yeni slayder')"
        size="md"
        wireOpen="modalOpen"
    >
        <form wire:submit.prevent="save">
            <x-gopanel.translatable-fields
                form="form"
                :fields="[
                    ['name' => 'title', 'label' => __('Başlıq'), 'type' => 'text'],
                    ['name' => 'description', 'label' => __('Məlumat'), 'type' => 'textarea'],
                    ['name' => 'link_title', 'label' => __('Link başlığı'), 'type' => 'text'],
                ]"
            />

            <div class="mb-3">
                <label class="form-label">{{ __('Link') }}</label>
                <input type="text" class="form-control" wire:model="form.form.link" placeholder="https://...">
                @error('form.form.link') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
            </div>

            <x-gopanel.file-upload
                name="form.upload"
                :label="__('Şəkil') . ' (800x520)'"
                accept="image/*"
                :existing="$form->form['image'] ? url($form->form['image']) : null"
            />

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">{{ __('Status') }}</label>
                    <select class="form-select" wire:model="form.form.is_active">
                        <option value="1">{{ __('Aktiv') }}</option>
                        <option value="0">{{ __('Deaktiv') }}</option>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">{{ __('Sıra') }}</label>
                    <input type="number" class="form-control" wire:model="form.form.sort_order" min="0">
                </div>
            </div>
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
