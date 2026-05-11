<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

new
#[Layout('gopanel.layouts.main')]
class extends Component {
    #[Url]
    public ?int $parent_id = null;
}; ?>

<div class="page-content">
    <div class="container-fluid">
        <x-gopanel.page-header :title="__('Yeni menyu')" :showCreateButton="false">
            <x-slot:actions>
                <a wire:navigate class="btn btn-secondary" href="{{ route('gopanel.settings.menu.index', ['parent_id' => $parent_id]) }}">
                    <i class="fas fa-arrow-left"></i> {{ __('Geri') }}
                </a>
            </x-slot:actions>
        </x-gopanel.page-header>

        <livewire:gopanel.menu.form :parent-id="$parent_id" />
    </div>
</div>
