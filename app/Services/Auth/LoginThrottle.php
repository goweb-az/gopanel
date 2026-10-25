<?php

declare(strict_types=1);

namespace App\Services\Auth;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/**
 * Uğursuz giriş cəhdlərinin limiti (`config/custom/security.php` → `login_throttle`).
 *
 * Niyə: config-də limit uzun müddət elan olunmuşdu, amma heç yerdə tətbiq
 * edilmirdi - panel girişi limitsiz parol yoxlamasına açıq idi.
 *
 * Açar `e-poçt|IP` cütüdür: yalnız e-poçta görə bağlansa, kənar adam istənilən
 * adminin hesabını qəsdən kilidləyə bilərdi; yalnız IP-yə görə bağlansa,
 * bir IP-dən bütün admin siyahısını yoxlamaq olardı.
 *
 * `max_attempts <= 0` → limit sönük (köhnə davranış).
 * Admin paneldən başqa giriş formalarında da (sayt, API) eyni sinif işlədilə
 * bilər - `$scope` açarları qarışdırmır.
 */
final class LoginThrottle
{
    public function __construct(private readonly string $scope = 'gopanel')
    {
    }

    public function enabled(): bool
    {
        return $this->maxAttempts() > 0;
    }

    public function tooManyAttempts(Request $request): bool
    {
        return $this->enabled() && RateLimiter::tooManyAttempts($this->key($request), $this->maxAttempts());
    }

    public function hit(Request $request): void
    {
        if ($this->enabled()) {
            RateLimiter::hit($this->key($request), $this->decayMinutes() * 60);
        }
    }

    public function clear(Request $request): void
    {
        RateLimiter::clear($this->key($request));
    }

    public function secondsUntilRetry(Request $request): int
    {
        return RateLimiter::availableIn($this->key($request));
    }

    /** İstifadəçiyə göstərilən mesaj - dəqiqə ilə, yuxarı yuvarlaqlaşdırılmış. */
    public function lockoutMessage(Request $request): string
    {
        $minutes = max(1, (int) ceil($this->secondsUntilRetry($request) / 60));

        return "Çox sayda uğursuz cəhd. {$minutes} dəqiqə sonra yenidən yoxlayın.";
    }

    private function maxAttempts(): int
    {
        return (int) config('custom.security.login_throttle.max_attempts', 5);
    }

    private function decayMinutes(): int
    {
        return max(1, (int) config('custom.security.login_throttle.decay_minutes', 10));
    }

    private function key(Request $request): string
    {
        return 'login:' . $this->scope . ':' . Str::lower((string) $request->input('email')) . '|' . $request->ip();
    }
}
