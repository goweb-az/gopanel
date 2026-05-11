<?php

use App\Models\Analytics\AnalyticsBrowser;
use App\Models\Analytics\AnalyticsDevice;
use App\Models\Analytics\AnalyticsOperatingSystem;
use Livewire\Attributes\Layout;
use Livewire\Component;

new
#[Layout('gopanel.layouts.main')]
class extends Component {
    public array $viewData = [];

    public function mount(): void
    {
        $this->viewData = [
            'devices'    => AnalyticsDevice::all(),
            'operations' => AnalyticsOperatingSystem::all(),
            'browsers'   => AnalyticsBrowser::all(),
        ];
    }
}; ?>

<div wire:ignore>
    @include('gopanel.pages.analytics.detail._clicks_body', $this->viewData)
</div>
