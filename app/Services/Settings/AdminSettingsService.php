<?php

declare(strict_types=1);

namespace App\Services\Settings;

use App\Models\Gopanel\Admin;
use App\Support\Settings\AdminSettingDefinitions;

/**
 * Adminin şəxsi tənzimləmələri - `SettingsService` üzərində qısa yol.
 *
 * Niyə ayrıca sinif: çağıran yerlər (bildiriş job-u, profil ekranı) adətən
 * yalnız admin id-sini bilir; hər dəfə tərif sinfini və morph tipini yazmaq
 * əvəzinə bu fasad işlədilir. İmzalar qrgate layihəsindəki eyni adlı sinifə
 * uyğundur - kod köçürüləndə dəyişməsin.
 */
class AdminSettingsService
{
    /** @return array<string, mixed> defaultlar birləşdirilmiş, cast olunmuş */
    public function all(Admin|int $admin): array
    {
        return $this->store($admin)->all();
    }

    /** Bir açarın dəyəri (sətir yoxdursa default). */
    public function get(Admin|int $admin, string $key): mixed
    {
        return $this->store($admin)->get($key);
    }

    /**
     * @param  array<string, mixed>  $values  key => native dəyər
     * @return array<string, mixed>  yazılandan sonrakı tam siyahı
     */
    public function saveMany(Admin|int $admin, array $values): array
    {
        return $this->store($admin)->saveMany($values);
    }

    /** Çatışmayan açarları default dəyərlərlə yaradır - idempotentdir. */
    public function seedDefaults(Admin|int $admin): int
    {
        return $this->store($admin)->seedDefaults();
    }

    /** Request-daxili yaddaşı sıfırlayır (test və command mühiti üçün). */
    public static function flush(): void
    {
        SettingsService::flushMemo();
    }

    private function store(Admin|int $admin): SettingsService
    {
        return $admin instanceof Admin
            ? SettingsService::for(AdminSettingDefinitions::class, $admin)
            : SettingsService::forOwner(AdminSettingDefinitions::class, (new Admin())->getMorphClass(), $admin);
    }
}
