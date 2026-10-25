<?php

namespace App\Http\Controllers\Gopanel;

use App\Http\Controllers\GoPanelController;
use App\Services\Auth\LoginThrottle;
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
        // Limit: config/custom/security.php → login_throttle (max_attempts=0 → sönük)
        $throttle = new LoginThrottle('gopanel');

        try {
            if ($throttle->tooManyAttempts($request)) {
                $this->response_code = 429;
                throw new Exception($throttle->lockoutMessage($request));
            }

            $credentials = $request->only('email', 'password');
            $remember = $request->has('remember');
            if (Auth::guard("gopanel")->attempt($credentials, $remember)) {
                $throttle->clear($request);
                // Session fixation: girişdən əvvəlki sessiya ID-si girişdən sonra işləməsin
                $request->session()->regenerate();
                $this->response['redirect_to']  = route("gopanel.index");
                $this->success_response([], "Sistemə uğurla daxil oldunuz");
            } else {
                $throttle->hit($request);
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
