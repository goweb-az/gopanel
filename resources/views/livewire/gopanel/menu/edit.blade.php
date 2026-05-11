<?php

use App\Models\Navigation\Menu;
use Livewire\Attributes\Layout;
use Livewire\Component;

new
#[Layout('gopanel.layouts.main')]
class extends Component {
    public Menu $record;

    public function mount(Menu $menu): void
    {
        $this->record = $menu;
    }
}; ?>

<div class="page-content">
    <div class="container-fluid">
        <x-gopanel.page-header :title="__('Menyuya düzəliş')" :showCreateButton="false">
            <x-slot:actions>
                <a wire:navigate class="btn btn-secondary" href="{{ route('gopanel.settings.menu.index', ['parent_id' => $record->parent_id]) }}">
                    <i class="fas fa-arrow-left"></i> {{ __('Geri') }}
                </a>
            </x-slot:actions>
        </x-gopanel.page-header>

        <livewire:gopanel.menu.form :record-id="$record->id" :key="'form-' . $record->id" />
    </div>
</div>
