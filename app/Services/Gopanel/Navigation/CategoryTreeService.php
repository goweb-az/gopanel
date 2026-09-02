<?php

declare(strict_types=1);

namespace App\Services\Gopanel\Navigation;

use App\Models\Navigation\Category;
use App\Repositories\BaseRepository;
use RuntimeException;

/**
 * Kateqoriya ağacında sətrin yerinin dəyişməsi.
 *
 * NİYƏ servis:
 * Köçürmə controller-də iki sətir idi - `parent_id`-ni yaz və saxla. Amma
 * ağacda iki yoxlama olmadan sistem sınır:
 *
 *   1) sətir öz-özünün valideyni ola bilməz;
 *   2) sətir ÖZ ÖVLADININ altına köçürülə bilməz - onda həmin budaq ağacdan
 *      qopur (nə kökdə görünür, nə də başqa yerdə) və yalnız bazaya birbaşa
 *      baxmaqla tapılır.
 *
 * Bu qayda paneldən sürüşdürməklə də, formadan «valideyn» seçməklə də eyni
 * olmalıdır - ona görə tək yerdədir.
 */
class CategoryTreeService
{
    public function __construct(private readonly BaseRepository $repository)
    {
    }

    public function move(int $id, ?int $parentId): Category
    {
        /** @var Category $item */
        $item = Category::query()->findOrFail($id);

        if ($parentId !== null) {
            $this->guardTarget($item, $parentId);
        }

        return $this->repository->save($item, ['parent_id' => $parentId]);
    }

    private function guardTarget(Category $item, int $parentId): void
    {
        if ($parentId === (int) $item->id) {
            throw new RuntimeException('Kateqoriya öz-özünün valideyni ola bilməz.');
        }

        if ($this->isDescendant($parentId, (int) $item->id)) {
            throw new RuntimeException('Kateqoriya öz alt kateqoriyasının içinə köçürülə bilməz.');
        }
    }

    /**
     * `$candidateId` sətri `$ancestorId`-nin altındadırmı.
     *
     * Yuxarı doğru gedilir (aşağı yox): ağac dərinliyi kiçikdir, ona görə bu
     * yol bütün budağı yükləməkdən ucuzdur. `$seen` sonsuz dövrədən qoruyur -
     * bazada əvvəllər yaranmış pozuq bağlantı varsa proses ilişib qalmasın.
     */
    private function isDescendant(int $candidateId, int $ancestorId): bool
    {
        $seen    = [];
        $current = $candidateId;

        while ($current !== 0 && !isset($seen[$current])) {
            $seen[$current] = true;

            $parentId = (int) (Category::query()->whereKey($current)->value('parent_id') ?? 0);

            if ($parentId === $ancestorId) {
                return true;
            }

            $current = $parentId;
        }

        return false;
    }
}
