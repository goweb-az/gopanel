<?php

declare(strict_types=1);

namespace App\Services\Gopanel\Admins;

use App\Models\Gopanel\Admin;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Adminin PIN-i - həssas əməliyyatların (backup endirmə, silmə) ikinci təsdiqi.
 *
 * Niyə şifrədən ayrı: oğurlanmış sessiya cookie-si ilə panelə girən adam
 * şifrəni bilmir, amma sessiya artıq «daxil olub». PIN həmin anda ikinci
 * maneədir. Giriş vermir - yalnız daxil olmuş adminin konkret addımını
 * təsdiqləyir.
 *
 * Təsdiq SESSİYAYA yazılır (`ttl_minutes` müddətinə) - hər klikdə PIN
 * soruşmaq istifadəçini mexanizmi söndürməyə məcbur edərdi.
 *
 * Mənbə: texnotech `AdminPinService` - config yolu `custom.security.admin_pin`
 * və master açar (`enabled`) əlavə olunub.
 */
final class AdminPinService
{
    /** Təsdiqin sessiyadakı açarı. */
    private const SESSION_KEY = 'gopanel_pin_confirmed_at';

    /** Mexanizm ümumiyyətlə açıqdırmı (default sönük). */
    public function enabled(): bool
    {
        return (bool) config('custom.security.admin_pin.enabled', false);
    }

    public function minLength(): int
    {
        return max(4, (int) config('custom.security.admin_pin.length.min', 4));
    }

    public function maxLength(): int
    {
        return max($this->minLength(), (int) config('custom.security.admin_pin.length.max', 6));
    }

    public function ttlMinutes(): int
    {
        return max(1, (int) config('custom.security.admin_pin.ttl_minutes', 15));
    }

    public function set(Admin $admin, string $pin): void
    {
        $admin->forceFill([
            'pin'            => Hash::make($pin),
            'pin_updated_at' => now(),
        ])->save();

        $this->clearThrottle($admin);
        $this->confirm();
    }

    public function forget(Admin $admin): void
    {
        $admin->forceFill(['pin' => null, 'pin_updated_at' => null])->save();

        $this->clearThrottle($admin);
        $this->revoke();
    }

    public function check(Admin $admin, string $pin): bool
    {
        return $admin->hasPin() && Hash::check($pin, (string) $admin->pin);
    }

    /**
     * PIN təsdiqi axını: limit → yoxlama → sessiyaya yazma.
     *
     * Bir yerdə saxlanılır ki, AJAX modalı və ayrıca təsdiq səhifəsi eyni
     * qaydanı işlətsin (biri limitsiz qalmasın).
     *
     * @return array{ok: bool, status: int, message: string}
     */
    public function attemptConfirmation(Admin $admin, string $pin): array
    {
        if (!$admin->hasPin()) {
            return ['ok' => false, 'status' => 422, 'message' => 'PIN təyin edilməyib.'];
        }

        if ($this->tooManyAttempts($admin)) {
            return [
                'ok'      => false,
                'status'  => 429,
                'message' => 'Çox sayda səhv cəhd. ' . $this->secondsUntilRetry($admin) . ' saniyə gözləyin.',
            ];
        }

        if (!$this->check($admin, $pin)) {
            $this->hitThrottle($admin);

            return ['ok' => false, 'status' => 422, 'message' => 'PIN səhvdir.'];
        }

        $this->clearThrottle($admin);
        $this->confirm();

        return ['ok' => true, 'status' => 200, 'message' => 'PIN təsdiqləndi.'];
    }

    // ── Təsdiq vəziyyəti ─────────────────────────────────────────────────

    /**
     * Bu sorğu üçün PIN soruşulmalıdırmı.
     *
     * Mexanizm sönükdürsə və ya admin PIN təyin etməyibsə - heç vaxt.
     * Beləliklə middleware-i route-a bağlamaq mövcud axını sındırmır.
     */
    public function needsConfirmation(Admin $admin): bool
    {
        return $this->enabled() && $admin->hasPin() && !$this->isConfirmed();
    }

    public function isConfirmed(): bool
    {
        $at = session(self::SESSION_KEY);

        if (!is_numeric($at)) {
            return false;
        }

        return (int) $at > now()->subMinutes($this->ttlMinutes())->getTimestamp();
    }

    public function confirm(): void
    {
        session([self::SESSION_KEY => now()->getTimestamp()]);
    }

    public function revoke(): void
    {
        session()->forget(self::SESSION_KEY);
    }

    // ── Sürət limiti ─────────────────────────────────────────────────────
    // 4 rəqəmli PIN cəmi 10 000 variantdır - limitsiz cəhd onu dəqiqələrlə tapır.

    public function tooManyAttempts(Admin $admin): bool
    {
        return RateLimiter::tooManyAttempts(
            $this->throttleKey($admin),
            max(1, (int) config('custom.security.admin_pin.throttle.attempts', 5)),
        );
    }

    public function hitThrottle(Admin $admin): void
    {
        RateLimiter::hit(
            $this->throttleKey($admin),
            max(1, (int) config('custom.security.admin_pin.throttle.decay_minutes', 5)) * 60,
        );
    }

    public function secondsUntilRetry(Admin $admin): int
    {
        return RateLimiter::availableIn($this->throttleKey($admin));
    }

    public function clearThrottle(Admin $admin): void
    {
        RateLimiter::clear($this->throttleKey($admin));
    }

    private function throttleKey(Admin $admin): string
    {
        return 'gopanel-pin:' . $admin->getKey();
    }
}
