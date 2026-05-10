<?php

use App\Actions\Gopanel\Auth\UpdateAdminProfileAction;
use App\Models\Gopanel\Admin;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

new
#[Layout('gopanel.layouts.main')]
class extends Component {
    use WithFileUploads;

    public Admin $record;

    #[Validate('required|string|max:120')]
    public string $full_name = '';

    #[Validate('required|email|max:160')]
    public string $email = '';

    public mixed $upload = null;

    public function mount(): void
    {
        $this->record    = Auth::guard('gopanel')->user();
        $this->full_name = $this->record->full_name ?? '';
        $this->email     = $this->record->email ?? '';
    }

    public function save(): void
    {
        $this->validate(array_merge($this->rules(), [
            'email'  => 'required|email|max:160|unique:admins,email,' . $this->record->id,
            'upload' => 'nullable|image|max:2048',
        ]));

        UpdateAdminProfileAction::run(
            $this->record,
            $this->full_name,
            $this->email,
            $this->upload,
        );

        $this->record = $this->record->refresh();
        $this->upload = null;

        $this->dispatch('notify', type: 'success', message: __('Profil yeniləndi'));
    }
}; ?>

<div class="page-content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0 font-size-18">{{ __('Profil') }}</h4>
                    <div class="page-title-right">
                        <a class="btn btn-secondary" href="{{ route('gopanel.profile.change-password.index') }}">
                            <i class="fas fa-key"></i> {{ __('Şifrəni dəyiş') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <form wire:submit.prevent="save">
            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">{{ __('Hesab məlumatları') }}</h5>

                            <div class="mb-3">
                                <label class="form-label">{{ __('Tam ad') }} <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" wire:model="full_name">
                                @error('full_name') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label">{{ __('E-poçt') }} <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" wire:model="email">
                                @error('email') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">{{ __('Avatar') }}</h5>
                            <x-gopanel.file-upload
                                name="upload"
                                :label="__('Profil şəkli')"
                                accept="image/*"
                                :existing="$record->image ? \App\Helpers\Gopanel\FileUploader::url($record->image) : null"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <div class="text-end mt-3">
                <button type="submit" class="btn btn-primary">
                    <span class="lw-not-loading"><i class="fas fa-save me-1"></i> {{ __('Yadda saxla') }}</span>
                    <span class="lw-loading"><i class="fas fa-spinner fa-spin me-1"></i> {{ __('Saxlanır...') }}</span>
                </button>
            </div>
        </form>
    </div>
</div>
