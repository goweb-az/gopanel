<?php

namespace App\Helpers\Common;

use App\Models\User\User;
use Exception;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Crypt;

/**
 * Class CrossAuth
 *
 * This helper class provides cross-subdomain authentication using cookies.
 * It encrypts user data into a secure cookie token and allows for:
 * - Setting token cookies across all subdomains
 * - Reading and decrypting token values
 * - Validating token and fetching authenticated user
 * - Logging out by deleting cross-domain cookies
 *
 * Use case: Shared login state between subdomains like app.yourdomain.loc and yourdomain.loc
 */
class CrossAuth
{
    /**
     * @var string $cookieKey Default cookie name for storing the encrypted token
     */
    protected static string $cookieKey = 'cross_a_token';

    /**
     * @var int $cookieMinutes Cookie lifetime in minutes (30 days = 43200 minutes)
     */
    protected static int $cookieMinutes = 43200;

    /**
     * Store the given token value in a cookie.
     *
     * @param string $token The token string to store
     * @param string|null $key Optional cookie name (default: self::$cookieKey)
     * @param bool $domainWide Whether the cookie should be shared across subdomains
     * @return void
     */
    protected static function storeTokenInCookie(string $token, ?string $key = null, bool $domainWide = true): void
    {
        $key = $key ?? self::$cookieKey;

        $path = '/';
        $domain = $domainWide ? '.' . parse_url(env('APP_URL'), PHP_URL_HOST) : null;

        // Secure: false (can be changed), HttpOnly: true
        Cookie::queue($key, $token, self::$cookieMinutes, $path, $domain, false, true);
    }

    /**
     * Set a secure cookie with encrypted user ID and timestamp.
     * Cookie will be available across all subdomains.
     *
     * @param User $user The user to be stored in the cookie
     * @param string|null $key Custom cookie name (optional)
     * @return string The encrypted token value
     */
    public static function setUser(User $user, ?string $key = null): string
    {
        $token = Crypt::encryptString(json_encode([
            'uid' => $user->uid,
            'timestamp' => now()->timestamp
        ]));

        self::storeTokenInCookie($token, $key);
        return $token;
    }

    /**
     * Set any given token into cookie (non-encrypted).
     *
     * @param string $token The token string
     * @param string|null $key Optional cookie name
     * @return string The token value
     */
    public static function setToken(string $token, ?string $key = null): string
    {
        self::storeTokenInCookie($token, $key);
        return $token;
    }


    /**
     * Retrieve the token value from cookie.
     *
     * @param string|null $key Cookie key to fetch (default: cross_a_token)
     * @return string|null The token if exists, otherwise null
     */
    public static function getToken(?string $key = null): ?string
    {
        $key = $key ?? self::$cookieKey;
        return Cookie::get($key);
    }

    /**
     * Decrypt and decode the token.
     *
     * @param string|null $token The encrypted token (optional)
     * @return array|string|null Returns:
     *   - array: if token is JSON data (contains user info)
     *   - string: if token is a plain encrypted string
     *   - null: if token is missing or decryption fails
     */
    public static function decrypt(?string $token = null): array|string|null
    {
        $token = $token ?? self::getToken(self::$cookieKey);
        if (!$token) {
            return null;
        }

        try {
            $decrypted = Crypt::decryptString($token);
            $decoded = json_decode($decrypted, true);

            return is_array($decoded) ? $decoded : $decrypted;
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Retrieve the user based on decrypted token's uid.
     *
     * @param string|null $token Optional encrypted token to use
     * @return User|null The matched User model or null if invalid
     */
    public static function user(?string $token = null): ?User
    {
        $token = $token ?? self::getToken();
        if (!$token) {
            return null;
        }

        $uid = self::decrypt($token)['uid'] ?? null;

        if (!$uid) {
            return null;
        }

        return User::where('uid', $uid)->first();
    }

    /**
     * Check if there is a valid logged-in user token.
     *
     * @return bool True if user is authenticated, false otherwise
     */
    public static function check(): bool
    {
        return self::getToken() !== null && self::user() !== null;
    }

    /**
     * Remove the token cookie from client.
     *
     * @param string|null $key Optional cookie name to remove
     * @return bool|null True if removed, null if queue fails
     */
    public static function forgetToken(?string $key = null): bool|null
    {
        return Cookie::queue(Cookie::forget($key ?? self::$cookieKey));
    }

    /**
     * Logout user by removing the cookie from all subdomains.
     *
     * @param string|null $key Optional cookie name
     * @return bool|null True if removed, null otherwise
     */
    public static function logout(?string $key = null): bool|null
    {
        $key = $key ?? self::$cookieKey;

        // Use same domain and path used during setUser to ensure deletion works
        $domain = '.' . parse_url(env('APP_URL'), PHP_URL_HOST);

        return Cookie::queue(Cookie::forget($key, '/', $domain));
    }
}
