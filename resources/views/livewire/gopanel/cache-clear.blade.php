<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;

new class extends Component {
    public function clear(string $type = 'basic'): void
    {
        $messageKey = match ($type) {
            'basic' => 'Keş təmizləndi',
            'route' => 'Route keşi təmizləndi',
            'config' => 'Config keşi təmizləndi',
            'view' => 'View keşi təmizləndi',
            'all' => 'Bütün keş təmizləndi',
            default => null,
        };

        if (is_null($messageKey)) {
            return;
        }

        match ($type) {
            'basic' => [Cache::flush(), Artisan::call('cache:clear')],
            'route' => Artisan::call('route:clear'),
            'config' => Artisan::call('config:clear'),
            'view' => Artisan::call('view:clear'),
            'all' => [Cache::flush(), Artisan::call('optimize:clear')],
        };

        $this->dispatch('notify', type: 'success', message: __($messageKey));
    }
}; ?>

<div
    x-data="{ open: false }"
    x-on:click.outside="open = false"
    x-on:keydown.escape.window="open = false"
    class="clear-cache-div position-relative"
>
    <div class="btn-group">
        <button type="button"
                class="btn btn-danger waves-effect waves-light"
                wire:click="clear('basic')"
                wire:confirm="{{ __('Silmək istədiyinizə əminsiniz?') }}">
            <span class="lw-not-loading"><i class="fas fa-recycle"></i> {{ __('Keşi təmizlə') }}</span>
            <span class="lw-loading"><i class="fas fa-spinner fa-spin"></i> {{ __('Saxlanır...') }}</span>
        </button>
        <button type="button"
                class="btn btn-danger dropdown-toggle dropdown-toggle-split"
                x-on:click="open = !open"
                aria-haspopup="true"
                :aria-expanded="open">
            <i class="mdi mdi-chevron-down"></i>
        </button>
        <div
            x-show="open"
            x-transition.opacity.duration.150ms
            x-cloak
            class="dropdown-menu show"
            style="position:absolute; left:0; top:100%; margin-top:4px;"
        >
            <button type="button"
                    class="dropdown-item"
                    wire:click="clear('route')"
                    x-on:click="open = false"
                    wire:confirm="{{ __('Silmək istədiyinizə əminsiniz?') }}">
                <i class="fas fa-project-diagram me-2"></i> {{ __('Route cache təmizlə') }}
            </button>
            <button type="button"
                    class="dropdown-item"
                    wire:click="clear('config')"
                    x-on:click="open = false"
                    wire:confirm="{{ __('Silmək istədiyinizə əminsiniz?') }}">
                <i class="fas fa-cogs me-2"></i> {{ __('Config cache təmizlə') }}
            </button>
            <button type="button"
                    class="dropdown-item"
                    wire:click="clear('view')"
                    x-on:click="open = false"
                    wire:confirm="{{ __('Silmək istədiyinizə əminsiniz?') }}">
                <i class="fas fa-eye me-2"></i> {{ __('View cache təmizlə') }}
            </button>
            <button type="button"
                    class="dropdown-item"
                    wire:click="clear('all')"
                    x-on:click="open = false"
                    wire:confirm="{{ __('Silmək istədiyinizə əminsiniz?') }}">
                <i class="fas fa-broom me-2"></i> {{ __('Hamısını təmizlə') }}
            </button>
        </div>
    </div>
</div>
