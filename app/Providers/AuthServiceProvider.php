<?php

namespace App\Providers;

// use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        //
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        //eger adminin is_super columu 1 dirse o zaman butun role ve permissionlardan uzaq tutur
        Gate::before(function ($user, $ability) {

            // Gopanel guard'dan gelen istekler
            if (Auth::guard('gopanel')->check()) {
                return $user->is_super == 1 ? true : null;
            }
        });
    }
}
