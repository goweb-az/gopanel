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

        <form wire:submit.prevent="save" x-data="{ tab: 'head' }">
            <div class="card">
                <div class="card-body">
                    <ul class="nav nav-tabs mb-3">
                        @foreach (['head' => 'Head', 'body' => 'Body', 'footer' => 'Footer', 'robots_txt' => 'robots.txt', 'ai_txt' => 'ai.txt', 'other' => __('Digər')] as $key => $label)
                            <li class="nav-item">
                                <button type="button" class="nav-link" :class="tab === '{{ $key }}' ? 'active' : ''"
                                    x-on:click.prevent="tab = '{{ $key }}'">
                                    {{ $label }}
                                </button>
                            </li>
                        @endforeach
                    </ul>

                    @foreach (['head', 'body', 'footer', 'robots_txt', 'ai_txt', 'other'] as $key)
                        <div x-show="tab === '{{ $key }}'" x-cloak>
                            <textarea class="form-control font-monospace" rows="18" wire:model="form.form.{{ $key }}"></textarea>
                            @error("form.form.{$key}") <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>
                    @endforeach
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
