<?php

namespace App\Http\Controllers\Gopanel;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    /**
     * Stateless GET that ends the gopanel session and redirects to the
     * Livewire-rendered login page. Kept as a controller because Livewire
     * doesn't model session-killing requests cleanly.
     */
    public function logout(Request $request)
    {
        Auth::guard('gopanel')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('gopanel.auth.login');
    }
}
