<?php

namespace App\Services\Site\Seo;

use App\Models\Geography\Language;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Throwable;

/**
 * `<link rel="canonical">` və bütün `<link rel="alternate" hreflang="..">`
 * linklərini qurur.
 *
 * - Hər aktiv dil üçün ünvan `LanguageSwitcherService`-dən gəlir: slug da
 *   tərcümə olunur (`/az/bloqlar` ↔ `/en/blogs`). Əvvəl yalnız dil seqmenti
 *   dəyişirdi və `hreflang` 404 verən ünvana işarə edirdi.
 * - `x-default` əlavə olunur - dil seçilə bilmədikdə göstərilən nüsxə. Google
 *   onu ayrıca istəyir, yoxdursa dil qrupunu natamam sayır. Default dil
 *   `languages.default` sütunundan, yoxdursa `app.locale`-dən götürülür.
 */
class AlternatesService
{
    /**
     * @return array{canonical: string, alternates: array<string, string>}
     */
    public function compose(Request $request)
    {
        $alternates = [];

        try {
            if (Schema::hasTable('languages')) {
                $switcher = app(LanguageSwitcherService::class);

                foreach (Language::getCachedAll() as $language) {
                    $alternates[$language->code] = $switcher->urlFor($language);
                }

                $default = Language::getCachedAll()->firstWhere('default', true)?->code
                    ?? (string) config('app.locale', 'az');

                if (isset($alternates[$default])) {
                    $alternates['x-default'] = $alternates[$default];
                }
            }
        } catch (Throwable $e) {
            report($e);
        }

        return [
            'canonical'  => URL::current(),
            'alternates' => $alternates,
        ];
    }
}
