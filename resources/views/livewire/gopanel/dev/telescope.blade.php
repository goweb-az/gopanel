<?php

use Livewire\Attributes\Layout;
use Livewire\Component;

new
#[\Livewire\Attributes\Lazy]
#[Layout('gopanel.layouts.main')]
class extends Component {}; ?>

<div class="page-content p-0">
    <iframe src="{{ url(config('telescope.path', 'telescope')) }}" class="border-0 w-100" style="height: calc(100vh - 70px); display: block;" title="Telescope"></iframe>
</div>
