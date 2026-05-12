<?php

use Livewire\Attributes\Layout;
use Livewire\Component;

new
#[\Livewire\Attributes\Lazy]
#[Layout('gopanel.layouts.main')]
class extends Component {}; ?>

<div class="page-content">
    <div class="container-fluid">
        <div class="d-flex flex-column overflow-hidden rounded border bg-white" style="height: calc(100vh - 8rem);">
            <div class="flex-grow-1">
                <iframe src="{{ url(config('telescope.path', 'telescope')) }}" class="h-100 w-100 border-0" title="Telescope"></iframe>
            </div>
        </div>
    </div>
</div>
