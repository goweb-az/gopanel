<?php

use App\Actions\Gopanel\Auth\ChangeAdminPasswordAction;
use App\Models\Gopanel\Admin;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

new
#[Layout('gopanel.layouts.main')]
class extends Component {
    public Admin $record;

    public string $current_password = '';

    #[Validate('required|string|min:6|confirmed')]
    public string $password = '';

    public string $password_confirmation = '';

    public function mount(): void
    {
        $this->record = Auth::guard('gopanel')->user();
    }

    public function save(): void
    {
        $this->validate([
            'current_password'      => ['required', 'string'],
            'password'              => ['required', 'string', 'min:6', 'confirmed'],
            'password_confirmation' => ['required', 'string'],
        ]);

        if (! Hash::check($this->current_password, $this->record->password)) {
            $this->addError('current_password', __('Cari şifrə yanlışdır.'));
            return;
        }

        ChangeAdminPasswordAction::run($this->record, $this->password);

        $this->reset(['current_password', 'password', 'password_confirmation']);

        $this->dispatch('notify', type: 'success', message: __('Şifrə yeniləndi'));

        $this->redirectRoute('gopanel.profile.index', navigate: true);
    }
}; ?>

<div class="page-content">
    <div class="container-fluid">
        <x-gopanel.page-header :title="__('Şifrəni dəyiş')" :showCreateButton="false">
            <x-slot:actions>
                <a wire:navigate class="btn btn-secondary" href="{{ route('gopanel.profile.index') }}">
                    <i class="fas fa-arrow-left"></i> {{ __('Geri') }}
                </a>
            </x-slot:actions>
        </x-gopanel.page-header>

        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="card">
                    <div class="card-body">
                        <form wire:submit.prevent="save">
                            <div class="mb-3">
                                <label class="form-label">{{ __('Cari şifrə') }} <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" wire:model="current_password" autocomplete="current-password">
                                @error('current_password') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label">{{ __('Yeni şifrə') }} <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" wire:model="password" autocomplete="new-password">
                                @error('password') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label">{{ __('Yeni şifrə təsdiq') }} <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" wire:model="password_confirmation" autocomplete="new-password">
                            </div>

                            <div class="text-end">
                                <button type="submit" class="btn btn-primary">
                                    <span class="lw-not-loading"><i class="fas fa-save me-1"></i> {{ __('Yadda saxla') }}</span>
                                    <span class="lw-loading"><i class="fas fa-spinner fa-spin me-1"></i> {{ __('Saxlanır...') }}</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
