<?php

use App\Models\Site\Blog;
use Livewire\Attributes\Layout;
use Livewire\Component;

new
#[Layout('gopanel.layouts.main')]
class extends Component {
    public Blog $record;

    public function mount(Blog $blog): void
    {
        $this->record = $blog;
    }
}; ?>

<div class="page-content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0 font-size-18">{{ __('Bloq yazısına düzəliş') }}</h4>
                    <div class="page-title-right">
                        <a wire:navigate class="btn btn-secondary" href="{{ route('gopanel.blog.index') }}">
                            <i class="fas fa-arrow-left"></i> {{ __('Geri') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <livewire:gopanel.blog.form :record-id="$record->id" :key="'form-' . $record->id" />
    </div>
</div>
