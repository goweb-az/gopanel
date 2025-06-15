<?php


namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class AuthLogViewer
{
    public function handle($request, Closure $next)
    {
        if (!Auth::guard("gopanel")->check()) {
            return redirect('/')->with('error', "Xəta baş verdi!");
        }
        return $next($request);
    }
}
