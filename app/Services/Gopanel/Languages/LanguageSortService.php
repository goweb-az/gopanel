<?php

namespace App\Services\Gopanel\Languages;

use App\Models\Geography\Language;
use Illuminate\Support\Facades\DB;

class LanguageSortService
{
    /**
     * Persist a new ordering for the given languages.
     *
     * $items is a validated list of ['id' => int, 'sort_order' => int]. The
     * update runs in a transaction with the affected rows locked so concurrent
     * sorts can't interleave, and the language cache is invalidated afterwards.
     */
    public function sort(array $items): void
    {
        DB::transaction(function () use ($items) {
            $ids = array_column($items, 'id');

            // Lock the affected rows for the duration of the transaction.
            Language::query()->whereIn('id', $ids)->lockForUpdate()->get();

            foreach ($items as $item) {
                Language::query()
                    ->where('id', $item['id'])
                    ->update(['sort_order' => $item['sort_order']]);
            }
        });

        Language::clearLanguageCache();
    }
}
