<?php

use Livewire\Attributes\Layout;
use Livewire\Component;

new
#[Layout('gopanel.layouts.main')]
class extends Component {
}; ?>

<div wire:ignore>
    @include('gopanel.pages.analytics.detail._utm_parameters_body')
</div>
