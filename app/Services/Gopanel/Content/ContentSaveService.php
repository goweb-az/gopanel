<?php

declare(strict_types=1);

namespace App\Services\Gopanel\Content;

use App\DTOs\Gopanel\ContentPayload;
use App\DTOs\Gopanel\FileField;
use App\Helpers\Gopanel\FileUploader;
use App\Helpers\Gopanel\Site\PageMetaDataHelper;
use App\Helpers\Gopanel\TranslationHelper;
use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Model;

/**
 * Panelin məzmun modullarının (bloq, kateqoriya, xidmət, məhsul, slayder,
 * haqqımızda...) yadda saxlama axını.
 *
 * NİYƏ ortaq servis:
 * Bu modulların `save()` metodları demək olar hərfbəhərf eyni idi -
 * fayl yüklə, sütunları yaz, tərcümələri yaz, SEO meta yaz. Kod yeddi yerdə
 * kopyalanmışdı və hər dəfə bir addım unudulurdu: kimisi meta saxlamırdı,
 * kimisi şəkil seçilməyəndə köhnə yolu boş dəyərlə əvəz edirdi. İndi
 * ardıcıllıq tək yerdədir, modul yalnız hansı fayl sahələrinin olduğunu deyir.
 *
 * ARDICILLIQ VACİBDİR: əvvəl fayl yüklənir (yol sütuna düşsün), sonra model
 * yazılır (id yaransın), yalnız bundan sonra tərcümə və meta - onlar modelin
 * `id`-sinə bağlıdır.
 */
class ContentSaveService
{
    public function __construct(private readonly BaseRepository $repository)
    {
    }

    /**
     * @param  list<FileField>  $fileFields  formadakı fayl sahələri
     */
    public function save(Model $item, ContentPayload $payload, array $fileFields = []): Model
    {
        $payload = $this->applyFiles($item, $payload, $fileFields);

        $item = $this->repository->save($item, $payload->attributes);

        if (!isset($item->id)) {
            return $item;
        }

        if ($payload->translations !== [] && !empty($item->translatedAttributes)) {
            TranslationHelper::fromInput($item, $payload->translations);
        }

        if (method_exists($item, 'meta')) {
            PageMetaDataHelper::save($item, $payload->meta, $payload->metaFiles);
        }

        return $item;
    }

    /**
     * Fayl sahələrini emal edir: yükləyib yolu sütuna yazır.
     *
     * Fayl gəlməyibsə sütun ÜMUMİYYƏTLƏ toxunulmur - əks halda formanın hər
     * saxlanışı mövcud şəkli silərdi.
     */
    private function applyFiles(Model $item, ContentPayload $payload, array $fileFields): ContentPayload
    {
        foreach ($fileFields as $field) {
            $file = $payload->file($field->input);

            if ($file === null) {
                $payload = $this->keepExisting($payload, $field);
                continue;
            }

            $fileName = $field->fileName
                ?? FileUploader::nameGenerate($payload->nameSource(), $field->prefix);

            $path = FileUploader::toPublic(
                $file,
                $field->folder ?? $item->getTable(),
                $fileName
            );

            $payload = $payload->withAttribute($field->column, $path);

            if ($field->typeColumn !== null) {
                $payload = $payload->withAttribute($field->typeColumn, 'image');
            }
        }

        return $payload;
    }

    /**
     * Yeni fayl seçilməyib.
     *
     * «İkon tipi = şəkil» qalıbsa, formadan gələn boş `icon` dəyəri köhnə
     * yolun üzərinə yazılmamalıdır - sütun massivdən çıxarılır.
     */
    private function keepExisting(ContentPayload $payload, FileField $field): ContentPayload
    {
        if ($field->typeColumn !== null && $payload->attribute($field->typeColumn) === 'image') {
            return $payload->withoutAttribute($field->column);
        }

        return $payload;
    }
}
