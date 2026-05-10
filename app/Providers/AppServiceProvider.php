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
        \Illuminate\Pagination\Paginator::useBootstrapFive();

        // Promote the gopanel guard to default on Livewire update requests so that
        // @can, Auth::user() and Gate::check() resolve against the panel admin.
        \Livewire\Livewire::setUpdateRoute(function ($handle) {
            return \Illuminate\Support\Facades\Route::post('/livewire/update', $handle)
                ->middleware(['web', \App\Http\Middleware\UseGopanelGuardIfAuthenticated::class]);
        });
    }
}
