<?php

namespace App\Actions\Gopanel\Auth;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Auth;
use Lorisleiva\Actions\Concerns\AsAction;

class LoginAdminAction
{
    use AsAction;

    /**
     * @throws AuthenticationException when credentials are invalid.
     */
    public function handle(string $email, string $password, bool $remember = false): void
    {
        if (! Auth::guard('gopanel')->attempt(['email' => $email, 'password' => $password], $remember)) {
            throw new AuthenticationException('Məlumatlar düzgün göndərilməyib');
        }
    }
}
