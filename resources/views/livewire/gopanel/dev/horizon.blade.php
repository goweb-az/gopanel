<?php

use Livewire\Attributes\Layout;
use Livewire\Component;

new
#[\Livewire\Attributes\Lazy]
#[Layout('gopanel.layouts.main')]
class extends Component
{
    public bool $isRunning = true;
}; ?>

<div class="page-content p-0">
    <iframe src="{{ url(config('horizon.path', 'horizon')) }}" class="border-0 w-100" style="height: calc(100vh - 70px); display: block;" title="Horizon"></iframe>
</div>
