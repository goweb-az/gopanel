<?php

namespace App\Traits\Content;

use App\Helpers\Gopanel\FileUploader;
use App\Support\Url\CdnUrl;

trait HasFiles
{
    public function getAttribute($key)
    {
        if (property_exists($this, 'files') && is_array($this->files)) {
            if (substr($key, -4) === '_url') {
                $fileAttribute = substr($key, 0, -4);
                if (in_array($fileAttribute, $this->files)) {
                    return $this->getFileUrl($fileAttribute);
                }
            }

            if (property_exists($this, 'fillable') && is_array($this->fillable)) {
                if (substr($key, -5) === '_view') {
                    $field = substr($key, 0, -5);
                    if (in_array($field, $this->fillable)) {
                        return $this->getFieldView($field);
                    }
                }
            }
        }

        return parent::getAttribute($key);
    }

    /**
     * `<sahə>_url` accessor-unun dəyəri - sütun doludursa HƏMİŞƏ string.
     *
     * Ünvan `CdnUrl` ilə qurulur: `public/` altında tapılan fayl `CdnUrl::asset()`,
     * qalanı `CdnUrl::url()` (tam URL toxunulmur, nisbi yol storage faylı sayılır).
     * Əvvəl fayl tapılmayanda XAM nisbi yol (`admins/a.png`) qaytarılırdı -
     * brauzer onu cari səhifəyə nisbətən açır və şəkil sınırdı; `toStorage()`
     * ilə yüklənən fayllar isə ümumiyyətlə işləmirdi.
     *
     * `null` YALNIZ sütun boş (null / '') olanda qayıdır - blade-lərdəki
     * `$item->image_url ?? '...'` yoxlamaları əvvəlki kimi işləyir. Faylın
     * diskdə həqiqətən olub-olmadığı lazımdırsa `getExistingFileUrl()`.
     */
    public function getFileUrl(string $file): ?string
    {
        $value = $this->{$file};

        // Boş sətir də null: əvvəl `public_path('')` qovluğu «tapılırdı» və
        // sütun boş olanda saytın kök ünvanı şəkil kimi qaytarılırdı.
        if (is_null($value) || $value === '') {
            return null;
        }

        if (!CdnUrl::isAbsolute($value) && file_exists(public_path($value))) {
            return CdnUrl::asset($value);
        }

        return CdnUrl::url($value);
    }

    /**
     * Faylın URL-i yalnız fayl diskdə (public və ya storage) VARSA, əks halda null.
     *
     * Niyə ayrıca metod: `getFileUrl()`-in «həmişə string» müqaviləsinə bağlı
     * kod var (`?? 'javascript:void()'`), onu dəyişmək olmaz. Panel xanası isə
     * itmiş fayl üçün sınıq `<img>` əvəzinə placeholder göstərməlidir.
     * Tam (kənar) URL diskdə axtarılmır - olduğu kimi qaytarılır.
     */
    public function getExistingFileUrl(string $file): ?string
    {
        $value = $this->{$file};

        if (empty($value)) {
            return null;
        }

        return FileUploader::url($value, 'public') ?? FileUploader::url($value, 'storage');
    }

    public function getFieldView(string $field): ?string
    {
        if (is_null($this->{$field})) {
            return null;
        }

        $ext = strtolower(pathinfo($this->{$field}, PATHINFO_EXTENSION));
        $path = $this->getExistingFileUrl($field);

        // Baza sətri var, fayl isə diskdə yoxdur - `<img src="">` sınıq şəkil
        // verir, ona görə panel xanası placeholder göstərir.
        if ($path === null) {
            return '<span class="badge bg-light text-muted">Fayl yoxdur</span>';
        }

        if (in_array($ext, ['png', 'jpg', 'jpeg', 'gif', 'webp', 'svg'])) {
            return '<a href="' . $path . '" class="image-lightbox" title="' . class_basename($this) . '"><img src="' . $path . '" width="50"></a>';
        }

        return '<a href="' . $path . '" target="_blank">Fayla bax</a>';
    }
}
