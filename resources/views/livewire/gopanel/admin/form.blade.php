<?php

use App\Livewire\Concerns\AuthorizesGopanel;
use App\Livewire\Forms\AdminForm as RecordForm;
use App\Models\Gopanel\Admin as RecordModel;
use App\Models\Gopanel\CustomRole;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component {
    use AuthorizesGopanel, WithFileUploads;

    public RecordForm $form;

    public ?int $recordId = null;

    public string $permissionCreate = 'gopanel.admins.add';
    public string $permissionEdit   = 'gopanel.admins.edit';
    public string $indexRoute       = 'gopanel.admins.index';

    public function mount(): void
    {
        $record = $this->recordId ? RecordModel::with('roles')->findOrFail($this->recordId) : new RecordModel();

        $this->authorize($this->recordId ? $this->permissionEdit : $this->permissionCreate);

        $this->form->setItem($record);
    }

    #[Computed]
    public function roles()
    {
        return CustomRole::orderBy('name')->get(['id', 'name']);
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
                        <h5 class="card-title">{{ __('Hesab məlumatları') }}</h5>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">{{ __('Tam ad') }} <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" wire:model="form.form.full_name">
                                @error('form.form.full_name') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">{{ __('E-poçt') }} <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" wire:model="form.form.email">
                                @error('form.form.email') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    {{ __('Şifrə') }}
                                    @if (! $form->form['id']) <span class="text-danger">*</span> @endif
                                </label>
                                <input type="password" class="form-control" wire:model="form.password" autocomplete="new-password">
                                @error('form.password') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                @if ($form->form['id'])
                                    <small class="text-muted">{{ __('Boş buraxılarsa, şifrə dəyişməz') }}</small>
                                @endif
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">{{ __('Şifrə təsdiq') }}</label>
                                <input type="password" class="form-control" wire:model="form.password_confirmation" autocomplete="new-password">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">{{ __('Vəzifə') }}</label>
                                <select class="form-select" wire:model="form.form.role_id">
                                    <option value="">{{ __('Seçin...') }}</option>
                                    @foreach ($this->roles as $role)
                                        <option value="{{ $role->id }}">{{ $role->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">{{ __('Status') }}</label>
                                <select class="form-select" wire:model="form.form.is_active">
                                    <option value="1">{{ __('Aktiv') }}</option>
                                    <option value="0">{{ __('Deaktiv') }}</option>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">{{ __('Super admin') }}</label>
                                <select class="form-select" wire:model="form.form.is_super">
                                    <option value="0">{{ __('Xeyr') }}</option>
                                    <option value="1">{{ __('Bəli') }}</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">{{ __('Avatar') }}</h5>
                        <x-gopanel.file-upload
                            name="form.upload"
                            :label="__('Profil şəkli')"
                            accept="image/*"
                            :existing="$form->form['image'] ? \App\Helpers\Gopanel\FileUploader::url($form->form['image']) : null"
                        />
                        <small class="text-muted">{{ __('Boş buraxılarsa, avatar adından avtomatik yaradılır.') }}</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="text-end mt-3">
            <a href="{{ route($indexRoute) }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i> {{ __('Geri') }}
            </a>
            <button type="submit" class="btn btn-primary">
                <span class="lw-not-loading"><i class="fas fa-save me-1"></i> {{ __('Yadda saxla') }}</span>
                <span class="lw-loading"><i class="fas fa-spinner fa-spin me-1"></i> {{ __('Saxlanır...') }}</span>
            </button>
        </div>
    </form>
</div>
