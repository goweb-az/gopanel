<?php

use App\Enums\Common\SocialIconTypeEnum;
use App\Livewire\Concerns\AuthorizesGopanel;
use App\Livewire\Forms\ServiceForm as RecordForm;
use App\Models\Site\Service as RecordModel;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component {
    use AuthorizesGopanel, WithFileUploads;

    public RecordForm $form;

    public bool $modalOpen = false;

    public string $permissionCreate = 'gopanel.services.add';
    public string $permissionEdit   = 'gopanel.services.edit';
    public string $eventSaved       = 'service-saved';

    public function mount(): void
    {
        $this->form->setItem(new RecordModel());
    }

    #[On('service-form-open')]
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
        name="service-form"
        :title="$form->form['id'] ? __('Xidmətə düzəliş') : __('Yeni xidmət')"
        size="lg"
        wireOpen="modalOpen"
    >
        <form wire:submit.prevent="save">
            <x-gopanel.translatable-fields
                form="form"
                :fields="[
                    ['name' => 'title', 'label' => __('Başlıq'), 'type' => 'text'],
                    ['name' => 'short_description', 'label' => __('Qısa təsvir'), 'type' => 'textarea'],
                    ['name' => 'description', 'label' => __('Təsvir'), 'type' => 'textarea'],
                ]"
            />

            <hr>
            <h6>{{ __('İkon') }}</h6>

            <div class="mb-3">
                <label class="form-label">{{ __('İkon növü') }}</label>
                <select class="form-select" wire:model.live="form.form.icon_type">
                    @foreach (SocialIconTypeEnum::cases() as $case)
                        <option value="{{ $case->value }}">{{ ucfirst($case->value) }}</option>
                    @endforeach
                </select>
            </div>

            @if ($form->form['icon_type'] === SocialIconTypeEnum::Image->value)
                <x-gopanel.file-upload
                    name="form.iconUpload"
                    :label="__('İkon faylı')"
                    accept="image/*"
                    :existing="$form->form['icon'] ? asset($form->form['icon']) : null"
                />
            @else
                <div class="mb-3">
                    <label class="form-label">{{ __('İkon dəyəri') }} <small class="text-muted">({{ __('class adı, SVG və ya mətn') }})</small></label>
                    <textarea class="form-control" rows="2" wire:model="form.form.icon"></textarea>
                    @error('form.form.icon') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                </div>
            @endif

            <hr>
            <h6>{{ __('Şəkil') }}</h6>
            <x-gopanel.file-upload
                name="form.imageUpload"
                :label="__('Əsas şəkil')"
                accept="image/*"
                :existing="$form->form['image'] ? asset($form->form['image']) : null"
            />

            <div class="mb-3">
                <label class="form-label">{{ __('Sıra') }}</label>
                <input type="number" class="form-control" wire:model="form.form.sort_order" min="0">
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
