<?php

use Illuminate\Support\Facades\Artisan;
use Livewire\Attributes\Layout;
use Livewire\Component;

new
#[\Livewire\Attributes\Lazy]
#[Layout('gopanel.layouts.main')]
class extends Component
{
    public function restart(): void
    {
        Artisan::call('pulse:restart');
        $this->dispatch('notify', type: 'success', message: __('Pulse işçiləri yenidən başladıldı'));
    }

    public function clear(): void
    {
        Artisan::call('pulse:clear');
        $this->dispatch('notify', type: 'success', message: __('Pulse verisi silindi'));
    }
}; ?>

<div class="page-content">
    <div class="container-fluid">
        <div class="d-flex flex-column overflow-hidden rounded border bg-white" style="height: calc(100vh - 8rem);">
            <div class="d-flex align-items-center justify-content-between border-bottom px-3 py-2">
                <h3 class="font-size-13 fw-medium text-dark mb-0">{{ __('Pulse — Performans monitorinqi') }}</h3>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-primary btn-sm" wire:click="restart" wire:loading.attr="disabled" wire:target="restart">
                        <span wire:loading.remove wire:target="restart"><i class="fas fa-redo me-1"></i> {{ __('Yenidən başlat') }}</span>
                        <span wire:loading wire:target="restart"><i class="fas fa-spinner fa-spin me-1"></i> {{ __('Başladılır...') }}</span>
                    </button>
                    <button type="button" class="btn btn-danger btn-sm" wire:click="clear" wire:loading.attr="disabled" wire:target="clear"
                            wire:confirm="{{ __('Pulse-da bütün verilər silinəcək. Əminsiniz?') }}">
                        <span wire:loading.remove wire:target="clear"><i class="fas fa-trash me-1"></i> {{ __('Hamısını sil') }}</span>
                        <span wire:loading wire:target="clear"><i class="fas fa-spinner fa-spin me-1"></i> {{ __('Silinir...') }}</span>
                    </button>
                </div>
            </div>

            <div class="flex-grow-1">
                <iframe src="{{ url(config('pulse.path', 'pulse')) }}" class="h-100 w-100 border-0" title="Pulse"></iframe>
            </div>
        </div>
    </div>
</div>
