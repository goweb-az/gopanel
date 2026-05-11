<?php

namespace App\Providers;

use App\Models\Geography\Language;
use App\Services\Locale\LocaleManager;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(LocaleManager::class);

        if (config('app.env') === 'local') {
            $this->app->register(\App\Providers\HorizonServiceProvider::class);
            $this->app->register(\App\Providers\TelescopeServiceProvider::class);
        }
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

        Language::saved(fn () => app(LocaleManager::class)->clearCache());
        Language::deleted(fn () => app(LocaleManager::class)->clearCache());
    }
}
