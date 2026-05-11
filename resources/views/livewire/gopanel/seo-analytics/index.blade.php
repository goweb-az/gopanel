<?php

use App\Livewire\Forms\SeoAnalyticsForm;
use App\Models\Seo\SeoAnalytics;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

new
#[Layout('gopanel.layouts.main')]
class extends Component {
    public SeoAnalyticsForm $form;

    public function mount(): void
    {
        $item = SeoAnalytics::latest()->first() ?? new SeoAnalytics();
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
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0 font-size-18">{{ __('SEO və analitika kodları') }}</h4>
                </div>
            </div>
        </div>

        <form wire:submit.prevent="save">
            <x-gopanel.tabs :tabs="[
                'head' => 'Head',
                'body' => 'Body',
                'footer' => 'Footer',
                'robots_txt' => 'robots.txt',
                'ai_txt' => 'ai.txt',
                'other' => __('Digər'),
            ]">
                <div class="card">
                    <div class="card-body">
                        @foreach (['head', 'body', 'footer', 'robots_txt', 'ai_txt', 'other'] as $key)
                            <x-gopanel.tab :name="$key">
                                <textarea class="form-control font-monospace" rows="18" wire:model="form.form.{{ $key }}"></textarea>
                                @error("form.form.{$key}") <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            </x-gopanel.tab>
                        @endforeach
                    </div>
                </div>
            </x-gopanel.tabs>

            <div class="text-end mt-3">
                <button type="submit" class="btn btn-primary">
                    <span class="lw-not-loading"><i class="fas fa-save me-1"></i> {{ __('Yadda saxla') }}</span>
                    <span class="lw-loading"><i class="fas fa-spinner fa-spin me-1"></i> {{ __('Saxlanır...') }}</span>
                </button>
            </div>
        </form>
    </div>
</div>
