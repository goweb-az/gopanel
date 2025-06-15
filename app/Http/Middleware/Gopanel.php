<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class Gopanel extends Middleware
// class Panel
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  mixed  ...$guards
     * @return mixed
     */

    public function handle($request, Closure $next, ...$guards)
    {
        // $this->authenticate($request, $guards);
        if (!Auth::guard("gopanel")->check()) {
            return redirect()->route('gopanel.auth.login');
        }
        return $next($request);
    }
}
