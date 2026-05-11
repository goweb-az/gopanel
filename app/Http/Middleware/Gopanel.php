<?php

namespace App\Http\Middleware;

use App\Facades\Locale;
use Closure;
use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class Gopanel extends Middleware
{
    public function handle($request, Closure $next, ...$guards)
    {
        if (!Auth::guard("gopanel")->check()) {
            return redirect()->route('gopanel.auth.login');
        }

        Auth::shouldUse('gopanel');

        $codes = Locale::all()->pluck('code')->all();
        $stored = Session::get('gopanel.locale');

        if (in_array($stored, $codes, true)) {
            App::setLocale($stored);
        }

        return $next($request);
    }
}
