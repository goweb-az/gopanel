<?php

namespace App\Providers;

use App\Helpers\Gopanel\GoPanelHelper;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton('gopanel', function ($app) {
            return new GoPanelHelper();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
