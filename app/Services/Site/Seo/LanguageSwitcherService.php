<?php

declare(strict_types=1);

namespace App\Services\Site\Seo;

use App\Models\Geography\Language;
use App\Models\Translations\FieldTranslation;
use App\Services\Cache\CacheService;
use App\Support\Cache\CacheKey;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Cari URL-i başqa dilə çevirir - slug-lar da daxil olmaqla.
 *
 * URL qəlibi: `/{dil}/{slug}`. Slug `field_translations`-dadır və dilə görə
 * dəyişir (`/az/bloqlar` ↔ `/en/blogs`), ona görə yalnız dil seqmentini
 * dəyişmək kifayət deyil: `Language::switchLanguage()` `/en/bloqlar` verirdi -
 * belə ünvan 404-dür və `hreflang` linki axtarış sisteminə səhv cütlük göstərirdi.
 *
 * Servis slug-ın SAHİBİNİ (model_type + model_id) tapır və onun hədəf dildəki
 * slug-ını qoyur. Tərcümə yoxdursa seqment olduğu kimi qalır (köhnə davranış).
 * `Language::switchLanguage()` dəyişdirilmir - geriyə uyğunluq üçün qalır.
 */
class LanguageSwitcherService
{
    public function urlFor(Language $language): string
    {
        return $this->urlForLocale((string) $language->code);
    }

    /**
     * @param  string|null  $url  Cari sorğudan başqa ünvan üçün (nadir hal)
     */
    public function urlForLocale(string $code, ?string $url = null): string
    {
        $segments    = $this->segmentsOf($url);
        $activeCodes = $this->activeCodes();
        $current     = app()->getLocale();

        if ($segments !== [] && in_array($segments[0], $activeCodes, true)) {
            $current     = $segments[0];
            $segments[0] = $code;
        } else {
            array_unshift($segments, $code);
        }

        $this->replaceTranslatedSlugs($segments, $current, $code);

        $target = rtrim(url('/'), '/') . '/' . implode('/', $segments);
        $query  = is_null($url) ? request()->getQueryString() : parse_url($url, PHP_URL_QUERY);

        return $query ? "{$target}?{$query}" : $target;
    }

    /** @return array<int, string> */
    private function activeCodes(): array
    {
        try {
            return Language::getCachedAll()->pluck('code')->all();
        } catch (Throwable) {
            return [];
        }
    }

    /** @return array<int, string> */
    private function segmentsOf(?string $url): array
    {
        if (is_null($url)) {
            return request()->segments();
        }

        $path = (string) parse_url($url, PHP_URL_PATH);

        return array_values(array_filter(explode('/', trim($path, '/')), fn ($s) => $s !== ''));
    }

    /**
     * Dil seqmentindən sonrakı bütün slug seqmentlərini hədəf dilə çevirir.
     *
     * @param  array<int, string>  $segments
     */
    private function replaceTranslatedSlugs(array &$segments, string $current, string $target): void
    {
        if ($target === $current || count($segments) < 2) {
            return;
        }

        try {
            if (!Schema::hasTable('field_translations')) {
                return;
            }

            for ($i = 1, $n = count($segments); $i < $n; $i++) {
                $translated = $this->translateSlug($segments[$i], $current, $target);

                if ($translated) {
                    $segments[$i] = $translated;
                }
            }
        } catch (Throwable $e) {
            report($e);
        }
    }

    private function translateSlug(string $slug, string $current, string $target): ?string
    {
        // `seo` qrupu: FieldTranslation dəyişəndə sahibin qrupu təmizlənir,
        // `custom()` isə dildən asılı olmayan açar verir - dil açarda özü var
        $value = CacheService::remember(
            CacheKey::custom(CacheKey::GROUP_SEO, 'lang-switch', ['s' => $slug, 'from' => $current, 'to' => $target, 'fp' => $this->versions()], false),
            function () use ($slug, $current, $target): string {
                $owner = FieldTranslation::query()
                    ->select(['model_type', 'model_id'])
                    ->where('locale', $current)
                    ->where('key', 'slug')
                    ->where('value', $slug)
                    ->first();

                if (!$owner) {
                    return '';
                }

                return (string) FieldTranslation::query()
                    ->where('locale', $target)
                    ->where('key', 'slug')
                    ->where('model_type', $owner->model_type)
                    ->where('model_id', $owner->model_id)
                    ->value('value');
            },
        );

        return $value !== '' ? $value : null;
    }

    /** Slug sahibi istənilən qrupa aid ola bilər - bütün versiyalar açara qatılır. */
    private function versions(): string
    {
        return app(\App\Services\Seo\PublicContentService::class)->fingerprint();
    }
}
