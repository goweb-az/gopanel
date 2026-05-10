<?php

use App\Livewire\Concerns\AuthorizesGopanel;
use App\Livewire\Forms\ProductForm as RecordForm;
use App\Models\Site\Product as RecordModel;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component {
    use AuthorizesGopanel, WithFileUploads;

    public RecordForm $form;

    public ?int $recordId = null;

    public string $permissionCreate = 'gopanel.products.add';
    public string $permissionEdit   = 'gopanel.products.edit';
    public string $indexRoute       = 'gopanel.products.index';

    public function mount(): void
    {
        $record = $this->recordId ? RecordModel::findOrFail($this->recordId) : new RecordModel();

        $this->authorize($this->recordId ? $this->permissionEdit : $this->permissionCreate);

        $this->form->setItem($record);
    }

    public function save(): void
    {
        $this->authorize($this->form->form['id'] ? $this->permissionEdit : $this->permissionCreate);

        $this->form->validate();

        DB::transaction(fn () => $this->form->save());

        $this->dispatch('notify', type: 'success', message: __('Yadda saxlanıldı'));

        $this->redirectRoute($this->indexRoute, navigate: true);
    }
}; ?>

<div>
    <form wire:submit.prevent="save">
        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">{{ __('Məzmun') }}</h5>
                        <x-gopanel.translatable-fields
                            form="form"
                            :fields="[
                                ['name' => 'title', 'label' => __('Başlıq'), 'type' => 'text'],
                                ['name' => 'short_description', 'label' => __('Qısa təsvir'), 'type' => 'textarea'],
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
                        <h5 class="card-title">{{ __('Şəkil') }}</h5>
                        <x-gopanel.file-upload
                            name="form.upload"
                            :label="__('Əsas şəkil')"
                            accept="image/*"
                            :existing="$form->form['image'] ? asset($form->form['image']) : null"
                        />
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">{{ __('Qiymət və status') }}</h5>

                        <div class="mb-3">
                            <label class="form-label">{{ __('Qiymət') }} <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0" class="form-control" wire:model="form.form.price">
                            @error('form.form.price') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">{{ __('Endirim') }}</label>
                            <input type="number" step="0.01" min="0" class="form-control" wire:model="form.form.discount" placeholder="—">
                            @error('form.form.discount') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">{{ __('Status') }}</label>
                            <select class="form-select" wire:model="form.form.is_active">
                                <option value="1">{{ __('Aktiv') }}</option>
                                <option value="0">{{ __('Deaktiv') }}</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="text-end mt-3">
            <a href="{{ route($indexRoute) }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i> {{ __('Geri') }}
            </a>
            <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="save"><i class="fas fa-save me-1"></i> {{ __('Yadda saxla') }}</span>
                <span wire:loading wire:target="save"><i class="fas fa-spinner fa-spin me-1"></i> {{ __('Saxlanır...') }}</span>
            </button>
        </div>
    </form>
</div>
