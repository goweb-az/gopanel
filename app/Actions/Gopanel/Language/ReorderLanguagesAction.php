<?php

namespace App\Actions\Gopanel\Language;

use App\Models\Geography\Language;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class ReorderLanguagesAction
{
    use AsAction;

    /**
     * @param  array<int, int|string>  $ids  Ordered list of Language IDs.
     */
    public function handle(array $ids): void
    {
        DB::transaction(function () use ($ids): void {
            foreach ($ids as $order => $id) {
                Language::where('id', $id)->update(['sort_order' => $order]);
            }
        });
    }
}
