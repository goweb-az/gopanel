<?php

namespace App\Http\Middleware;

use Illuminate\Cookie\Middleware\EncryptCookies as Middleware;

class EncryptCookies extends Middleware
{
    /**
     * The names of the cookies that should not be encrypted.
     *
     * @var array<int, string>
     */
    protected $except = [
        // Qlobal RestrictDevDomain bu cookie-ni EncryptCookies-dən əvvəl xam oxuyur (dəyəri HMAC-dır)
        \App\Http\Middleware\Security\RestrictDevDomain::UNLOCK_COOKIE,
    ];
}
