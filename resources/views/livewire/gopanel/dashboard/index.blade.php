<?php

use Livewire\Attributes\Layout;
use Livewire\Component;

new
#[Layout('gopanel.layouts.main')]
class extends Component {
}; ?>

<div class="page-content">
    <div class="container-fluid">
        <x-gopanel.page-header :title="__('Əsas səhifə')" :showCreateButton="false" />
    </div>
</div>
