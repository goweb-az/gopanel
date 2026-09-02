<?php

declare(strict_types=1);

namespace App\DTOs\Gopanel;

use Illuminate\Http\UploadedFile;

/**
 * Panelin məzmun formalarından (bloq, kateqoriya, xidmət, məhsul...) gələn
 * məlumatın typed daşıyıcısı.
 *
 * NİYƏ lazımdır:
 * Əvvəllər servis/controller `Request`-in özünü qəbul edirdi və hansı açarın
 * hansı layerə aid olduğu yalnız `except([...])` siyahısından bilinirdi.
 * Nəticədə forma bir sahə əlavə edəndə o, səhvən sütuna yazılmağa çalışırdı.
 * Burada dörd axın açıq-aydın ayrılır:
 *
 *   attributes   → cədvəl sütunları (Repository yazır)
 *   translations → `field_translations` (TranslationHelper yazır)
 *   meta         → `page_meta_data` (PageMetaDataHelper yazır)
 *   files        → yüklənən fayllar (ContentSaveService yükləyir)
 *
 * Obyekt dəyişməzdir - dəyişiklik yeni nüsxə qaytarır.
 */
final class ContentPayload
{
    /**
     * @param  array<string, mixed>                 $attributes    sütun dəyərləri
     * @param  array<string, array<string, mixed>>  $translations  [sahə => [dil => dəyər]]
     * @param  array<string, mixed>                 $meta          SEO meta sahələri
     * @param  array<string, mixed>                 $metaFiles     SEO meta faylları
     * @param  array<string, UploadedFile>          $files         [forma açarı => fayl]
     */
    public function __construct(
        public readonly array $attributes = [],
        public readonly array $translations = [],
        public readonly array $meta = [],
        public readonly array $metaFiles = [],
        public readonly array $files = [],
    ) {
    }

    /**
     * Sütun dəyəri əlavə edilmiş yeni nüsxə.
     *
     * Fayl yükləndikdən sonra yol (`image`, `icon`) məhz belə yazılır -
     * mövcud obyekt dəyişdirilmir.
     */
    public function withAttribute(string $key, mixed $value): self
    {
        return new self(
            array_merge($this->attributes, [$key => $value]),
            $this->translations,
            $this->meta,
            $this->metaFiles,
            $this->files,
        );
    }

    /**
     * Sütun dəyərini çıxarır.
     *
     * Lazımdır: forma «ikon tipi = şəkil» göndərib, amma yeni fayl seçməyibsə,
     * köhnə `icon` dəyəri üzərinə boş dəyər yazılmamalıdır.
     */
    public function withoutAttribute(string $key): self
    {
        $attributes = $this->attributes;
        unset($attributes[$key]);

        return new self($attributes, $this->translations, $this->meta, $this->metaFiles, $this->files);
    }

    public function attribute(string $key, mixed $default = null): mixed
    {
        return $this->attributes[$key] ?? $default;
    }

    public function file(string $key): ?UploadedFile
    {
        $file = $this->files[$key] ?? null;

        return $file instanceof UploadedFile ? $file : null;
    }

    /**
     * Fayl adı yaratmaq üçün mənbə: tərcümələr + sütunlar.
     *
     * `FileUploader::nameGenerate()` həm `title` (tək dilli), həm `title[az]`
     * (çoxdilli) formasını qəbul edir - ona görə ikisi birləşdirilir.
     */
    public function nameSource(): array
    {
        return $this->translations + $this->attributes;
    }
}
