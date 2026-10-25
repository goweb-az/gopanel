<?php

declare(strict_types=1);

namespace App\Services\Settings;

use App\Models\Settings\SettingValue;
use App\Repositories\BaseRepository;
use App\Services\Cache\CacheService;
use App\Support\Cache\CacheKey;
use App\Support\Settings\SettingDefinitions;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * Tipli key-value tənzimləmələrin oxu/yazı qatı - istənilən təriflər dəsti üçün.
 *
 *   $settings = SettingsService::for(AdminSettingDefinitions::class, $admin);
 *   $settings->get('notify_new_order');          // true (sətir yoxdursa default)
 *   $settings->saveMany(['notify_new_order' => false]);
 *
 *   SettingsService::for(ShopSettingDefinitions::class)->get('min_order');   // qlobal (owner-siz)
 *
 * Niyə tək servis: tərif dəstləri (admin, istifadəçi, qlobal) yalnız config
 * açarı ilə fərqlənir; oxu/yazı/keş məntiqi hər dəst üçün ayrıca yazılsaydı
 * nüsxələr bir-birindən ayrılardı (biri keşi təmizləyər, digəri yox).
 *
 * Keş: (scope, owner) üzrə bütün xam dəyərlər bir açarda saxlanılır
 * (`CacheKey::settings`), yazandan sonra `settings` qrupu versiyası artırılır.
 * Request daxilində əlavə yaddaş keşi var - bildiriş job-u eyni admin üçün
 * onlarla dəfə oxuyur.
 *
 * `site_settings` (SiteSetting) BURAYA KÖÇÜRÜLMƏYİB - o, sütunlu cədvəldir,
 * fayl/meta saxlayır və öz servisi var. Bu qat yeni tənzimləmələr üçündür.
 */
final class SettingsService
{
    /** @var array<string, array<string, string|null>> memo açarı => [key => xam dəyər] */
    private static array $memo = [];

    /**
     * @param  class-string<SettingDefinitions>  $definitions
     */
    private function __construct(
        private readonly string $definitions,
        private readonly string $ownerType,
        private readonly int $ownerId,
    ) {
        if (! is_subclass_of($definitions, SettingDefinitions::class)) {
            throw new InvalidArgumentException("{$definitions} SettingDefinitions-dən törəməlidir.");
        }
    }

    /**
     * @param  class-string<SettingDefinitions>  $definitions
     * @param  Model|null  $owner  null = qlobal tənzimləmə
     */
    public static function for(string $definitions, ?Model $owner = null): self
    {
        return $owner === null
            ? new self($definitions, '', 0)
            : new self($definitions, $owner->getMorphClass(), (int) $owner->getKey());
    }

    /**
     * Model obyekti əldə olmayanda (job yalnız id daşıyır) - əlavə SELECT olmasın.
     *
     * @param  class-string<SettingDefinitions>  $definitions
     */
    public static function forOwner(string $definitions, string $ownerType, int $ownerId): self
    {
        return new self($definitions, $ownerType, $ownerId);
    }

    /**
     * Bütün tənzimləmələr - defaultlar birləşdirilmiş, cast olunmuş.
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        $raw = $this->rawValues();
        $values = [];

        foreach ($this->definitions::keys() as $key) {
            $values[$key] = $this->definitions::cast($key, $raw[$key] ?? null);
        }

        return $values;
    }

    /** Bir açarın dəyəri (sətir yoxdursa default; tərif yoxdursa null). */
    public function get(string $key): mixed
    {
        if (! $this->definitions::has($key)) {
            return null;
        }

        return $this->definitions::cast($key, $this->rawValues()[$key] ?? null);
    }

    public function set(string $key, mixed $value): void
    {
        $this->saveMany([$key => $value]);
    }

    /**
     * Bir neçə açarı birdən yazır - yalnız tərif siyahısındakı açarlar.
     *
     * @param  array<string, mixed>  $values  key => native dəyər
     * @return array<string, mixed>  yazılandan sonrakı tam siyahı
     */
    public function saveMany(array $values): array
    {
        $repository = app(BaseRepository::class);
        $written = false;

        foreach ($values as $key => $value) {
            if (! $this->definitions::has((string) $key)) {
                continue;
            }

            $row = SettingValue::query()->firstOrNew([
                'scope'      => $this->definitions::scope(),
                'owner_type' => $this->ownerType,
                'owner_id'   => $this->ownerId,
                'key'        => (string) $key,
            ]);

            $repository->save($row, ['value' => $this->definitions::toStorage((string) $key, $value)]);
            $written = true;
        }

        if ($written) {
            $this->forgetCache();
        }

        return $this->all();
    }

    /**
     * Açarı default-a qaytarır - sətir silinir (default config-də olduğu üçün
     * `value = null` saxlamaqdan təmizdir).
     */
    public function reset(string $key): void
    {
        SettingValue::query()
            ->where('scope', $this->definitions::scope())
            ->where('owner_type', $this->ownerType)
            ->where('owner_id', $this->ownerId)
            ->where('key', $key)
            ->delete();

        $this->forgetCache();
    }

    /**
     * Çatışmayan açarları default dəyərlərlə yaradır (yeni admin).
     * Mövcud sətirlərə toxunmur - idempotentdir.
     *
     * Adətən LAZIM DEYİL (sətir yoxdursa default oxunur). Yalnız sonradan
     * config default-u dəyişəndə köhnə istifadəçilərin dəyəri sabit qalsın
     * istəyirsənsə çağırılır.
     */
    public function seedDefaults(): int
    {
        $existing = $this->rawValues();
        $missing = array_diff_key($this->definitions::defaults(), $existing);

        if ($missing !== []) {
            $this->saveMany($missing);
        }

        return count($missing);
    }

    /** Request-daxili yaddaşı sıfırlayır (test və uzun işləyən command üçün). */
    public static function flushMemo(): void
    {
        self::$memo = [];
    }

    /** @return array<string, string|null> key => xam dəyər */
    private function rawValues(): array
    {
        $memoKey = $this->memoKey();

        return self::$memo[$memoKey] ??= (array) CacheService::remember(
            CacheKey::settings($memoKey),
            fn (): array => SettingValue::query()
                ->where('scope', $this->definitions::scope())
                ->where('owner_type', $this->ownerType)
                ->where('owner_id', $this->ownerId)
                ->pluck('value', 'key')
                ->all(),
        );
    }

    /**
     * Yalnız bu (scope, owner) açarını silmək kifayət deyil: eyni dəyərdən
     * törəmiş keşlər də (məs. `CacheKey::dependsOn(..., 'settings')`) yenilənməlidir.
     * Qrup versiyası artırılır - `Cache::flush()` çağırılmır.
     */
    private function forgetCache(): void
    {
        unset(self::$memo[$this->memoKey()]);
        CacheKey::flushGroup(CacheKey::GROUP_SETTINGS);
    }

    private function memoKey(): string
    {
        return 'kv.' . $this->definitions::scope() . '.' . md5($this->ownerType) . '.' . $this->ownerId;
    }
}
