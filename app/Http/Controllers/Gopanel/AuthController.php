<?php

namespace App\Http\Controllers\Gopanel;

use App\Http\Controllers\GoPanelController;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends GoPanelController
{

    public function __construct()
    {
        parent::__construct();
    }


    public function login(Request $request)
    {
        return view("gopanel.auth.login");
    }

    public function attempt(Request $request)
    {
        try {
            $credentials = $request->only('email', 'password');
            $remember = $request->has('remember');
            if (Auth::guard("gopanel")->attempt($credentials, $remember)) {
                $this->response['redirect_to']  = route("gopanel.index");
                $this->success_response([], "Sistemə uğurla daxil oldunuz");
            } else {
                $this->response_code = 403;
                throw new Exception("Məlumatlar düzgün göndərilməyib");
            }
        } catch (Exception $e) {
            $this->response['message'] .= $e->getMessage();
        }
        return $this->response_json();
    }



    public function logout(Request $request)
    {
        try {
            Auth::guard("gopanel")->logout();

            // Clear session after logout
            $request->session()->invalidate();

            // Create a new session ID
            $request->session()->regenerateToken();

            // Redirect the user to the login page or home page
            return redirect()->route('gopanel.auth.login');
        } catch (Exception $e) {
            throw $e;
        }
    }
}
