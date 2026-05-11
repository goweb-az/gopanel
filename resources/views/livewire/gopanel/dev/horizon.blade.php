<?php

use Illuminate\Support\Facades\Artisan;
use Livewire\Attributes\Layout;
use Livewire\Component;

new
#[\Livewire\Attributes\Lazy]
#[Layout('gopanel.layouts.main')]
class extends Component
{
    public function pause(): void
    {
        Artisan::call('horizon:pause');
        $this->dispatch('notify', type: 'success', message: __('Horizon dayandırıldı'));
    }

    public function continueHorizon(): void
    {
        Artisan::call('horizon:continue');
        $this->dispatch('notify', type: 'success', message: __('Horizon davam etdirildi'));
    }

    public function terminate(): void
    {
        Artisan::call('horizon:terminate');
        $this->dispatch('notify', type: 'success', message: __('Horizon yenidən başladılır'));
    }

    public function snapshot(): void
    {
        Artisan::call('horizon:snapshot');
        $this->dispatch('notify', type: 'success', message: __('Snapshot alındı'));
    }

    public function clearFailed(): void
    {
        Artisan::call('horizon:clear', ['--queue' => 'failed']);
        $this->dispatch('notify', type: 'success', message: __('Failed növbə təmizləndi'));
    }
}; ?>

<div class="page-content">
    <div class="container-fluid">
        <div class="d-flex flex-column overflow-hidden rounded border bg-white" style="height: calc(100vh - 8rem);">
            <div class="d-flex align-items-center justify-content-between border-bottom px-3 py-2">
                <h3 class="font-size-13 fw-medium text-dark mb-0">{{ __('Horizon — Növbə monitorinqi') }}</h3>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-warning btn-sm" wire:click="pause" wire:loading.attr="disabled" wire:target="pause">
                        <span wire:loading.remove wire:target="pause"><i class="fas fa-pause me-1"></i> {{ __('Dayandır') }}</span>
                        <span wire:loading wire:target="pause"><i class="fas fa-spinner fa-spin me-1"></i> {{ __('Dayandırılır...') }}</span>
                    </button>
                    <button type="button" class="btn btn-success btn-sm" wire:click="continueHorizon" wire:loading.attr="disabled" wire:target="continueHorizon">
                        <span wire:loading.remove wire:target="continueHorizon"><i class="fas fa-play me-1"></i> {{ __('Davam et') }}</span>
                        <span wire:loading wire:target="continueHorizon"><i class="fas fa-spinner fa-spin me-1"></i> {{ __('Davam etdirilir...') }}</span>
                    </button>
                    <button type="button" class="btn btn-primary btn-sm" wire:click="snapshot" wire:loading.attr="disabled" wire:target="snapshot">
                        <span wire:loading.remove wire:target="snapshot"><i class="fas fa-camera me-1"></i> {{ __('Snapshot') }}</span>
                        <span wire:loading wire:target="snapshot"><i class="fas fa-spinner fa-spin me-1"></i> {{ __('Alınır...') }}</span>
                    </button>
                    <button type="button" class="btn btn-danger btn-sm" wire:click="terminate" wire:loading.attr="disabled" wire:target="terminate">
                        <span wire:loading.remove wire:target="terminate"><i class="fas fa-redo me-1"></i> {{ __('Terminate') }}</span>
                        <span wire:loading wire:target="terminate"><i class="fas fa-spinner fa-spin me-1"></i> {{ __('Sonlandırılır...') }}</span>
                    </button>
                    <button type="button" class="btn btn-outline-danger btn-sm" wire:click="clearFailed" wire:loading.attr="disabled" wire:target="clearFailed"
                            wire:confirm="{{ __('Failed növbədəki bütün işlər silinəcək. Əminsiniz?') }}">
                        <span wire:loading.remove wire:target="clearFailed"><i class="fas fa-trash me-1"></i> {{ __('Failed təmizlə') }}</span>
                        <span wire:loading wire:target="clearFailed"><i class="fas fa-spinner fa-spin me-1"></i> {{ __('Təmizlənir...') }}</span>
                    </button>
                </div>
            </div>

            <div class="flex-grow-1">
                <iframe src="{{ url(config('horizon.path', 'horizon')) }}" class="h-100 w-100 border-0" title="Horizon"></iframe>
            </div>
        </div>
    </div>
</div>
