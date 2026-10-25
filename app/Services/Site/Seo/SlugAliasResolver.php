<?php

declare(strict_types=1);

namespace App\Services\Site\Seo;

use App\Models\Navigation\Menu;
use App\Models\Translations\FieldTranslation;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * «Yanlış dildəki slug» ünvanını düzgün ünvana çevirir.
 *
 * Nümunə: `/en/bloqlar` - prefiks ingiliscədir, slug isə azərbaycanca. Belə
 * ünvan `/{dil}/{slug}` catch-all-a düşür və 404 verirdi (köhnə link, əl ilə
 * dəyişdirilmiş dil prefiksi, səhv `hreflang`). Resolver `bloqlar` slug-ının
 * **sahibini** (`field_translations` → hansı qeyd) tapır, həmin qeydin
 * ingiliscə slug-ını götürür və `/en/blogs` qaytarır - controller 301 verir.
 *
 * Haradan çağırılır: `DynamicContentController` (slug cari dildə tapılmayanda)
 * və `MarkdownController` (`.md` variantı üçün eyni qayda). Yəni normal səhifə
 * açılışına heç bir yük düşmür - yalnız 404 olacaq halda işləyir.
 *
 * `site_redirects` cədvəlindəki açıq qaydalar bundan ƏVVƏL işləyir
 * (`SiteRedirectMiddleware`) - bu, onların tutmadığı halları yığan ehtiyatdır.
 */
class SlugAliasResolver
{
    /**
     * Dil prefiksindən sonrakı seqmentləri həmin dilə çevirir.
     *
     * @param  array<int, string>  $segments  dil kodundan SONRAKI seqmentlər
     * @return string|null  düzgün yol (`/en/blogs`) və ya dəyişiklik yoxdursa `null`
     */
    public function correctPath(string $locale, array $segments): ?string
    {
        $segments = array_values(array_filter($segments, fn ($s) => is_string($s) && trim($s) !== ''));

        if ($segments === []) {
            return null;
        }

        try {
            if (!Schema::hasTable('field_translations')) {
                return null;
            }

            $changed = false;

            foreach ($segments as $index => $segment) {
                $correct = $this->translateSegment($segment, $locale);

                if ($correct !== null && $correct !== $segment) {
                    $segments[$index] = $correct;
                    $changed          = true;
                }
            }
        } catch (Throwable $e) {
            report($e);

            return null;
        }

        return $changed ? '/' . $locale . '/' . implode('/', $segments) : null;
    }

    /**
     * Slug-ın sahibini tapır və həmin sahibin `$locale` dilindəki slug-ını verir.
     *
     * Eyni slug bir neçə model tipində ola bilər (slug unikallığı dil + tip
     * üzrədir) - menyu üstündür, çünki bölmə ünvanıdır; sonra digər tiplər.
     * Keşlənmir: yalnız 404 olacaq sorğuda işləyir, nadir haldır.
     */
    private function translateSegment(string $segment, string $locale): ?string
    {
        $owners = FieldTranslation::query()
            ->select(['model_type', 'model_id', 'locale'])
            ->where('key', 'slug')
            ->where('value', $segment)
            ->where('locale', '!=', $locale)
            ->limit(10)
            ->get()
            ->sortByDesc(fn ($row) => $row->model_type === Menu::class ? 1 : 0);

        foreach ($owners as $owner) {
            $value = FieldTranslation::query()
                ->where('key', 'slug')
                ->where('locale', $locale)
                ->where('model_type', $owner->model_type)
                ->where('model_id', $owner->model_id)
                ->value('value');

            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        return null;
    }
}
