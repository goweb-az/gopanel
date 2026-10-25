<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Services\Activity\LogService;
use App\Services\Mail\MailService;
use App\Support\Contact\EmailAddress;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Birdəfəlik e-poçt təsdiq kodu (OTP): yarat → göndər → yoxla.
 *
 * Niyə keşdə, cədvəldə yox: kodun ömrü bir neçə dəqiqədir və tarixçəsi
 * lazım deyil - cədvəl yalnız təmizləmə cron-u və miqrasiya yükü gətirərdi.
 * Keş driver-i `array` OLMAMALIDIR (sorğular arası yaşamır) - canlıda
 * redis/database/file.
 *
 * Qorumalar (hamısı `config/custom/security.php` → `otp`):
 *  - təkrar göndərmə intervalı (spam / mail xərci);
 *  - səhv cəhd limiti - limitdən sonra kod ləğv edilir (brute-force);
 *  - uğurlu yoxlamadan sonra kod silinir (təkrar istifadə olunmur);
 *  - kod heç bir mühitdə jurnala yazılmır, ünvan maskalanır.
 *
 * `purpose` eyni ünvan üçün müxtəlif axınları ayırır ('verify', 'reset',
 * 'login'...): qeydiyyat kodu şifrə sıfırlamaq üçün işlədilə bilməz.
 *
 * Mənbə: texnotech `OtpService` - sabitlər config-ə çıxarılıb, `Mail::raw`
 * əvəzinə brendli `MailService::sendOtpEmail()`, `LogChannelEnum` əvəzinə
 * config-dəki kanal.
 */
final class OtpService
{
    /**
     * Kod yaradır və göndərir.
     *
     * @return array{sent: bool, retry_after: int}
     *         `sent=false` → gözləmə müddəti bitməyib, `retry_after` saniyə qalıb.
     */
    public function send(string $email, string $purpose = 'verify', ?string $userName = null): array
    {
        $email = (string) EmailAddress::normalize($email);
        $cooldownKey = $this->key($email, $purpose, 'cooldown');

        if (Cache::has($cooldownKey)) {
            $retryAfter = max(0, (int) Cache::get($cooldownKey) - time());

            $this->log()->notice('OTP göndərilmədi: gözləmə müddəti bitməyib.', $this->context($email, $purpose, [
                'retry_after' => $retryAfter,
            ]));

            return ['sent' => false, 'retry_after' => $retryAfter];
        }

        $code = $this->generate();
        $ttl = $this->ttl();
        $cooldown = $this->cooldown();

        Cache::put($this->key($email, $purpose, 'code'), $code, $ttl);
        Cache::put($this->key($email, $purpose, 'attempts'), 0, $ttl);
        Cache::put($cooldownKey, time() + $cooldown, $cooldown);

        $this->deliver($email, $code, $purpose, $userName);

        return ['sent' => true, 'retry_after' => $cooldown];
    }

    /**
     * Kodu yoxlayır. Uğurlu yoxlamadan sonra kod silinir və ünvan qısa
     * müddət «təsdiqli» sayılır (bax `isVerified()`).
     */
    public function verify(string $email, string $code, string $purpose = 'verify'): bool
    {
        $email = (string) EmailAddress::normalize($email);
        $stored = Cache::get($this->key($email, $purpose, 'code'));

        if ($stored === null) {
            $this->log()->notice('OTP yoxlanmadı: kodun vaxtı bitib və ya göndərilməyib.', $this->context($email, $purpose));

            return false;
        }

        $attempts = (int) Cache::get($this->key($email, $purpose, 'attempts'), 0);
        $max = $this->maxAttempts();

        if ($attempts >= $max) {
            // Brute-force əlaməti - təhlükəsizlik hadisəsi kimi yazılır
            $this->log()->warning('OTP cəhd limiti aşıldı, kod ləğv edildi.', $this->context($email, $purpose, [
                'attempts' => $attempts,
            ]));

            $this->forget($email, $purpose);

            return false;
        }

        if (!hash_equals((string) $stored, trim($code))) {
            Cache::put($this->key($email, $purpose, 'attempts'), $attempts + 1, $this->ttl());

            $this->log()->notice('OTP səhv daxil edildi.', $this->context($email, $purpose, [
                'attempt' => ($attempts + 1) . '/' . $max,
            ]));

            return false;
        }

        $this->forget($email, $purpose);
        Cache::put($this->key($email, $purpose, 'verified'), true, $this->ttl());

        $this->log()->info('OTP təsdiqləndi.', $this->context($email, $purpose));

        return true;
    }

    /**
     * Ünvan bu məqsəd üçün yaxın vaxtda təsdiqlənibmi.
     *
     * Niyə: iki addımlı axında (kod → yeni şifrə) ikinci addım kodu təkrar
     * soruşmasın, amma təsdiqsiz də keçilməsin.
     */
    public function isVerified(string $email, string $purpose = 'verify'): bool
    {
        return (bool) Cache::get($this->key((string) EmailAddress::normalize($email), $purpose, 'verified'), false);
    }

    /** Axın bitəndə (şifrə dəyişdi) «təsdiqli» işarəsini silir - təkrar istifadə olunmasın. */
    public function clearVerified(string $email, string $purpose = 'verify'): void
    {
        Cache::forget($this->key((string) EmailAddress::normalize($email), $purpose, 'verified'));
    }

    /**
     * Kodu brendli məktubla göndərir. Mail xətası axını dağıtmır - kod
     * keşdədir, istifadəçi gözləmədən sonra təkrar istəyə bilər.
     */
    private function deliver(string $email, string $code, string $purpose, ?string $userName): void
    {
        try {
            (new MailService())->sendOtpEmail(
                $email,
                $code,
                $userName,
                (int) ceil($this->ttl() / 60),
                $this->subject($purpose),
                $this->intro($purpose),
            );

            $this->log()->info('OTP göndərildi.', $this->context($email, $purpose, ['ttl' => $this->ttl()]));
        } catch (Throwable $e) {
            $this->log()->error('OTP e-poçtu göndərilmədi: ' . $e->getMessage(), $this->context($email, $purpose));
        }
    }

    private function subject(string $purpose): string
    {
        return match ($purpose) {
            'password', 'reset' => 'Şifrə sıfırlama kodu',
            'login'             => 'Giriş kodu',
            default             => 'Təsdiq kodu',
        };
    }

    private function intro(string $purpose): string
    {
        return match ($purpose) {
            'password', 'reset' => 'Şifrənizi sıfırlamaq üçün təsdiq kodunuz aşağıdadır.',
            'login'             => 'Hesabınıza giriş üçün kodunuz aşağıdadır.',
            default             => 'Təsdiq kodunuz aşağıdadır.',
        };
    }

    /** Kriptoqrafik təsadüfi, başında sıfır ola bilən N rəqəmli kod. */
    private function generate(): string
    {
        $length = min(8, max(4, (int) config('custom.security.otp.length', 6)));

        return str_pad((string) random_int(0, (10 ** $length) - 1), $length, '0', STR_PAD_LEFT);
    }

    private function ttl(): int
    {
        return max(60, (int) config('custom.security.otp.ttl', 300));
    }

    private function cooldown(): int
    {
        return max(0, (int) config('custom.security.otp.resend_cooldown', 60));
    }

    private function maxAttempts(): int
    {
        return max(1, (int) config('custom.security.otp.max_attempts', 5));
    }

    private function forget(string $email, string $purpose): void
    {
        Cache::forget($this->key($email, $purpose, 'code'));
        Cache::forget($this->key($email, $purpose, 'attempts'));
    }

    private function key(string $email, string $purpose, string $part): string
    {
        // Ünvan hash-lənir: keş açarında (redis MONITOR, fayl adı) açıq e-poçt qalmasın
        return 'otp:' . $purpose . ':' . sha1($email) . ':' . $part;
    }

    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    private function context(string $email, string $purpose, array $extra = []): array
    {
        return array_merge([
            'email'   => EmailAddress::mask($email),
            'purpose' => $purpose,
            'ip'      => request()->ip(),
        ], $extra);
    }

    private function log(): LogService
    {
        return new LogService((string) config('custom.security.otp.log_channel', 'gopanel-auth'));
    }
}
