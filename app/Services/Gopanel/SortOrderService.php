<?php

declare(strict_types=1);

namespace App\Services\Gopanel;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Facades\Schema;

/**
 * Sürüklə-burax siyahının yeni sırasının yadda saxlanması.
 *
 * NİYƏ ayrıca servis (texnotech layihəsindən uyğunlaşdırılıb):
 * Əvvəlki həllər hər sətir üçün `find()->update()` edirdi - N sətir = 2N
 * sorğu, N dəfə `saved` event-i, N dəfə keş təmizlənməsi və tranzaksiya
 * olmadığından yarıda qırılan sorğu siyahını qarışıq sırada qoyurdu.
 *
 * Burada:
 *   - bütün sıra TƏK `UPDATE ... CASE` sorğusu ilə, tranzaksiya içində yazılır
 *     (ya hamısı, ya heç biri);
 *   - sorğu query builder ilədir - model event-ləri (activity log, keş flush)
 *     sətir başına İŞLƏMİR; keş sonda BİR dəfə təmizlənir;
 *   - sətir mümkün olanda `uid` ilə tapılır (daxili `id` DOM-a yazılmasın);
 *     `uid` sütunu olmayan köhnə cədvəldə ilkin açara düşülür.
 *
 * Açarların mövcudluğunu bu servis YOXLAMIR (tanınmayan açar sadəcə
 * yenilənmir) - ciddi yoxlama lazım olan çağıran (məs. LookupService) onu
 * əvvəlcədən edir.
 */
final class SortOrderService
{
    /**
     * @param  class-string<Model>  $modelClass
     * @param  array<int, string|int>  $keys  Sətir açarları, yeni ardıcıllıqla
     * @return int Yenilənən sətir sayı
     */
    public function apply(string $modelClass, array $keys): int
    {
        /** @var Model $model */
        $model = new $modelClass();

        if (!$this->hasSortOrder($model)) {
            return 0;
        }

        $keys = array_values(array_unique(array_map('strval', $keys)));

        if ($keys === []) {
            return 0;
        }

        $column = $this->keyColumn($model);
        $connection = $model->getConnection();

        $updated = $connection->transaction(function () use ($modelClass, $connection, $column, $keys) {
            // Dəyərlər PDO::quote ilə qaçırılır: CASE ifadəsi Expression-dur,
            // query builder ona binding qoşmur.
            $pdo = $connection->getPdo();
            $cases = '';

            foreach ($keys as $index => $key) {
                $cases .= ' WHEN ' . $pdo->quote($key) . ' THEN ' . ($index + 1);
            }

            $wrapped = $connection->getQueryGrammar()->wrap($column);

            // `toBase()` scope-ları (SoftDeletes) saxlayır, amma Eloquent
            // `update()`-in `updated_at` əlavəsini və event-ləri keçir.
            return $modelClass::query()
                ->whereIn($column, $keys)
                ->toBase()
                ->update(['sort_order' => new Expression('CASE ' . $wrapped . $cases . ' END')]);
        });

        // Event-lər işləmədiyi üçün modelin öz keşi (Cacheable) əl ilə, BİR dəfə.
        if (method_exists($modelClass, 'flushCache')) {
            $modelClass::flushCache();
        }

        return (int) $updated;
    }

    public function hasSortOrder(Model $model): bool
    {
        return Schema::connection($model->getConnectionName())
            ->hasColumn($model->getTable(), 'sort_order');
    }

    /**
     * Sətri tapan sütun: `uid` varsa o, yoxdursa ilkin açar.
     * Siyahı (DOM-dakı `data-id`) da eyni sütunu yazmalıdır.
     */
    public function keyColumn(Model $model): string
    {
        return Schema::connection($model->getConnectionName())->hasColumn($model->getTable(), 'uid')
            ? 'uid'
            : $model->getKeyName();
    }
}
