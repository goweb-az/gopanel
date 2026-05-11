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
        <x-gopanel.page-header :title="__('Bloq yazısına düzəliş')" :showCreateButton="false">
            <x-slot:actions>
                <a wire:navigate class="btn btn-secondary" href="{{ route('gopanel.blog.index') }}">
                    <i class="fas fa-arrow-left"></i> {{ __('Geri') }}
                </a>
            </x-slot:actions>
        </x-gopanel.page-header>

        <livewire:gopanel.blog.form :record-id="$record->id" :key="'form-' . $record->id" />
    </div>
</div>
