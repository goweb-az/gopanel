<?php

use Livewire\Attributes\Layout;
use Livewire\Component;

new
#[Layout('gopanel.layouts.main')]
class extends Component {
    //
}; ?>

<div class="page-content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0 font-size-18">{{ __('Yeni vəzifə') }}</h4>
                    <div class="page-title-right">
                        <a wire:navigate class="btn btn-secondary" href="{{ route('gopanel.admins.roles.index') }}">
                            <i class="fas fa-arrow-left"></i> {{ __('Geri') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <livewire:gopanel.role.form />
    </div>
</div>
