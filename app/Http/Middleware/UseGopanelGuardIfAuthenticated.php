<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Livewire's /livewire/update endpoint runs only under the default web guard.
 * The gopanel panel authenticates via the `gopanel` guard. Without this middleware
 * any AJAX action that uses Auth::user(), Gate::check(), or @can resolves against
 * the empty web guard and returns false / null.
 *
 * If the request has an active gopanel session, we promote that guard to be the
 * request-default. This mirrors what Gopanel middleware does on regular page loads.
 */
class UseGopanelGuardIfAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::guard('gopanel')->check()) {
            Auth::shouldUse('gopanel');
        }

        return $next($request);
    }
}
