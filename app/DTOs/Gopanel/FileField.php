<?php

declare(strict_types=1);

namespace App\DTOs\Gopanel;

/**
 * Formadakı bir fayl sahəsinin təsviri: hansı input hansı sütuna düşür.
 *
 * NİYƏ lazımdır:
 * Fayl yükləmə bloku hər controller-də əl ilə yazılırdı və hər dəfə eyni
 * üç səhv təkrarlanırdı - qovluq adı fərqli yazılırdı, `nameGenerate()`
 * prefiksi unudulurdu, «şəkil seçilməyibsə köhnəsi qalsın» yoxlaması
 * yazılmırdı. Burada sahə TƏSVİR olunur, yükləməni servis edir.
 */
final class FileField
{
    /**
     * @param  string       $input       formadakı input adı (`image`, `icon_image`)
     * @param  string       $column      yolun yazılacağı sütun (`image`, `icon`)
     * @param  string|null  $prefix      fayl adı prefiksi (`blog`, `service-icon`)
     * @param  string|null  $folder      `public/site/<folder>`; null → modelin cədvəli
     * @param  string|null  $fileName    sabit fayl adı (loqo kimi dəyişməz fayllar üçün)
     * @param  string|null  $typeColumn  «tip» sütunu (`icon_type`) - fayl gələndə
     *                                   ora `image` yazılır, gəlməyəndə köhnə
     *                                   dəyər qorunur
     */
    public function __construct(
        public readonly string $input,
        public readonly string $column,
        public readonly ?string $prefix = null,
        public readonly ?string $folder = null,
        public readonly ?string $fileName = null,
        public readonly ?string $typeColumn = null,
    ) {
    }
}
