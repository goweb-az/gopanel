<?php

declare(strict_types=1);

namespace App\Services\Gopanel\Settings;

use App\DTOs\Gopanel\ContentPayload;
use App\Models\Settings\SiteSetting;
use App\Services\Gopanel\Content\ContentSaveService;
use Illuminate\Support\Facades\Cache;

/**
 * Sayt tənzimləmələrinin saxlanması.
 *
 * NİYƏ ayrıca servis:
 * Yazmaqdan sonra KEŞ TƏMİZLƏNMƏLİDİR - `SiteSetting::getCached()` dəyəri
 * `rememberForever` ilə saxlayır. Bu addım unudulanda admin loqonu dəyişir,
 * amma saytda köhnəsi qalır və səbəbi tapmaq üçün heç bir xəta görünmür.
 * Ona görə saxlama ilə keş invalidasiyası tək metodda birləşdirilir.
 *
 * `Cache::flush()` ÇAĞIRILMIR - o, bütün sistemin keşini uçurur
 * (bax: .claude/rules/03-site.md § 5); yalnız bu modulun açarı silinir.
 */
class SiteSettingsService
{
    public function __construct(private readonly ContentSaveService $content)
    {
    }

    /**
     * @param  list<\App\DTOs\Gopanel\FileField>  $fileFields
     */
    public function save(SiteSetting $item, ContentPayload $payload, array $fileFields = []): SiteSetting
    {
        /** @var SiteSetting $item */
        $item = $this->content->save($item, $payload, $fileFields);

        $this->forgetCache();

        return $item;
    }

    /** Açar dilə görə qurulur - `SiteSetting::getCached($locale)` ilə eyni. */
    private function forgetCache(): void
    {
        Cache::forget('site_settings' . app()->getLocale());
    }
}
