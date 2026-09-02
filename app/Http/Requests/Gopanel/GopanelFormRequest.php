<?php

declare(strict_types=1);

namespace App\Http\Requests\Gopanel;

use App\DTOs\Gopanel\ContentPayload;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;

/**
 * Panel formalarının ortaq FormRequest əsası.
 *
 * NİYƏ lazımdır (üç səbəb):
 *
 * 1) İCAZƏ. Route-larda `can:` middleware-i hər modula yazılmayıb və düymənin
 *    blade-də gizlədilməsi tək müdafiə sayılmır (bax: 01-umumi.md § 5).
 *    Burada əlavə/redaktə ayrı-ayrı yoxlanılır: `.add` və `.edit`.
 *
 * 2) FORMANIN ŞƏKLİ. Hansı input sütuna, hansı tərcüməyə, hansı fayla gedir -
 *    bunu forma bilir. Servis `Request`-i görmür, hazır DTO alır
 *    (bax: 01-umumi.md § 1).
 *
 * 3) VALİDASİYA. Controller-də inline `validate()` qadağandır.
 *
 * Alt sinif adətən yalnız `$module`, `$translatedFields`, `$fileInputs` və
 * `rules()` elan edir.
 */
abstract class GopanelFormRequest extends FormRequest
{
    /** İcazə prefiksi, məs. `gopanel.blog`. Boş buraxılsa icazə yoxlanılmır. */
    protected string $module = '';

    /** `field_translations`-a gedən sahələr: `title`, `description`, `slug`... */
    protected array $translatedFields = [];

    /** Formadakı fayl input-ları: `image`, `icon_image`... */
    protected array $fileInputs = [];

    public function authorize(): bool
    {
        if ($this->module === '') {
            return true;
        }

        $admin = auth('gopanel')->user();

        if (!$admin) {
            return false;
        }

        return (bool) $admin->can($this->ability());
    }

    /**
     * Bu sorğu hansı icazəni tələb edir.
     *
     * Standart CRUD-da əlavə (`.add`) ilə redaktə (`.edit`) ayrıdır. Tək
     * sətirli səhifələr (Haqqımızda, Əlaqə, Sayt tənzimləmələri) isə həmişə
     * `.edit` işlədir - orada «əlavə etmək» anlayışı yoxdur, ilk saxlanış da
     * mahiyyətcə redaktədir; ona görə həmin modullarda bu metod override olunur.
     */
    protected function ability(): string
    {
        return $this->module . '.' . ($this->isUpdate() ? 'edit' : 'add');
    }

    /**
     * Formanın məzmununu layerlərə ayırır.
     *
     * `attributes`-ə hər şey düşür: hansı açarın sütun olduğunu
     * `BaseRepository` modelin `fillable`-ına baxaraq özü seçir. Beləliklə
     * forma yeni sahə əlavə edəndə burada siyahı yeniləmək lazım gəlmir.
     */
    public function payload(): ContentPayload
    {
        return new ContentPayload(
            attributes: $this->except(array_merge(['_token', 'meta'], $this->translatedFields, $this->fileInputs)),
            translations: $this->translationInput(),
            meta: (array) $this->input('meta', []),
            metaFiles: (array) $this->file('meta', []),
            files: $this->uploadedFiles(),
        );
    }

    /**
     * Formadakı fayl sahələrinin təsviri - `ContentSaveService` bunu işlədir.
     *
     * @return list<\App\DTOs\Gopanel\FileField>
     */
    public function fileFields(): array
    {
        return [];
    }

    /** Redaktədirmi? Route-da `{item}` varsa bəli. */
    protected function isUpdate(): bool
    {
        $item = $this->route('item');

        if ($item === null) {
            return false;
        }

        return is_object($item) ? isset($item->id) : true;
    }

    /** @return array<string, array<string, mixed>> */
    protected function translationInput(): array
    {
        $input = [];

        foreach ($this->translatedFields as $field) {
            $value = $this->input($field);

            if (is_array($value)) {
                $input[$field] = $value;
            }
        }

        return $input;
    }

    /** @return array<string, UploadedFile> */
    protected function uploadedFiles(): array
    {
        $files = [];

        foreach ($this->fileInputs as $input) {
            $file = $this->file($input);

            if ($file instanceof UploadedFile) {
                $files[$input] = $file;
            }
        }

        return $files;
    }

    /**
     * Şəkil sahələri üçün ortaq qayda.
     *
     * `svg` bilərəkdən yoxdur: SVG icra oluna bilən mətn faylıdır və panelə
     * yüklənən şəkillər saytda birbaşa göstərilir.
     */
    protected function imageRules(int $maxKb = 4096): array
    {
        return ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:' . $maxKb];
    }
}
