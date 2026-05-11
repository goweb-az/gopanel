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
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0 font-size-18">{{ __('Yeni menyu') }}</h4>
                    <div class="page-title-right">
                        <a wire:navigate class="btn btn-secondary" href="{{ route('gopanel.settings.menu.index', ['parent_id' => $parent_id]) }}">
                            <i class="fas fa-arrow-left"></i> {{ __('Geri') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <livewire:gopanel.menu.form :parent-id="$parent_id" />
    </div>
</div>
