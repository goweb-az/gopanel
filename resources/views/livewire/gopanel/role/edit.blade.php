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
        <x-gopanel.page-header :title="__('Vəzifəyə düzəliş')" :showCreateButton="false">
            <x-slot:actions>
                <a wire:navigate class="btn btn-secondary" href="{{ route('gopanel.admins.roles.index') }}">
                    <i class="fas fa-arrow-left"></i> {{ __('Geri') }}
                </a>
            </x-slot:actions>
        </x-gopanel.page-header>

        <livewire:gopanel.role.form :record-id="$record->id" :key="'form-' . $record->id" />
    </div>
</div>
