<?php

use App\Livewire\Forms\LlmsTxtForm;
use App\Models\Seo\LlmsTxt;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

new
#[Layout('gopanel.layouts.main')]
class extends Component {
    public LlmsTxtForm $form;

    public function mount(): void
    {
        $item = LlmsTxt::latest()->first() ?? new LlmsTxt();
        $this->form->setItem($item);
    }

    public function save(): void
    {
        $this->form->validate();

        DB::transaction(fn () => $this->form->save());

        $this->dispatch('notify', type: 'success', message: __('Yadda saxlanıldı'));
    }
}; ?>

<div class="page-content">
    <div class="container-fluid">
        <x-gopanel.page-header :title="__('llms.txt')" :showCreateButton="false" />

        <form wire:submit.prevent="save">
            <div class="card">
                <div class="card-body">
                    <x-gopanel.fullscreen-textarea
                        name="form.form.content"
                        :label="__('Məzmun')"
                        :rows="20"
                        placeholder="# llms.txt"
                    />
                </div>
            </div>

            <div class="text-end mt-3">
                <button type="submit" class="btn btn-primary">
                    <span class="lw-not-loading"><i class="fas fa-save me-1"></i> {{ __('Yadda saxla') }}</span>
                    <span class="lw-loading"><i class="fas fa-spinner fa-spin me-1"></i> {{ __('Saxlanır...') }}</span>
                </button>
            </div>
        </form>
    </div>
</div>
