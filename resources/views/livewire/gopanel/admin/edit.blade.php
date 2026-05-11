<?php

use App\Models\Gopanel\Admin;
use Livewire\Attributes\Layout;
use Livewire\Component;

new
#[Layout('gopanel.layouts.main')]
class extends Component {
    public Admin $record;

    public function mount(Admin $admin): void
    {
        $this->record = $admin;
    }
}; ?>

<div class="page-content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0 font-size-18">{{ __('Adminə düzəliş') }}</h4>
                    <div class="page-title-right">
                        <a wire:navigate class="btn btn-secondary" href="{{ route('gopanel.admins.index') }}">
                            <i class="fas fa-arrow-left"></i> {{ __('Geri') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <livewire:gopanel.admin.form :record-id="$record->id" :key="'form-' . $record->id" />
    </div>
</div>
