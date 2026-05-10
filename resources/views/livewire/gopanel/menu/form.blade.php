<?php

use App\Enums\Common\Menu\MenuPositionEnum;
use App\Enums\Common\Menu\MenuTypeEnum;
use App\Livewire\Concerns\AuthorizesGopanel;
use App\Livewire\Forms\MenuForm as RecordForm;
use App\Models\Navigation\Menu as RecordModel;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

new class extends Component {
    use AuthorizesGopanel;

    public RecordForm $form;

    public ?int $recordId = null;

    public ?int $parentId = null;

    public string $permissionCreate = 'gopanel.settings.menu.add';
    public string $permissionEdit   = 'gopanel.settings.menu.edit';
    public string $indexRoute       = 'gopanel.settings.menu.index';

    public function mount(): void
    {
        $record = $this->recordId ? RecordModel::findOrFail($this->recordId) : new RecordModel();

        if (! $this->recordId && $this->parentId) {
            $record->parent_id = $this->parentId;
        }

        $this->authorize($this->recordId ? $this->permissionEdit : $this->permissionCreate);

        $this->form->setItem($record);
    }

    public function save(): void
    {
        $this->authorize($this->form->form['id'] ? $this->permissionEdit : $this->permissionCreate);

        $this->form->validate();

        DB::transaction(fn () => $this->form->save());

        $this->dispatch('notify', type: 'success', message: __('Yadda saxlanıldı'));

        $this->redirect(route($this->indexRoute, ['parent_id' => $this->form->form['parent_id']]), navigate: true);
    }
}; ?>

<div>
    <form wire:submit.prevent="save">
        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">{{ __('Mətn') }}</h5>
                        <x-gopanel.translatable-fields
                            form="form"
                            :fields="[
                                ['name' => 'title', 'label' => __('Başlıq'), 'type' => 'text'],
                                ['name' => 'description', 'label' => __('Təsvir'), 'type' => 'textarea'],
                                ['name' => 'slug', 'label' => __('Slug'), 'type' => 'text'],
                            ]"
                        />
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">{{ __('Parametrlər') }}</h5>

                        <div class="mb-3">
                            <label class="form-label">{{ __('Tip') }}</label>
                            <select class="form-select" wire:model="form.form.type">
                                @foreach (MenuTypeEnum::cases() as $case)
                                    <option value="{{ $case->value }}">{{ ucfirst($case->value) }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">{{ __('Mövqe') }}</label>
                            <select class="form-select" wire:model="form.form.position">
                                @foreach (MenuPositionEnum::cases() as $case)
                                    <option value="{{ $case->value }}">{{ $case->value }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">{{ __('Route adı') }}</label>
                            <input type="text" class="form-control" wire:model="form.form.route_name">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">{{ __('Funksiya adı') }}</label>
                            <input type="text" class="form-control" wire:model="form.form.function_name">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">{{ __('Sıra') }}</label>
                            <input type="number" min="0" class="form-control" wire:model="form.form.sort_order">
                        </div>

                        <div class="form-check form-switch mb-2">
                            <input type="checkbox" class="form-check-input" id="menuActive" wire:model="form.form.is_active">
                            <label class="form-check-label" for="menuActive">{{ __('Aktiv') }}</label>
                        </div>

                        <div class="form-check form-switch">
                            <input type="checkbox" class="form-check-input" id="menuDropdown" wire:model="form.form.is_dropdown">
                            <label class="form-check-label" for="menuDropdown">{{ __('Dropdown') }}</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="text-end mt-3">
            <a href="{{ route($indexRoute, ['parent_id' => $form->form['parent_id']]) }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i> {{ __('Geri') }}
            </a>
            <button type="submit" class="btn btn-primary">
                <span class="lw-not-loading"><i class="fas fa-save me-1"></i> {{ __('Yadda saxla') }}</span>
                <span class="lw-loading"><i class="fas fa-spinner fa-spin me-1"></i> {{ __('Saxlanır...') }}</span>
            </button>
        </div>
    </form>
</div>
