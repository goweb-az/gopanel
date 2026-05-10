<?php

namespace App\Livewire\Concerns;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

/**
 * Authorize Livewire actions against the `gopanel` auth guard.
 *
 * Livewire's update endpoint runs under the default web guard, so the parent
 * Component::authorize() resolves Auth::user() as null. Routes registered
 * under the gopanel middleware authenticate via Auth::guard('gopanel'), and
 * during HTTP requests Auth::shouldUse('gopanel') is set, but the Livewire
 * update POST does not pass through that middleware. This trait routes
 * authorization through Gate::forUser($gopanelUser).
 */
trait AuthorizesGopanel
{
    public function authorize($ability, $arguments = [])
    {
        $user = Auth::guard('gopanel')->user() ?? Auth::user();

        if (! $user) {
            throw new AuthorizationException('Unauthenticated.');
        }

        return Gate::forUser($user)->authorize($ability, $arguments);
    }
}
