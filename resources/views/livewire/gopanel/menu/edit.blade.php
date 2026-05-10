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
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0 font-size-18">{{ __('Menyuya düzəliş') }}</h4>
                    <div class="page-title-right">
                        <a class="btn btn-secondary" href="{{ route('gopanel.settings.menu.index', ['parent_id' => $record->parent_id]) }}">
                            <i class="fas fa-arrow-left"></i> {{ __('Geri') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <livewire:gopanel.menu.form :record-id="$record->id" :key="'form-' . $record->id" />
    </div>
</div>
