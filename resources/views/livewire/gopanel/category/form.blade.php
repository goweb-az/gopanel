<?php

use App\Enums\Common\SocialIconTypeEnum;
use App\Livewire\Concerns\AuthorizesGopanel;
use App\Livewire\Forms\CategoryForm as RecordForm;
use App\Models\Navigation\Category as RecordModel;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component {
    use AuthorizesGopanel, WithFileUploads;

    public RecordForm $form;

    public bool $modalOpen = false;

    public ?int $defaultParentId = null;

    public string $permissionCreate = 'gopanel.categories.add';
    public string $permissionEdit   = 'gopanel.categories.edit';
    public string $eventSaved       = 'category-saved';

    public function mount(): void
    {
        $this->form->setItem(new RecordModel());
    }

    #[On('category-form-open')]
    public function openForm(?int $id = null, ?int $parentId = null): void
    {
        $this->resetValidation();

        $record = $id ? RecordModel::findOrFail($id) : new RecordModel();

        if (! $id && $parentId) {
            $record->parent_id = $parentId;
        }

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
        name="category-form"
        :title="$form->form['id'] ? __('Kateqoriyaya düzəliş') : __('Yeni kateqoriya')"
        size="lg"
        wireOpen="modalOpen"
    >
        <form wire:submit.prevent="save">
            <x-gopanel.translatable-fields
                form="form"
                :fields="[
                    ['name' => 'name', 'label' => __('Adı'), 'type' => 'text'],
                    ['name' => 'description', 'label' => __('Təsvir'), 'type' => 'textarea'],
                    ['name' => 'slug', 'label' => __('Slug'), 'type' => 'text'],
                ]"
            />

            <hr>
            <h6>{{ __('İkon') }}</h6>

            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">{{ __('İkon növü') }}</label>
                    <select class="form-select" wire:model.live="form.form.icon_type">
                        @foreach (SocialIconTypeEnum::cases() as $case)
                            <option value="{{ $case->value }}">{{ ucfirst($case->value) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">{{ __('Rəng') }}</label>
                    <input type="text" class="form-control" wire:model="form.form.color" placeholder="#0d6efd">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">{{ __('Sıra') }}</label>
                    <input type="number" min="0" class="form-control" wire:model="form.form.sort_order">
                </div>
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
                    <label class="form-label">{{ __('İkon dəyəri') }} <small class="text-muted">({{ __('class adı və ya SVG') }})</small></label>
                    <textarea class="form-control" rows="2" wire:model="form.form.icon"></textarea>
                </div>
            @endif

            <div class="row">
                <div class="col-md-4">
                    <div class="form-check form-switch mb-2">
                        <input type="checkbox" class="form-check-input" id="catActive" wire:model="form.form.is_active">
                        <label class="form-check-label" for="catActive">{{ __('Aktiv') }}</label>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-check form-switch mb-2">
                        <input type="checkbox" class="form-check-input" id="catHome" wire:model="form.form.show_in_home">
                        <label class="form-check-label" for="catHome">{{ __('Ana səhifədə göstər') }}</label>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-check form-switch mb-2">
                        <input type="checkbox" class="form-check-input" id="catMenu" wire:model="form.form.show_in_menu">
                        <label class="form-check-label" for="catMenu">{{ __('Menyuda göstər') }}</label>
                    </div>
                </div>
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
