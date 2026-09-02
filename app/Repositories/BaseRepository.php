<?php

declare(strict_types=1);

namespace App\Repositories;

use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * Yazma əməliyyatlarının tək yeri: insert / update / delete.
 *
 * NİYƏ ayrıca sinif:
 * Əvvəllər eyni "massivi modelə mənimsət və saxla" döngüsü hər controller-də
 * təkrarlanırdı. Bir gün `fillable` yoxlaması və ya tranzaksiya lazım olanda
 * onu 15 yerdə dəyişmək tələb olunurdu. İndi qayda burada bir dəfə yazılır.
 *
 * Burada SELECT YOXDUR - oxuma `app/Queries/` altındakı Query siniflərindədir
 * (bax: .claude/rules/01-umumi.md § 1).
 */
class BaseRepository
{
    /**
     * Modeli verilmiş atributlarla yazır.
     *
     * Yalnız `fillable`-da olan skalyar açarlar mənimsədilir: kənardan gələn
     * massivlər (`title[az]`, `meta[...]`) ayrıca layerdə (tərcümə / meta)
     * emal olunur, birbaşa sütuna yazılmır.
     */
    public function save(Model $item, array $attributes): Model
    {
        if (is_null($item)) {
            throw new InvalidArgumentException('Item cannot be null.');
        }

        $fillable = $item->getFillable();

        foreach ($attributes as $key => $value) {
            if (!is_array($value) && in_array($key, $fillable, true)) {
                $item->{$key} = $value;
            }
        }

        $item->save();

        return $item->fresh();
    }

    /**
     * Adi silmə. Modeldə `SoftDeletes` varsa bu, arxivləmə deməkdir -
     * sətir bazada qalır, `deleted_at` dolur.
     */
    public function delete(Model $item): bool
    {
        return (bool) $item->delete();
    }

    /**
     * Birdəfəlik silmə - sətir bazadan tamamilə gedir.
     *
     * Toplu rejimdə çağırılmır (bax: .claude/rules/02-gopanel.md § 7):
     * yalnız tək-tək və ayrıca icazə ilə.
     */
    public function forceDelete(Model $item): bool
    {
        return (bool) $item->forceDelete();
    }
}
