<?php

use App\Models\Gopanel\CustomRole;
use Livewire\Attributes\Layout;
use Livewire\Component;

new
#[Layout('gopanel.layouts.main')]
class extends Component {
    public CustomRole $record;

    public function mount(CustomRole $role): void
    {
        $this->record = $role;
    }
}; ?>

<div class="page-content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0 font-size-18">{{ __('Vəzifəyə düzəliş') }}</h4>
                    <div class="page-title-right">
                        <a class="btn btn-secondary" href="{{ route('gopanel.admins.roles.index') }}">
                            <i class="fas fa-arrow-left"></i> {{ __('Geri') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <livewire:gopanel.role.form :record-id="$record->id" :key="'form-' . $record->id" />
    </div>
</div>
