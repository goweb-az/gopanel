<?php

namespace App\Providers;

use App\Models\Translations\FieldTranslation;
use App\Support\Cache\CacheKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;

/**
 * `CacheKey` qruplarının avtomatik təmizlənməsi.
 *
 * Niyə ayrıca provider: keş açarları versiyalıdır (`menu.v3.web.header.az`),
 * amma versiyanı kimsə artırmasa köhnə məlumat TTL bitənə qədər qalır. Hər
 * servisdə əl ilə `flushGroup()` yazmaq unudulur - burada model event-lərinə
 * BİR DƏFƏ bağlanır və `config/custom/cache.php` → `model_groups` xəritəsi
 * tək mənbə olur.
 *
 * İki mənbədən təmizlənir:
 *   1. «Sahib» modelin öz event-i   → struktur dəyişikliyi (aktivlik, sıra, silinmə);
 *   2. FieldTranslation event-i     → yalnız tərcümə mətni dəyişəndə. Bu hal
 *      vacibdir: menyu adını redaktə edəndə `menus` sətri `saved` OLMUR, yalnız
 *      `field_translations` yazılır - bu hissə olmasa keş köhnə adı göstərir.
 */
class CacheInvalidationServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->registerOwnerModelEvents();
        $this->registerFieldTranslationEvents();
    }

    protected function registerOwnerModelEvents(): void
    {
        $flush = static fn (Model $model) => CacheKey::flushForModelType($model::class);

        foreach (CacheKey::trackedModels() as $modelClass) {
            $modelClass::saved($flush);
            $modelClass::deleted($flush);

            // restored() yalnız SoftDeletes işlədən modellərdə mövcuddur.
            if (method_exists($modelClass, 'restored')) {
                $modelClass::restored($flush);
            }
        }
    }

    protected function registerFieldTranslationEvents(): void
    {
        if (!class_exists(FieldTranslation::class)) {
            return;
        }

        $flush = static fn (FieldTranslation $translation) => CacheKey::flushForModelType($translation->model_type);

        FieldTranslation::saved($flush);
        FieldTranslation::deleted($flush);
    }
}
