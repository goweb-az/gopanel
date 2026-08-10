<?php

namespace App\Services\Sms;

use App\Services\Activity\LogService;
use Throwable;

/**
 * SMS göndərişinin TƏK giriş nöqtəsi.
 *
 * Provayder `ProviderInterface` arxasındadır - provayder dəyişəndə yalnız
 * `config/custom/sms.php` → `provider` sətri dəyişir, çağıran kod toxunulmur.
 *
 * MODELƏ BAĞLI DEYİL. Layihədə `sms_logs` cədvəli varsa, `setLogger()` ilə
 * öz yazıcını ötür - onda hər göndəriş bazaya da düşür. Ötürülməsə yalnız
 * `logs/sms/` fayl jurnalına yazılır.
 *
 * ```php
 * app(SmsService::class)->send('+994551112233', 'Kod: 1234', 'otp');
 *
 * // öz provayderin ilə:
 * (new SmsService(new Lsim()))->send($phone, $message);
 * ```
 */
class SmsService
{
    public const TYPE_OTP          = 'otp';
    public const TYPE_NOTIFICATION = 'notification';
    public const TYPE_CAMPAIGN     = 'campaign';
    public const TYPE_OTHER        = 'other';

    public const STATUS_SENT    = 'sent';
    public const STATUS_FAILED  = 'failed';
    public const STATUS_BLOCKED = 'blocked';

    protected ?ProviderInterface $provider;

    protected LogService $logging;

    /**
     * Bazaya yazan könüllü jurnal. `fn (array $row) => SmsLog::create($row)`.
     *
     * @var callable|null
     */
    protected $logger = null;

    /** Sonuncu göndərişin jurnal sətri - test/debug üçün. */
    public ?array $lastLog = null;

    public function __construct(?ProviderInterface $provider = null)
    {
        $this->provider = $provider ?: self::defaultProvider();
        $this->logging  = new LogService('sms');
    }

    /** Config-dəki default provayderi qurur (yoxdursa null). */
    public static function defaultProvider(): ?ProviderInterface
    {
        $class = config('custom.sms.provider');

        if (!is_string($class) || !class_exists($class)) {
            return null;
        }

        $provider = app($class);

        return $provider instanceof ProviderInterface ? $provider : null;
    }

    public function setProvider(ProviderInterface $provider): self
    {
        $this->provider = $provider;

        return $this;
    }

    /**
     * Bazaya yazan jurnalı təyin edir.
     *
     * @param  callable(array<string, mixed>): mixed  $logger
     */
    public function setLogger(callable $logger): self
    {
        $this->logger = $logger;

        return $this;
    }

    /**
     * SMS göndərir.
     *
     * Mətn provayderə getməzdən əvvəl Azərbaycan hərflərindən təmizlənir -
     * əks halda operatorlar mesajı Unicode sayır və 1 SMS 70 simvola düşür.
     *
     * @throws Throwable  Provayder xətası yuxarı ötürülür (çağıran qərar verir).
     */
    public function send(string $phone, string $message, string $type = self::TYPE_OTHER): self
    {
        if (!$this->provider) {
            $this->log($phone, $message, $type, self::STATUS_FAILED, 'SMS provayderi təyin edilməyib.');

            throw new \RuntimeException('SMS provayderi təyin edilməyib (config/custom/sms.php → provider).');
        }

        if (!config('custom.sms.enabled', true)) {
            return $this->logBlocked($phone, $message, $type, 'SMS göndərişi söndürülüb.');
        }

        try {
            $this->provider->send($phone, $this->convert($message));
            $this->log($phone, $message, $type, self::STATUS_SENT);
        } catch (Throwable $th) {
            $this->log($phone, $message, $type, self::STATUS_FAILED, $th->getMessage());

            throw $th;
        }

        return $this;
    }

    /**
     * Göndərilməmiş SMS-i jurnala yazır (məs. aylıq limit dolub).
     *
     * Provayderə heç nə getmir - qeyd yalnız hesabat üçündür: «nə qədər SMS
     * getdi, nə qədəri getmədi» sualının cavabı tam olsun.
     */
    public function logBlocked(string $phone, string $message, string $type, string $reason): self
    {
        return $this->log($phone, $message, $type, self::STATUS_BLOCKED, $reason);
    }

    /**
     * Jurnala yazır. Jurnal xətası göndərişi POZMAMALIDIR.
     */
    protected function log(string $phone, string $message, string $type, string $status, ?string $error = null): self
    {
        $row = [
            'phone'    => $phone,
            'message'  => $message,
            'type'     => $type,
            'provider' => $this->provider ? class_basename($this->provider) : null,
            'status'   => $status,
            'error'    => $error,
        ];

        $this->lastLog = $row;

        $status === self::STATUS_SENT
            ? $this->logging->info("SMS {$status}: {$phone}", $row)
            : $this->logging->error("SMS {$status}: {$phone}", $row);

        if ($this->logger) {
            try {
                ($this->logger)($row);
            } catch (Throwable $th) {
                report($th);
            }
        }

        return $this;
    }

    /**
     * Azərbaycan hərflərini latın qarşılığına çevirir (ü → u, ç → c ...).
     * `app/Helpers/helpers.php` → `convertAzerbaijaniToEnglish()`.
     */
    protected function convert(string $text): string
    {
        return function_exists('convertAzerbaijaniToEnglish')
            ? convertAzerbaijaniToEnglish($text)
            : $text;
    }
}
