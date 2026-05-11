<?php

use App\Facades\Locale;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component {
    public function switch(string $code): void
    {
        $codes = Locale::all()->pluck('code')->all();

        if (!in_array($code, $codes, true)) {
            return;
        }

        Session::put('gopanel.locale', $code);
        app()->setLocale($code);

        $this->redirect(request()->header('Referer') ?: url()->current(), navigate: true);
    }

    #[Computed]
    public function languages()
    {
        return Locale::all();
    }

    #[Computed]
    public function current()
    {
        $code = Locale::current();
        return $this->languages->firstWhere('code', $code) ?? $this->languages->first();
    }
}; ?>

<div
    x-data="{ open: false }"
    x-on:click.outside="open = false"
    x-on:keydown.escape.window="open = false"
    class="dropdown d-inline-block position-relative"
>
    <button type="button"
            x-on:click="open = !open"
            class="btn header-item waves-effect"
            aria-haspopup="true"
            :aria-expanded="open">
        <span class="text-uppercase fw-medium">{{ $this->current?->code ?? 'az' }}</span>
        <i class="mdi mdi-chevron-down ms-1"></i>
    </button>
    <div
        x-show="open"
        x-transition.opacity.duration.150ms
        x-cloak
        class="dropdown-menu dropdown-menu-end show"
        style="position:absolute; right:0; top:100%; margin-top:4px;"
    >
        @foreach ($this->languages as $lang)
            <button type="button"
                    wire:click="switch('{{ $lang->code }}')"
                    x-on:click="open = false"
                    class="dropdown-item d-flex align-items-center justify-content-between {{ $lang->code === ($this->current?->code) ? 'active' : '' }}">
                <span>{{ $lang->name }}</span>
                <span class="text-muted small text-uppercase">{{ $lang->code }}</span>
            </button>
        @endforeach
    </div>
</div>
