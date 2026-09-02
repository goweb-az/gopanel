<?php

declare(strict_types=1);

namespace App\Rules;

use App\Models\Geography\Language;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Çoxdilli sahə üçün: ƏN AZI standart dildə dəyər olmalıdır.
 *
 * NİYƏ adi `required` bəs etmir:
 * Panel formaları çoxdilli sahəni `title[az]`, `title[en]` şəklində göndərir.
 * `required` massivin özünü yoxlayır - bütün dillər boş olsa belə massiv
 * mövcud olduğu üçün validasiyadan keçir və bazaya adsız sətir düşür.
 * `required_array_keys` isə bütün dilləri məcbur edir; halbuki əlavə dillərin
 * sonradan doldurulması normaldır.
 *
 * Standart dil `languages` cədvəlindən oxunur (`default = 1`), sabit deyil -
 * layihə hansı dildə işləyirsə, məcburi olan da odur.
 */
class TranslatedRequired implements ValidationRule
{
    public function __construct(private readonly ?string $locale = null)
    {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $locale = $this->locale ?? Language::getDefaultCode();

        $translation = is_array($value) ? ($value[$locale] ?? null) : $value;

        if (is_string($translation)) {
            $translation = trim($translation);
        }

        if ($translation === null || $translation === '' || $translation === []) {
            $fail('Bu sahə ən azı «' . mb_strtoupper($locale) . '» dilində doldurulmalıdır.');
        }
    }
}
