<?php

declare(strict_types=1);

namespace App\Services\Site;

use App\Models\Settings\SiteSetting;

/**
 * `site_settings` status açarlarının (site/login/register/payment) TƏK oxunma nöqtəsi.
 *
 * Niyə: `if ($settings->login_status)` controller/blade/middleware-də ayrı-ayrı
 * yazılanda biri `null`-u «bağlı», digəri «açıq» sayır. Burada qayda birdir:
 * sətir yoxdursa və ya sütun `null`-dursa → AÇIQ. Tənzimləmə sətrinin
 * olmaması (təzə quraşdırma) saytı bağlamamalıdır.
 *
 * Oxu `SiteSetting::getCached()` ilə gedir - panel yadda saxlayanda həmin
 * keşi `SiteSettingsService` artıq təmizləyir, əlavə invalidasiya lazım deyil.
 *
 * Mənbə: texnotech `SiteStatusService` - sifariş/kredit açarları və onların
 * domen istisnaları (OrderDisabled, PaymentDisabled...) çıxarılıb: onlar
 * layihəyə xas iş məntiqidir, törəmə layihə `ensure*()` metodlarını özü yazır.
 */
class SiteStatusService
{
    /** @var array<string, bool>|null Sorğu ərzində bir dəfə oxunur. */
    private ?array $flags = null;

    public function isSiteEnabled(): bool
    {
        return $this->flag('site_status');
    }

    public function isLoginEnabled(): bool
    {
        return $this->flag('login_status');
    }

    public function isRegisterEnabled(): bool
    {
        return $this->flag('register_status');
    }

    public function isPaymentEnabled(): bool
    {
        return $this->flag('payment_status');
    }

    /**
     * Frontend / API üçün bütün açarlar bir yerdə.
     *
     * @return array<string, bool>
     */
    public function toArray(): array
    {
        return [
            'site_enabled'     => $this->isSiteEnabled(),
            'login_enabled'    => $this->isLoginEnabled(),
            'register_enabled' => $this->isRegisterEnabled(),
            'payment_enabled'  => $this->isPaymentEnabled(),
        ];
    }

    /** Testlərdə və uzunömürlü prosesdə (queue worker) sətri yenidən oxumaq üçün. */
    public function forget(): void
    {
        $this->flags = null;
    }

    private function flag(string $column): bool
    {
        return $this->flags()[$column] ?? true;
    }

    /**
     * @return array<string, bool>
     */
    private function flags(): array
    {
        if ($this->flags !== null) {
            return $this->flags;
        }

        try {
            $row = SiteSetting::getCached()?->only(['site_status', 'login_status', 'register_status', 'payment_status']) ?? [];
        } catch (\Throwable $e) {
            // Cədvəl hələ yoxdur (miqrasiyadan əvvəl) - hər şey açıq sayılır
            report($e);
            $row = [];
        }

        $flags = [];

        foreach ($row as $column => $value) {
            $flags[$column] = $value === null ? true : (bool) $value;
        }

        return $this->flags = $flags;
    }
}
