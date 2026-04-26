<?php

namespace App\Listeners\Analytics;

use App\Events\Analytics\ClickRegistered;
use App\Services\Site\Seo\AnalyticsService;
use Illuminate\Support\Facades\Log;

class RegisterClickListener
{
    protected AnalyticsService $analytics;

    public function __construct(AnalyticsService $analytics)
    {
        $this->analytics = $analytics;
    }

    public function handle(ClickRegistered $event): void
    {
        try {
            $this->analytics->register($event->data);
        } catch (\Throwable $e) {
            Log::error('RegisterClickListener error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
