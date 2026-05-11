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
        <x-gopanel.page-header :title="__('Adminə düzəliş')" :showCreateButton="false">
            <x-slot:actions>
                <a wire:navigate class="btn btn-secondary" href="{{ route('gopanel.admins.index') }}">
                    <i class="fas fa-arrow-left"></i> {{ __('Geri') }}
                </a>
            </x-slot:actions>
        </x-gopanel.page-header>

        <livewire:gopanel.admin.form :record-id="$record->id" :key="'form-' . $record->id" />
    </div>
</div>
